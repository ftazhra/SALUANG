<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class CleanupTrialTeachers extends Command
{
    protected $signature = 'saluang:cleanup-trial-teachers
        {--teacher=* : Target Guru ID:username; ulangi untuk setiap akun}
        {--protect= : Akun Guru yang dilindungi, format ID:username}
        {--protect-name= : Nama persis akun Guru yang dilindungi}
        {--execute : Jalankan penghapusan setelah seluruh pemeriksaan aman}
        {--deactivate : Nonaktifkan akun lewat soft-delete sambil mempertahankan riwayat}
        {--confirm= : Konfirmasi tindakan untuk kedua ID setelah backup}';

    protected $description = 'Dry run dampak dan hapus hanya akun Guru tanpa data terkait';

    private const USER_REFERENCE_COLUMNS = [
        'user_id',
        'teacher_id',
        'student_id',
        'guardian_id',
        'parent_id',
        'sender_id',
        'recipient_id',
        'created_by',
        'used_by',
        'owner_id',
        'author_id',
        'from_user_id',
        'to_user_id',
    ];

    public function handle(): int
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            $this->error('Tabel pengguna atau kolom peran tidak tersedia.');

            return self::FAILURE;
        }

        try {
            $targets = $this->resolveTargets();
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($targets->isEmpty()) {
            $targets = User::query()->where('role', 'guru')->orderBy('id')->get();
        }

        if ($targets->isEmpty()) {
            $this->info('Tidak ada akun Guru yang ditemukan.');

            return self::SUCCESS;
        }

        $this->displayImpact($targets);

        if (!$this->option('execute')) {
            $message = $this->option('deactivate')
                ? 'DRY RUN: --deactivate hanya akan soft-delete akun; relasi dan riwayat tetap disimpan.'
                : 'DRY RUN: tidak ada akun atau data yang diubah.';
            $this->warn($message);

            return self::SUCCESS;
        }

        try {
            $this->validateExecution($targets);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $blocked = $targets->mapWithKeys(fn (User $teacher) => [
            $teacher->id => $this->userReferences((int) $teacher->id),
        ])->filter(fn (array $references) => $references !== []);

        if ($this->option('deactivate')) {
            $deactivated = DB::transaction(function () use ($targets): bool {
                $ids = $targets->pluck('id')->map(fn ($id) => (int) $id)->all();
                $locked = User::query()
                    ->where('role', 'guru')
                    ->whereIn('id', $ids)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                if (!$this->executionIdentitiesMatch($targets, $locked)) {
                    return false;
                }
                foreach ($locked as $teacher) {
                    $teacher->delete();
                }
                return true;
            });
            if (!$deactivated) {
                $this->error('Penonaktifan dibatalkan karena identitas target berubah.');
                return self::FAILURE;
            }
            $this->info('Dua akun Guru dinonaktifkan dengan soft-delete. Riwayat dan relasi historis dipertahankan.');
            return self::SUCCESS;
        }

        if ($blocked->isNotEmpty()) {
            $this->error('Penghapusan ditolak: akun masih memiliki relasi/data. Tidak ada perubahan dilakukan.');

            return self::FAILURE;
        }

        $deleted = DB::transaction(function () use ($targets): bool {
            $ids = $targets->pluck('id')->map(fn ($id) => (int) $id)->all();
            $locked = User::query()
                ->where('role', 'guru')
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if (!$this->executionIdentitiesMatch($targets, $locked)) {
                return false;
            }

            foreach ($locked as $teacher) {
                if ($this->userReferences((int) $teacher->id) !== []) {
                    return false;
                }
            }

            foreach ($locked as $teacher) {
                $teacher->forceDelete();
            }

            return true;
        });

        if (!$deleted) {
            $this->error('Penghapusan dibatalkan karena daftar target atau relasinya berubah. Tidak ada akun yang dihapus.');

            return self::FAILURE;
        }

        $this->info('Dua akun Guru tanpa relasi berhasil dihapus. Data terkait tidak disentuh.');

        return self::SUCCESS;
    }

    private function resolveTargets()
    {
        $specifications = (array) $this->option('teacher');
        if ($specifications === []) {
            return collect();
        }

        $targets = collect();
        foreach ($specifications as $specification) {
            [$id, $username] = $this->parseIdentity((string) $specification);
            $teacher = $this->verifyTeacher($id, $username);
            if ($targets->contains('id', $teacher->id)) {
                throw new InvalidArgumentException('ID Guru target tidak boleh berulang.');
            }
            $targets->push($teacher);
        }

        return $targets;
    }

    private function validateExecution($targets): void
    {
        if ($targets->count() !== 2) {
            throw new InvalidArgumentException(
                'Mode eksekusi wajib menyebut tepat dua akun melalui --teacher=ID:username.'
            );
        }

        $protectSpecification = trim((string) $this->option('protect'));
        $protectName = trim((string) $this->option('protect-name'));
        if ($protectSpecification === '' || $protectName === '') {
            throw new InvalidArgumentException(
                'Mode eksekusi wajib menyebut --protect=ID:username dan --protect-name persis untuk akun Ibu Ayu.'
            );
        }

        [$protectId, $protectUsername] = $this->parseIdentity($protectSpecification);
        $protected = $this->verifyTeacher($protectId, $protectUsername);
        if ($protected->name !== $protectName) {
            throw new InvalidArgumentException(
                'Nama akun yang dilindungi tidak cocok persis. Verifikasi identitas Ibu Ayu terlebih dahulu.'
            );
        }
        if ($targets->contains('id', $protectId)) {
            throw new InvalidArgumentException('Akun Guru yang dilindungi tidak boleh menjadi target.');
        }

        $ids = $targets->pluck('id')->map(fn ($id) => (int) $id)->all();
        sort($ids);
        $action = $this->option('deactivate') ? 'NONAKTIFKAN' : 'HAPUS';
        $expectedConfirmation = $action . '-GURU-' . implode('-', $ids) . '-SETELAH-BACKUP';
        if ((string) $this->option('confirm') !== $expectedConfirmation) {
            throw new InvalidArgumentException(
                "Konfirmasi tindakan tidak cocok. Setelah backup diverifikasi, gunakan --confirm={$expectedConfirmation}."
            );
        }
    }

    private function executionIdentitiesMatch($targets, $locked): bool
    {
        if ($locked->count() !== 2) {
            return false;
        }

        foreach ($targets as $target) {
            $current = $locked->firstWhere('id', $target->id);
            if (
                !$current
                || $current->role !== 'guru'
                || $current->username !== $target->username
            ) {
                return false;
            }
        }

        [$protectedId, $protectedUsername] = $this->parseIdentity(
            (string) $this->option('protect')
        );
        if ($targets->contains('id', $protectedId)) {
            return false;
        }

        return User::query()
            ->whereKey($protectedId)
            ->where('role', 'guru')
            ->where('username', $protectedUsername)
            ->where('name', trim((string) $this->option('protect-name')))
            ->lockForUpdate()
            ->first() !== null;
    }

    private function parseIdentity(string $value): array
    {
        $parts = explode(':', trim($value), 2);
        if (count($parts) !== 2 || !ctype_digit($parts[0]) || trim($parts[1]) === '') {
            throw new InvalidArgumentException('Identitas harus berformat ID:username.');
        }

        return [(int) $parts[0], trim($parts[1])];
    }

    private function verifyTeacher(int $id, string $username): User
    {
        if (!Schema::hasColumn('users', 'username')) {
            throw new InvalidArgumentException('Kolom username tidak tersedia untuk verifikasi identitas.');
        }

        $matches = User::query()->where('username', $username)->get();
        if ($matches->count() !== 1 || (int) $matches->first()->id !== $id) {
            throw new InvalidArgumentException(
                "ID {$id} dan username yang diberikan tidak menunjuk tepat ke satu akun."
            );
        }

        $teacher = $matches->first();
        if ($teacher->role !== 'guru') {
            throw new InvalidArgumentException("Akun ID {$id} bukan Guru.");
        }

        return $teacher;
    }

    private function displayImpact($teachers): void
    {
        $accounts = $teachers->map(fn (User $teacher) => [
            $teacher->id,
            $teacher->name,
            $teacher->username ?? '-',
            $teacher->sekolah ?? '-',
        ])->all();
        $this->table(['ID', 'Nama Guru', 'Username', 'Sekolah'], $accounts);

        foreach ($teachers as $teacher) {
            $this->line("Dampak untuk Guru ID {$teacher->id} ({$teacher->name}):");
            $this->table(
                ['Jenis data', 'Jumlah'],
                collect($this->impactCounts((int) $teacher->id))
                    ->map(fn (int $count, string $label) => [$label, $count])
                    ->values()
                    ->all()
            );
            $references = $this->userReferences((int) $teacher->id);
            $this->table(
                ['Relasi langsung', 'Jumlah'],
                $references
                    ? collect($references)
                        ->map(fn (int $count, string $label) => [$label, $count])
                        ->values()
                        ->all()
                    : [['Tidak ada', 0]]
            );
        }
    }

    private function impactCounts(int $teacherId): array
    {
        $chapterIds = $this->ids('chapters', 'user_id', $teacherId);
        $subchapterIds = Schema::hasTable('subchapters')
            && $chapterIds !== []
            ? DB::table('subchapters')->whereIn('chapter_id', $chapterIds)->pluck('id')->all()
            : [];
        $attemptIds = $this->ids('evaluation_attempts', 'teacher_id', $teacherId);
        $discussionIds = $this->ids('discussions', 'user_id', $teacherId);

        return [
            'Materi Bab' => count($chapterIds),
            'Subbab' => $this->countIn('subchapters', 'chapter_id', $chapterIds),
            'Soal kuis' => $this->countIn('quiz_questions', 'subchapter_id', $subchapterIds),
            'Attempt kuis' => $this->countIn('student_quiz_attempts', 'subchapter_id', $subchapterIds),
            'Jawaban kuis pilihan ganda' => $this->countIn('student_quiz_answers', 'subchapter_id', $subchapterIds),
            'Jawaban kuis aktivitas' => $this->countIn('student_quiz_activity_answers', 'subchapter_id', $subchapterIds),
            'Soal Evaluasi lama' => $this->count('evaluation_questions', 'user_id', $teacherId),
            'Bank Evaluasi' => $this->count('evaluation_banks', 'teacher_id', $teacherId),
            'Attempt Evaluasi' => count($attemptIds),
            'Jawaban attempt Evaluasi' => $this->countIn('evaluation_attempts', 'id', $attemptIds, 'answers'),
            'Nilai kuis' => $this->countByChapterOrSubchapter($chapterIds, $subchapterIds),
            'Catatan Guru' => $this->count('student_teacher_notes', 'teacher_id', $teacherId)
                + $this->count('student_teacher_note_messages', 'teacher_id', $teacherId),
            'Diskusi dan balasan' => $this->count('discussions', 'user_id', $teacherId)
                + $this->count('discussion_replies', 'user_id', $teacherId)
                + $this->countIn('discussion_replies', 'discussion_id', $discussionIds),
            'Chat Orang Tua' => $this->count('parent_teacher_messages', 'teacher_id', $teacherId),
            'Undangan yang digunakan' => $this->count('teacher_invitations', 'used_by', $teacherId),
            'Penugasan kelas' => $this->count('teacher_class_assignments', 'teacher_id', $teacherId),
        ];
    }

    private function userReferences(int $userId): array
    {
        $references = [];
        foreach (Schema::getTableListing(null, false) as $table) {
            if ($table === 'migrations') {
                continue;
            }

            $columns = array_fill_keys(Schema::getColumnListing($table), true);
            if ($columns === []) {
                continue;
            }

            $referenceColumns = [];
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                $foreignTableParts = explode(
                    '.',
                    strtolower((string) ($foreignKey['foreign_table'] ?? ''))
                );
                $foreignTable = end($foreignTableParts);
                $foreignColumns = $foreignKey['foreign_columns'] ?? [];
                $localColumns = $foreignKey['columns'] ?? [];
                if (
                    $foreignTable === 'users'
                    && $foreignColumns === ['id']
                    && count($localColumns) === 1
                ) {
                    $referenceColumns[] = $localColumns[0];
                }
            }

            foreach (self::USER_REFERENCE_COLUMNS as $column) {
                if (isset($columns[$column])) {
                    $referenceColumns[] = $column;
                }
            }

            foreach (array_unique($referenceColumns) as $column) {
                $query = DB::table($table)->where($column, $userId);
                if ($table === 'users' && isset($columns['id'])) {
                    $query->where('id', '!=', $userId);
                }
                $count = $query->count();
                if ($count > 0) {
                    $references["{$table}.{$column}"] = $count;
                }
            }
        }

        return $references;
    }

    private function ids(string $table, string $column, int $value): array
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column)
            ? DB::table($table)->where($column, $value)->pluck('id')->all()
            : [];
    }

    private function count(string $table, string $column, int $value): int
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column)
            ? DB::table($table)->where($column, $value)->count()
            : 0;
    }

    private function countIn(
        string $table,
        string $column,
        array $values,
        ?string $presentColumn = null
    ): int {
        if (
            $values === []
            || !Schema::hasTable($table)
            || !Schema::hasColumn($table, $column)
        ) {
            return 0;
        }

        $query = DB::table($table)->whereIn($column, $values);
        if ($presentColumn !== null && Schema::hasColumn($table, $presentColumn)) {
            $query->whereNotNull($presentColumn)->where($presentColumn, '!=', '');
        }

        return $query->count();
    }

    private function countByChapterOrSubchapter(array $chapterIds, array $subchapterIds): int
    {
        if (
            !Schema::hasTable('student_assessment_results')
            || ($chapterIds === [] && $subchapterIds === [])
        ) {
            return 0;
        }

        $hasChapter = Schema::hasColumn('student_assessment_results', 'chapter_id');
        $hasSubchapter = Schema::hasColumn('student_assessment_results', 'subchapter_id');
        if (!$hasChapter && !$hasSubchapter) {
            return 0;
        }

        return DB::table('student_assessment_results')->where(function ($query) use (
            $chapterIds,
            $subchapterIds,
            $hasChapter,
            $hasSubchapter
        ): void {
            if ($hasChapter && $chapterIds !== []) {
                $query->whereIn('chapter_id', $chapterIds);
            }
            if ($hasSubchapter && $subchapterIds !== []) {
                $method = $hasChapter && $chapterIds !== [] ? 'orWhereIn' : 'whereIn';
                $query->{$method}('subchapter_id', $subchapterIds);
            }
        })->count();
    }
}
