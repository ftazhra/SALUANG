<?php

namespace App\Services;

use App\Models\User;
use App\Models\WebsiteNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WebsiteNotificationService
{
    public function latestFor(User $recipient, int $limit = 10): Collection
    {
        if (!Schema::hasTable('website_notifications')) {
            return collect();
        }

        return WebsiteNotification::query()
            ->where('recipient_id', $recipient->id)
            ->where('recipient_role', $recipient->role)
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (WebsiteNotification $notification): array => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'date' => $notification->created_at,
                'is_read' => $notification->read_at !== null,
                'url' => route('notifications.open', $notification),
                'event_type' => $notification->event_type,
                'source_type' => $notification->source_type,
                'source_id' => $notification->source_id,
            ]);
    }

    public function unreadCountFor(User $recipient): int
    {
        if (!Schema::hasTable('website_notifications')) {
            return 0;
        }

        return WebsiteNotification::query()
            ->where('recipient_id', $recipient->id)
            ->where('recipient_role', $recipient->role)
            ->whereNull('read_at')
            ->count();
    }

    public function completedAssessment(
        int $studentId,
        string $assessmentType,
        int $resultId
    ): void {
        if (!in_array($assessmentType, ['quiz', 'evaluation'], true)) {
            return;
        }

        DB::afterCommit(function () use ($studentId, $assessmentType, $resultId) {
            if (!$this->schemaReady()) {
                return;
            }

            $student = User::query()
                ->whereKey($studentId)
                ->where('role', 'siswa')
                ->first();
            if (!$student) {
                return;
            }

            $result = $assessmentType === 'quiz'
                ? DB::table('student_assessment_results')
                    ->where('id', $resultId)
                    ->where('user_id', $studentId)
                    ->where('assessment_type', 'quiz')
                    ->whereNotNull('completed_at')
                    ->first()
                : DB::table('evaluation_attempts')
                    ->where('id', $resultId)
                    ->where('user_id', $studentId)
                    ->whereNotNull('submitted_at')
                    ->whereNotNull('score')
                    ->first();
            if (!$result) {
                return;
            }

            $eventType = $assessmentType . '_result';
            $label = $assessmentType === 'quiz' ? 'Kuis' : 'Evaluasi Akhir';
            $sourceType = $assessmentType === 'quiz'
                ? 'student_assessment_result'
                : 'evaluation_attempt';
            $teacherIds = $assessmentType === 'quiz'
                ? $this->quizTeacherIds($result)
                : $this->evaluationTeacherIds($studentId, $resultId);
            $evaluationTeacherId = $assessmentType === 'evaluation'
                ? (int) ($teacherIds[0] ?? 0)
                : null;
            if (
                $assessmentType === 'evaluation'
                && (!$evaluationTeacherId
                    || !self::studentAssignedToTeacher($studentId, $evaluationTeacherId))
            ) {
                return;
            }
            $target = $assessmentType === 'quiz'
                ? '/dashboard?section=quiz&subchapter_id=' . (int) $result->subchapter_id
                : '/dashboard?section=evaluasi&teacher_id=' . $evaluationTeacherId;
            $eventKey = $eventType . ':' . $resultId;

            $this->store(
                $student,
                $eventKey,
                $eventType,
                $sourceType,
                $resultId,
                $studentId,
                'Hasil ' . $label . ' tersedia',
                'Hasil ' . $label . ' kamu sudah tersedia. Buka SALUANG untuk melihatnya.',
                $target
            );

            foreach ($teacherIds as $teacherId) {
                $teacher = User::query()
                    ->whereKey($teacherId)
                    ->where('role', 'guru')
                    ->first();
                if (!$teacher) {
                    continue;
                }

                $this->store(
                    $teacher,
                    $eventKey,
                    $eventType,
                    $sourceType,
                    $resultId,
                    $studentId,
                    'Hasil ' . $label . ' baru',
                    $student->name . ' menyelesaikan ' . $label . '. Nilai telah tersedia.',
                    '/dashboard?page=nilai&student_id=' . $studentId
                );
            }

            $guardians = DB::table('student_guardians as relation')
                ->join('users as guardian', 'guardian.id', '=', 'relation.guardian_id')
                ->where('relation.student_id', $studentId)
                ->where('relation.status', 'approved')
                ->where('guardian.role', 'orang_tua')
                ->get(['guardian.id']);

            foreach ($guardians as $guardianRow) {
                $guardian = User::find($guardianRow->id);
                if (!$guardian) {
                    continue;
                }

                $this->store(
                    $guardian,
                    $eventKey,
                    $eventType,
                    $sourceType,
                    $resultId,
                    $studentId,
                    'Pembaruan hasil belajar',
                    'Hasil ' . $label . ' ' . $student->name . ' telah tersedia. Buka SALUANG untuk melihat perkembangan anak.',
                    '/dashboard?page=hasil&child_id=' . $studentId
                        . ($evaluationTeacherId ? '&teacher_id=' . $evaluationTeacherId : '')
                );
            }
        });
    }

    public function publishedTeacherNote(int $studentId, int $messageId): void
    {
        DB::afterCommit(function () use ($studentId, $messageId) {
            if (!$this->schemaReady()) {
                return;
            }

            $note = DB::table('student_teacher_note_messages')
                ->where('id', $messageId)
                ->where('student_id', $studentId)
                ->whereNotNull('published_at')
                ->first(['teacher_id', 'audience']);
            if (!$note) {
                return;
            }

            $student = User::query()
                ->whereKey($studentId)
                ->where('role', 'siswa')
                ->first();
            $teacher = User::query()
                ->whereKey($note->teacher_id)
                ->where('role', 'guru')
                ->first();
            if (!$student || !$teacher) {
                return;
            }

            if ($note->audience === 'siswa') {
                $this->store(
                    $student,
                    'teacher_note:' . $messageId,
                    'teacher_note',
                    'student_teacher_note_message',
                    $messageId,
                    $studentId,
                    'Catatan Guru baru',
                    'Guru ' . $teacher->name . ' mengirim catatan baru untuk kamu.',
                    '/dashboard'
                );

                return;
            }

            if ($note->audience !== 'orang_tua') {
                return;
            }

            $guardians = DB::table('student_guardians as relation')
                ->join('users as guardian', 'guardian.id', '=', 'relation.guardian_id')
                ->where('relation.student_id', $studentId)
                ->where('relation.status', 'approved')
                ->where('guardian.role', 'orang_tua')
                ->get(['guardian.id']);

            foreach ($guardians as $guardianRow) {
                $guardian = User::find($guardianRow->id);
                if (!$guardian) {
                    continue;
                }

                $this->store(
                    $guardian,
                    'teacher_note:' . $messageId,
                    'teacher_note',
                    'student_teacher_note_message',
                    $messageId,
                    $studentId,
                    'Catatan Guru baru',
                    'Guru ' . $teacher->name . ' mengirim catatan baru untuk ' . $student->name . '.',
                    '/dashboard?page=hasil&child_id=' . $studentId
                );
            }
        });
    }

    public function chatMessageCreated(int $messageId): void
    {
        DB::afterCommit(function () use ($messageId) {
            if (!$this->schemaReady()) {
                return;
            }

            $message = DB::table('parent_teacher_messages')
                ->where('id', $messageId)
                ->first(['parent_id', 'student_id', 'teacher_id', 'sender_id']);
            if (
                !$message
                || !in_array((int) $message->sender_id, [
                    (int) $message->parent_id,
                    (int) $message->teacher_id,
                ], true)
                || !self::approvedThread($message)
            ) {
                return;
            }

            $student = User::query()
                ->whereKey($message->student_id)
                ->where('role', 'siswa')
                ->first();
            if (!$student) {
                return;
            }

            $recipientId = (int) $message->sender_id === (int) $message->parent_id
                ? (int) $message->teacher_id
                : (int) $message->parent_id;
            $recipient = User::find($recipientId);
            if (
                !$recipient
                || !in_array($recipient->role, ['guru', 'orang_tua'], true)
                || ($recipient->role === 'guru' && (int) $recipient->id !== (int) $message->teacher_id)
                || ($recipient->role === 'orang_tua' && (int) $recipient->id !== (int) $message->parent_id)
            ) {
                return;
            }

            $sender = User::find($message->sender_id);
            $relationshipId = DB::table('student_guardians')
                ->where('guardian_id', $message->parent_id)
                ->where('student_id', $message->student_id)
                ->where('status', 'approved')
                ->value('id');
            if (!$relationshipId) {
                return;
            }

            $this->store(
                $recipient,
                'chat_message:' . $messageId,
                'chat_message',
                'parent_teacher_message',
                $messageId,
                (int) $message->student_id,
                'Pesan baru dari ' . ($sender?->name ?? 'pengguna'),
                'Ada pesan baru di percakapan tentang ' . $student->name . '. Buka Chat di SALUANG.',
                $recipient->role === 'guru'
                    ? '/dashboard?page=orang-tua&relationship_id=' . (int) $relationshipId
                    : '/dashboard?page=chat&student_id=' . (int) $message->student_id
                        . '&teacher_id=' . (int) $message->teacher_id
            );
        });
    }

    public function publishedContent(int $teacherId, string $sourceType, int $sourceId): void
    {
        if (!in_array($sourceType, ['chapter', 'subchapter', 'quiz_question', 'evaluation_question'], true)) {
            return;
        }

        DB::afterCommit(function () use ($teacherId, $sourceType, $sourceId) {
            if (!$this->schemaReady()) {
                return;
            }

            $content = $this->publishedContentRecord($sourceType, $sourceId);
            if (!$content || (int) $content['teacher_id'] !== $teacherId) {
                return;
            }

            $classes = DB::table('teacher_class_assignments')
                ->where('teacher_id', $teacherId)
                ->pluck('class_name')
                ->unique()
                ->values();
            if ($classes->isEmpty()) {
                return;
            }

            $students = User::query()
                ->where('role', 'siswa')
                ->whereIn('kelas', $classes)
                ->get(['id', 'role']);
            $eventType = in_array($sourceType, ['chapter', 'subchapter'], true)
                ? 'material_published'
                : 'activity_published';
            $title = $eventType === 'material_published' ? 'Materi baru tersedia' : 'Aktivitas baru tersedia';
            $label = $eventType === 'material_published' ? 'materi' : 'aktivitas';
            $target = match ($sourceType) {
                'evaluation_question' => '/dashboard?section=evaluasi',
                'chapter' => '/dashboard?section=material&chapter_id=' . $sourceId,
                default => '/dashboard?section=quiz&subchapter_id=' .
                    (int) ($content['subchapter_id'] ?? 0),
            };

            foreach ($students as $student) {
                $this->store(
                    $student,
                    $eventType . ':' . $sourceType . ':' . $sourceId,
                    $eventType,
                    $sourceType,
                    $sourceId,
                    (int) $student->id,
                    $title,
                    'Guru memublikasikan ' . $label . ' "' . $content['title'] . '".',
                    $target
                );
            }
        });
    }

    public function open(User $recipient, int $notificationId): string
    {
        abort_unless(Schema::hasTable('website_notifications'), 503, 'Migration notifikasi website belum diterapkan.');

        $notification = WebsiteNotification::query()
            ->whereKey($notificationId)
            ->where('recipient_id', $recipient->id)
            ->where('recipient_role', $recipient->role)
            ->firstOrFail();
        abort_unless($this->isContextAccessible($notification, $recipient), 404);

        $parts = parse_url($notification->target_url);
        abort_unless(
            is_array($parts)
                && isset($parts['path'])
                && str_starts_with($parts['path'], '/')
                && !str_starts_with($parts['path'], '//')
                && !isset($parts['host'], $parts['scheme']),
            404
        );

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return $notification->target_url;
    }

    public function deleteFor(User $recipient, int $notificationId): void
    {
        abort_unless(Schema::hasTable('website_notifications'), 503, 'Migration notifikasi website belum diterapkan.');

        WebsiteNotification::query()
            ->whereKey($notificationId)
            ->where('recipient_id', $recipient->id)
            ->where('recipient_role', $recipient->role)
            ->firstOrFail()
            ->delete();
    }

    private function schemaReady(): bool
    {
        if (Schema::hasTable('website_notifications')) {
            return true;
        }

        Log::warning('Website notification skipped because its migration is not applied.');

        return false;
    }

    private function store(
        User $recipient,
        string $eventKey,
        string $eventType,
        string $sourceType,
        int $sourceId,
        ?int $studentId,
        string $title,
        string $message,
        string $targetUrl
    ): void {
        DB::table('website_notifications')->upsert(
            [[
                'recipient_id' => $recipient->id,
                'recipient_role' => $recipient->role,
                'event_key' => $eventKey,
                'event_type' => $eventType,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'student_id' => $studentId,
                'title' => mb_substr($title, 0, 120),
                'message' => mb_substr($message, 0, 500),
                'target_url' => $targetUrl,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['recipient_id', 'event_key'],
            ['event_key']
        );
    }

    private function quizTeacherIds(object $result): array
    {
        $chapterId = $result->chapter_id ?? null;
        if (!empty($result->subchapter_id)) {
            $chapterId = DB::table('subchapters')
                ->where('id', $result->subchapter_id)
                ->value('chapter_id');
        }

        $teacherId = $chapterId
            ? DB::table('chapters')->where('id', $chapterId)->value('user_id')
            : null;

        return $teacherId ? [(int) $teacherId] : [];
    }

    private function evaluationTeacherIds(int $studentId, int $attemptId): array
    {
        return collect(app(EvaluationResultService::class)->validCompletedForStudent($studentId))
            ->filter(fn (array $result): bool => (int) $result['id'] === $attemptId)
            ->pluck('teacher_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function isContextAccessible(WebsiteNotification $notification, User $recipient): bool
    {
        $studentId = (int) $notification->student_id;
        if ($studentId < 1) {
            return false;
        }

        if (in_array($notification->event_type, ['quiz_result', 'evaluation_result'], true)) {
            $type = $notification->event_type === 'quiz_result' ? 'quiz' : 'evaluation';
            if ($recipient->role === 'siswa') {
                return (int) $recipient->id === $studentId
                    && $this->assessmentExists($type, (int) $notification->source_id, $studentId);
            }
            if ($recipient->role === 'orang_tua') {
                $authorized = self::hasApprovedGuardianLink((int) $recipient->id, $studentId)
                    && $this->assessmentExists($type, (int) $notification->source_id, $studentId);

                return $authorized && (
                    $type !== 'evaluation'
                    || $this->evaluationAccessibleToGuardian(
                        (int) $notification->source_id,
                        $studentId
                    )
                );
            }
            if ($recipient->role === 'guru') {
                return app(EvaluationResultService::class)->findForTeacher(
                    (int) $recipient->id,
                    $studentId,
                    $type,
                    (int) $notification->source_id
                ) !== null;
            }

            return false;
        }

        if ($notification->event_type === 'teacher_note') {
            $audience = match ($recipient->role) {
                'siswa' => 'siswa',
                'orang_tua' => 'orang_tua',
                default => null,
            };
            if (
                $audience === null
                || !DB::table('student_teacher_note_messages')
                    ->where('id', $notification->source_id)
                    ->where('student_id', $studentId)
                    ->where('audience', $audience)
                    ->whereNotNull('published_at')
                    ->exists()
            ) {
                return false;
            }

            return $recipient->role === 'siswa'
                ? (int) $recipient->id === $studentId
                : self::hasApprovedGuardianLink((int) $recipient->id, $studentId);
        }

        if ($notification->event_type === 'chat_message') {
            $message = DB::table('parent_teacher_messages')
                ->where('id', $notification->source_id)
                ->where('student_id', $studentId)
                ->first(['parent_id', 'student_id', 'teacher_id', 'sender_id']);
            if (!$message || !self::approvedThread($message)) {
                return false;
            }

            return ($recipient->role === 'guru'
                    && (int) $recipient->id === (int) $message->teacher_id
                    && (int) $message->sender_id === (int) $message->parent_id)
                || ($recipient->role === 'orang_tua'
                    && (int) $recipient->id === (int) $message->parent_id
                    && (int) $message->sender_id === (int) $message->teacher_id);
        }

        if (in_array($notification->event_type, ['material_published', 'activity_published'], true)) {
            if ($recipient->role !== 'siswa' || (int) $recipient->id !== $studentId) {
                return false;
            }
            $content = $this->publishedContentRecord(
                $notification->source_type,
                (int) $notification->source_id
            );
            if (!$content || !self::studentAssignedToTeacher($studentId, (int) $content['teacher_id'])) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function assessmentExists(string $type, int $resultId, int $studentId): bool
    {
        if ($type === 'evaluation') {
            return collect(
                app(EvaluationResultService::class)->validCompletedForStudent($studentId)
            )->contains(fn (array $result): bool => $result['id'] === $resultId);
        }

        $table = $type === 'quiz' ? 'student_assessment_results' : 'evaluation_attempts';
        $completedColumn = $type === 'quiz' ? 'completed_at' : 'submitted_at';

        return Schema::hasTable($table)
            && DB::table($table)
                ->where('id', $resultId)
                ->where('user_id', $studentId)
                ->whereNotNull($completedColumn)
                ->where('assessment_type', 'quiz')
                ->exists();
    }

    private function evaluationAccessibleToGuardian(int $attemptId, int $studentId): bool
    {
        $result = collect(app(EvaluationResultService::class)->validCompletedForStudent($studentId))
            ->first(fn (array $attempt): bool => $attempt['id'] === $attemptId);

        return $result !== null
            && $result['teacher_id'] !== null
            && self::studentAssignedToTeacher($studentId, (int) $result['teacher_id']);
    }

    private function publishedContentRecord(string $sourceType, int $sourceId): ?array
    {
        if ($sourceType === 'chapter') {
            $chapter = DB::table('chapters')
                ->where('id', $sourceId)
                ->where('is_published', true)
                ->first(['id', 'user_id', 'judul']);

            return $chapter ? [
                'teacher_id' => (int) $chapter->user_id,
                'title' => $chapter->judul,
                'subchapter_id' => null,
            ] : null;
        }

        if ($sourceType === 'subchapter') {
            $subchapter = DB::table('subchapters as subchapter')
                ->join('chapters as chapter', 'chapter.id', '=', 'subchapter.chapter_id')
                ->where('subchapter.id', $sourceId)
                ->where('subchapter.is_published', true)
                ->where('chapter.is_published', true)
                ->first([
                    'chapter.user_id as teacher_id',
                    'subchapter.judul as title',
                    'subchapter.id as subchapter_id',
                ]);

            return $subchapter ? (array) $subchapter : null;
        }

        if ($sourceType === 'quiz_question') {
            $question = DB::table('quiz_questions as question')
                ->join('subchapters as subchapter', 'subchapter.id', '=', 'question.subchapter_id')
                ->join('chapters as chapter', 'chapter.id', '=', 'subchapter.chapter_id')
                ->where('question.id', $sourceId)
                ->where('question.is_published', true)
                ->where('subchapter.is_published', true)
                ->where('chapter.is_published', true)
                ->first([
                    'chapter.user_id as teacher_id',
                    'question.pertanyaan as title',
                    'subchapter.id as subchapter_id',
                ]);

            return $question ? (array) $question : null;
        }

        if ($sourceType === 'evaluation_question') {
            $question = DB::table('evaluation_questions')
                ->where('id', $sourceId)
                ->where('is_published', true)
                ->first(['user_id', 'pertanyaan']);

            return $question ? [
                'teacher_id' => (int) $question->user_id,
                'title' => $question->pertanyaan,
                'subchapter_id' => null,
            ] : null;
        }

        return null;
    }

    private static function hasApprovedGuardianLink(int $guardianId, int $studentId): bool
    {
        return DB::table('student_guardians')
            ->where('guardian_id', $guardianId)
            ->where('student_id', $studentId)
            ->where('status', 'approved')
            ->whereExists(function ($query) use ($studentId) {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', 'student_guardians.student_id')
                    ->where('users.role', 'siswa')
                    ->whereNull('users.deleted_at')
                    ->where('users.id', $studentId);
            })
            ->exists();
    }

    private static function approvedThread(object $message): bool
    {
        $approved = self::hasApprovedGuardianLink(
            (int) $message->parent_id,
            (int) $message->student_id
        );
        if (!$approved) {
            return false;
        }

        $student = User::query()
            ->whereKey($message->student_id)
            ->where('role', 'siswa')
            ->first(['id', 'kelas', 'sekolah']);
        $teacher = User::query()
            ->whereKey($message->teacher_id)
            ->where('role', 'guru')
            ->first(['id', 'sekolah']);
        if (!$student || !$teacher) {
            return false;
        }

        $assigned = DB::table('teacher_class_assignments')
            ->join('users as assigned_teacher', 'assigned_teacher.id', '=', 'teacher_class_assignments.teacher_id')
            ->where('teacher_id', $message->teacher_id)
            ->where('teacher_class_assignments.class_name', $student->kelas)
            ->where('assigned_teacher.role', 'guru')
            ->when(
                $student->sekolah === null,
                fn ($query) => $query->whereNull('assigned_teacher.sekolah'),
                fn ($query) => $query->where('assigned_teacher.sekolah', $student->sekolah)
            )
            ->exists();
        if ($assigned) {
            return true;
        }

        return DB::table('student_assessment_results as result')
            ->leftJoin('subchapters as subchapter', 'subchapter.id', '=', 'result.subchapter_id')
            ->leftJoin('chapters as subchapter_chapter', 'subchapter_chapter.id', '=', 'subchapter.chapter_id')
            ->leftJoin('chapters as direct_chapter', 'direct_chapter.id', '=', 'result.chapter_id')
            ->where('result.user_id', $message->student_id)
            ->where('result.assessment_type', 'quiz')
            ->whereNotNull('result.completed_at')
            ->whereRaw(
                'CASE WHEN result.subchapter_id IS NOT NULL THEN subchapter_chapter.user_id ELSE direct_chapter.user_id END = ?',
                [(int) $message->teacher_id]
            )
            ->exists();
    }

    private static function studentAssignedToTeacher(int $studentId, int $teacherId): bool
    {
        $student = User::query()
            ->whereKey($studentId)
            ->where('role', 'siswa')
            ->first(['id', 'kelas', 'sekolah']);
        if (!$student) {
            return false;
        }

        return DB::table('teacher_class_assignments')
            ->join('users as assigned_teacher', 'assigned_teacher.id', '=', 'teacher_class_assignments.teacher_id')
            ->where('teacher_class_assignments.teacher_id', $teacherId)
            ->where('teacher_class_assignments.class_name', $student->kelas)
            ->where('assigned_teacher.role', 'guru')
            ->when(
                $student->sekolah === null,
                fn ($query) => $query->whereNull('assigned_teacher.sekolah'),
                fn ($query) => $query->where('assigned_teacher.sekolah', $student->sekolah)
            )
            ->exists();
    }
}
