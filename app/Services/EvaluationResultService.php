<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EvaluationResultService
{
    public function quizResultsForTeacher(int $teacherId, ?array $studentIds = null): array
    {
        return $this->quizResults($teacherId, $studentIds);
    }

    public function quizResultsForStudent(int $studentId): array
    {
        return $this->quizResults(null, [$studentId]);
    }

    private function quizResults(?int $teacherId, ?array $studentIds): array
    {
        if (
            !Schema::hasTable('student_assessment_results')
            || !Schema::hasTable('chapters')
            || !Schema::hasTable('subchapters')
        ) {
            return [];
        }

        if ($studentIds !== null && $studentIds === []) {
            return [];
        }

        $query = DB::table('student_assessment_results as result')
            ->leftJoin('chapters as direct_chapter', 'direct_chapter.id', '=', 'result.chapter_id')
            ->leftJoin('subchapters as subchapter', 'subchapter.id', '=', 'result.subchapter_id')
            ->leftJoin('chapters as subchapter_chapter', 'subchapter_chapter.id', '=', 'subchapter.chapter_id')
            ->leftJoin('users as direct_teacher', 'direct_teacher.id', '=', 'direct_chapter.user_id')
            ->leftJoin('users as subchapter_teacher', 'subchapter_teacher.id', '=', 'subchapter_chapter.user_id')
            ->where('result.assessment_type', 'quiz')
            ->whereNotNull('result.score')
            ->whereBetween('result.score', [0, 100])
            ->whereNotNull('result.completed_at')
            ->where('result.total_questions', '>', 0)
            ->where('result.correct_answers', '>=', 0)
            ->whereColumn('result.correct_answers', '<=', 'result.total_questions')
            ->whereRaw(
                "(CASE WHEN result.subchapter_id IS NOT NULL THEN subchapter_teacher.role ELSE direct_teacher.role END) = ?",
                ['guru']
            )
            ->orderByDesc('result.completed_at')
            ->orderByDesc('result.id');

        if ($teacherId !== null) {
            $query->where(function ($chapters) use ($teacherId) {
                $chapters->where(function ($subchapter) use ($teacherId) {
                    $subchapter->whereNotNull('result.subchapter_id')
                        ->where('subchapter_chapter.user_id', $teacherId);
                })->orWhere(function ($directChapter) use ($teacherId) {
                    $directChapter->whereNull('result.subchapter_id')
                        ->where('direct_chapter.user_id', $teacherId);
                });
            });
        }

        if ($studentIds !== null) {
            $query->whereIn('result.user_id', $studentIds);
        }

        return $query->get([
            'result.id',
            'result.user_id',
            'result.chapter_id',
            'result.subchapter_id',
            'result.total_questions',
            'result.correct_answers',
            'result.score',
            'result.completed_at',
            'direct_chapter.judul as direct_chapter_title',
            'direct_teacher.id as direct_teacher_id',
            'direct_teacher.name as direct_teacher_name',
            'subchapter_chapter.id as subchapter_chapter_id',
            'subchapter_chapter.judul as subchapter_chapter_title',
            'subchapter_teacher.id as subchapter_teacher_id',
            'subchapter_teacher.name as subchapter_teacher_name',
            'subchapter.judul as subchapter_title',
        ])->map(fn(object $result) => [
            'id' => (int) $result->id,
            'user_id' => (int) $result->user_id,
            'assessment_type' => 'quiz',
            'chapter_id' => ($result->subchapter_chapter_id ?? $result->chapter_id) === null
                ? null
                : (int) ($result->subchapter_chapter_id ?? $result->chapter_id),
            'chapter_title' => $result->subchapter_chapter_title ?? $result->direct_chapter_title,
            'subchapter_id' => $result->subchapter_id === null ? null : (int) $result->subchapter_id,
            'subchapter_title' => $result->subchapter_title,
            'teacher_id' => (int) ($result->subchapter_id !== null
                ? $result->subchapter_teacher_id
                : $result->direct_teacher_id),
            'teacher_name' => $result->subchapter_id !== null
                ? $result->subchapter_teacher_name
                : $result->direct_teacher_name,
            'total_questions' => (int) $result->total_questions,
            'correct_answers' => (int) $result->correct_answers,
            'score' => (float) $result->score,
            'completed_at' => $result->completed_at,
        ])->all();
    }

    public function findForTeacher(
        int $teacherId,
        int $studentId,
        string $assessmentType,
        int $resultId
    ): ?array {
        if (!in_array($assessmentType, ['quiz', 'evaluation'], true)) {
            return null;
        }

        $results = $assessmentType === 'quiz'
            ? $this->quizResultsForTeacher($teacherId, [$studentId])
            : $this->validCompletedForTeacher($teacherId, [$studentId]);

        foreach ($results as $result) {
            if ($result['id'] === $resultId && $result['user_id'] === $studentId) {
                return $result;
            }
        }

        return null;
    }

    public function latestQuizByStudentForTeacher(int $teacherId, ?array $studentIds = null): array
    {
        $latest = [];

        foreach ($this->quizResultsForTeacher($teacherId, $studentIds) as $result) {
            if (!array_key_exists($result['user_id'], $latest)) {
                $latest[$result['user_id']] = $result;
            }
        }

        return $latest;
    }

    public function completedForTeacher(int $teacherId, ?array $studentIds = null): array
    {
        if (
            !Schema::hasTable('evaluation_attempts')
            || !Schema::hasColumn('evaluation_attempts', 'teacher_id')
        ) {
            return [];
        }

        if ($studentIds !== null && $studentIds === []) {
            return [];
        }

        $query = DB::table('evaluation_attempts')
            ->where(function ($attempts) use ($teacherId) {
                $attempts->where('teacher_id', $teacherId)
                    ->orWhereNull('teacher_id');
            })
            ->whereNotNull('submitted_at')
            ->whereNotNull('score')
            ->whereBetween('score', [0, 100])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        if ($studentIds !== null) {
            $query->whereIn('user_id', $studentIds);
        }

        return collect($this->projectAttempts($query->get()))
            ->filter(fn(array $result) => $result['teacher_id'] === $teacherId)
            ->values()
            ->all();
    }

    public function validCompletedForTeacher(int $teacherId, ?array $studentIds = null): array
    {
        return array_values(array_filter(
            $this->completedForTeacher($teacherId, $studentIds),
            fn(array $result) => $result['valid_for_grading']
        ));
    }

    public function latestByStudentForTeacher(
        int $teacherId,
        ?array $studentIds = null
    ): array {
        $latest = [];

        foreach ($this->validCompletedForTeacher($teacherId, $studentIds) as $result) {
            $studentId = $result['user_id'];
            if (!array_key_exists($studentId, $latest)) {
                $latest[$studentId] = $result;
            }
        }

        return $latest;
    }

    public function completedForStudent(int $studentId): array
    {
        if (
            !Schema::hasTable('evaluation_attempts')
            || !Schema::hasColumn('evaluation_attempts', 'teacher_id')
        ) {
            return [];
        }

        $attempts = DB::table('evaluation_attempts')
            ->where('user_id', $studentId)
            ->whereNotNull('submitted_at')
            ->whereNotNull('score')
            ->whereBetween('score', [0, 100])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        return $this->projectAttempts($attempts);
    }

    public function validCompletedForStudent(int $studentId): array
    {
        return array_values(array_filter(
            $this->completedForStudent($studentId),
            fn(array $result) => $result['valid_for_grading']
        ));
    }

    private function projectAttempts($attempts): array
    {
        $questionIds = [];
        foreach ($attempts as $attempt) {
            $snapshot = json_decode((string) $attempt->question_snapshot, true);
            foreach (is_array($snapshot) ? $snapshot : [] as $question) {
                if (is_array($question) && isset($question['id']) && is_numeric($question['id'])) {
                    $questionIds[] = (int) $question['id'];
                }
            }
        }

        $archivesByQuestion = [];
        if ($questionIds && Schema::hasTable('evaluation_question_archives')) {
            $archives = DB::table('evaluation_question_archives')
                ->whereIn('question_id', array_values(array_unique($questionIds)))
                ->get(['question_id', 'teacher_id', 'question_snapshot']);

            foreach ($archives as $archive) {
                $archiveSnapshot = json_decode(
                    (string) $archive->question_snapshot,
                    true
                );
                $archivesByQuestion[(int) $archive->question_id][] = [
                    'teacher_id' => (int) $archive->teacher_id,
                    'attempt_id_cutoff' => is_array($archiveSnapshot)
                        && isset($archiveSnapshot['_attempt_id_cutoff'])
                        && is_numeric($archiveSnapshot['_attempt_id_cutoff'])
                        ? (int) $archiveSnapshot['_attempt_id_cutoff']
                        : null,
                ];
            }
        }

        $projected = $attempts->map(fn(object $attempt): array => $this->project($attempt));
        $legacyTeacherIds = $projected
            ->filter(fn(array $result) => $result['teacher_id'] !== null
                && $result['evaluation_bank_id'] === null)
            ->pluck('teacher_id')
            ->unique()
            ->values()
            ->all();
        $legacyQuestionIdsByTeacher = [];
        if (
            $legacyTeacherIds
            && Schema::hasTable('evaluation_questions')
            && collect([
                'id',
                'user_id',
                'nomor_soal',
                'tipe_soal',
                'pertanyaan',
                'konten_soal',
                'kunci_jawaban',
                'is_published',
            ])->every(fn(string $column) => Schema::hasColumn('evaluation_questions', $column))
        ) {
            $legacyQuestionIdsByTeacher = DB::table('evaluation_questions')
                ->whereIn('user_id', $legacyTeacherIds)
                ->where('is_published', true)
                ->orderBy('user_id')
                ->orderBy('nomor_soal')
                ->get([
                    'id',
                    'user_id',
                    'nomor_soal',
                    'tipe_soal',
                    'pertanyaan',
                    'konten_soal',
                    'kunci_jawaban',
                ])
                ->groupBy('user_id')
                ->map(function ($questions): array {
                    if ($questions->count() !== 15) {
                        return [];
                    }

                    $numbers = [];
                    $ids = [];
                    foreach ($questions as $question) {
                        $content = $this->decodeArray($question->konten_soal);
                        $key = $this->decodeArray($question->kunci_jawaban);
                        if (
                            $question->tipe_soal !== 'pilihan_ganda'
                            || !is_string($question->pertanyaan)
                            || trim($question->pertanyaan) === ''
                            || !in_array($key['jawaban'] ?? null, ['A', 'B', 'C', 'D'], true)
                        ) {
                            return [];
                        }

                        foreach (['a', 'b', 'c', 'd'] as $letter) {
                            if (
                                !is_string($content['pilihan_' . $letter] ?? null)
                                || trim($content['pilihan_' . $letter]) === ''
                            ) {
                                return [];
                            }
                        }

                        $numbers[] = (int) $question->nomor_soal;
                        $ids[] = (int) $question->id;
                    }

                    sort($numbers);

                    return $numbers === range(1, 15) ? $ids : [];
                })
                ->all();
        }

        return $attempts->map(function (object $attempt, int $index) use (
            $archivesByQuestion,
            $legacyQuestionIdsByTeacher,
            $projected
        ): array {
            $result = $projected[$index];
            $result['valid_for_grading'] = $this->isValidForGrading(
                $attempt,
                $result['teacher_id'],
                $archivesByQuestion,
                $legacyQuestionIdsByTeacher
            );

            return $result;
        })->all();
    }

    private function isValidForGrading(
        object $attempt,
        ?int $teacherId,
        array $archivesByQuestion,
        array $legacyQuestionIdsByTeacher
    ): bool {
        if ($teacherId === null || empty($attempt->submitted_at)) {
            return false;
        }

        $snapshot = json_decode((string) $attempt->question_snapshot, true);
        $snapshotIds = [];
        foreach (is_array($snapshot) ? $snapshot : [] as $question) {
            if (!is_array($question) || !isset($question['id']) || !is_numeric($question['id'])) {
                return false;
            }
            $snapshotIds[] = (int) $question['id'];
        }

        if (
            count($snapshotIds) !== 15
            || count(array_unique($snapshotIds)) !== 15
        ) {
            return false;
        }

        if (isset($attempt->evaluation_bank_id) && $attempt->evaluation_bank_id !== null) {
            return $this->isActiveBankAttempt($attempt, $teacherId);
        }

        $activeQuestionIds = $legacyQuestionIdsByTeacher[$teacherId] ?? [];
        $activeQuestionIdSet = array_fill_keys($activeQuestionIds, true);
        foreach ($snapshotIds as $questionId) {
            foreach ($archivesByQuestion[$questionId] ?? [] as $archive) {
                if ($archive['teacher_id'] !== $teacherId) {
                    continue;
                }

                if (
                    ($archive['attempt_id_cutoff'] !== null
                        && (int) $attempt->id <= $archive['attempt_id_cutoff'])
                    || ($archive['attempt_id_cutoff'] === null
                        && !isset($activeQuestionIdSet[$questionId]))
                ) {
                    return false;
                }
            }
        }

        sort($snapshotIds);
        sort($activeQuestionIds);

        if (count($activeQuestionIds) !== 15 || $snapshotIds !== $activeQuestionIds) {
            return false;
        }

        // Nomor dan ID yang sama belum menjamin isi soal belum berubah.
        // Cocokkan snapshot pengerjaan dengan bank soal saat ini.
        return $this->matchesCurrentQuestions(
            $snapshot,
            'evaluation_questions',
            'user_id',
            $teacherId
        );
    }

    private function isActiveBankAttempt(object $attempt, int $teacherId): bool
    {
        if (
            !Schema::hasTable('evaluation_banks')
            || !Schema::hasColumn('evaluation_banks', 'teacher_id')
            || !Schema::hasColumn('evaluation_banks', 'is_published')
            || !Schema::hasColumn('evaluation_banks', 'active_slot')
        ) {
            return false;
        }

        $bank = DB::table('evaluation_banks')
            ->where('id', (int) $attempt->evaluation_bank_id)
            ->first();
        if (
            !$bank
            || (int) $bank->teacher_id !== $teacherId
            || !(bool) $bank->is_published
            || (int) $bank->active_slot !== 1
        ) {
            return false;
        }

        if (
            isset($attempt->chapter_id)
            && $attempt->chapter_id !== null
            && (int) $attempt->chapter_id !== (int) $bank->chapter_id
        ) {
            return false;
        }

        if (
            isset($bank->class_name)
            && Schema::hasTable('users')
            && Schema::hasColumn('users', 'kelas')
        ) {
            $studentClass = DB::table('users')
                ->where('id', (int) $attempt->user_id)
                ->value('kelas');
            if (
                $studentClass === null
                || mb_strtolower(trim((string) $studentClass))
                !== mb_strtolower(trim((string) $bank->class_name))
            ) {
                return false;
            }
        }

        // Bank tetap aktif belum berarti pertanyaan dan kunci jawabannya
        // sama dengan yang dikerjakan siswa pada attempt terdahulu.
        $snapshot = json_decode((string) $attempt->question_snapshot, true);

        return $this->matchesCurrentQuestions(
            is_array($snapshot) ? $snapshot : [],
            'evaluation_bank_questions',
            'evaluation_bank_id',
            (int) $bank->id
        );
    }

    /** Bandingkan substansi snapshot agar revisi soal mewajibkan pengerjaan ulang. */
    private function matchesCurrentQuestions(
        array $snapshot,
        string $table,
        string $foreignKey,
        int $ownerId
    ): bool {
        if (!Schema::hasTable($table)) {
            return false;
        }

        $questions = DB::table($table)
            ->where($foreignKey, $ownerId);
        if ($table === 'evaluation_questions') {
            $questions->where('is_published', true);
        }

        $current = $questions->get([
            'id',
            'nomor_soal',
            'pertanyaan',
            'konten_soal',
            'kunci_jawaban',
        ])->keyBy(fn(object $question) => (int) $question->id);

        if ($current->count() !== 15 || count($snapshot) !== 15) {
            return false;
        }

        foreach ($snapshot as $item) {
            if (!is_array($item) || !isset($item['id']) || !is_numeric($item['id'])) {
                return false;
            }
            $question = $current->get((int) $item['id']);
            if (
                !$question
                || (int) ($item['nomor_soal'] ?? 0) !== (int) $question->nomor_soal
                || trim((string) ($item['pertanyaan'] ?? '')) !== trim((string) $question->pertanyaan)
                || $this->normalizedQuestionJson($item['konten_soal'] ?? null)
                !== $this->normalizedQuestionJson($question->konten_soal)
                || $this->normalizedQuestionJson($item['kunci_jawaban'] ?? null)
                !== $this->normalizedQuestionJson($question->kunci_jawaban)
            ) {
                return false;
            }
        }

        return true;
    }

    /** Urutan key JSON tidak menentukan apakah isi pertanyaan berubah. */
    private function normalizedQuestionJson(mixed $value): string
    {
        $decoded = is_array($value) ? $value : $this->decodeArray($value);
        $normalize = function (array $data) use (&$normalize): array {
            if (!array_is_list($data)) {
                ksort($data);
            }
            foreach ($data as $key => $item) {
                if (is_array($item)) {
                    $data[$key] = $normalize($item);
                }
            }
            return $data;
        };

        return json_encode($normalize($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function project(object $attempt): array
    {
        $snapshot = json_decode((string) $attempt->question_snapshot, true) ?: [];
        $storedTeacherId = isset($attempt->teacher_id)
            ? (int) $attempt->teacher_id
            : null;
        $snapshotTeacherId = $this->snapshotTeacherId($snapshot);
        $bankTeacherId = $this->bankTeacherId($attempt);
        $evidenceTeacherIds = array_values(array_unique(array_filter(
            [$snapshotTeacherId, $bankTeacherId],
            fn(?int $teacherId) => $teacherId !== null
        )));
        $evidenceConflicts = count($evidenceTeacherIds) > 1;
        $evidenceTeacherId = count($evidenceTeacherIds) === 1
            ? $evidenceTeacherIds[0]
            : null;

        $teacherId = $evidenceConflicts
            || ($storedTeacherId !== null
                && $evidenceTeacherId !== null
                && $storedTeacherId !== $evidenceTeacherId)
            ? null
            : ($storedTeacherId ?? $evidenceTeacherId);
        $teacher = $teacherId === null
            ? null
            : DB::table('users')
            ->where('id', $teacherId)
            ->where('role', 'guru')
            ->first(['id', 'name']);
        if ($teacher === null) {
            $teacherId = null;
        }

        return [
            'id' => (int) $attempt->id,
            'user_id' => (int) $attempt->user_id,
            'teacher_id' => $teacherId,
            'teacher_name' => $teacher?->name,
            'attribution_status' => $teacherId === null ? 'unverified' : 'verified',
            'assessment_type' => 'evaluation',
            'chapter_id' => null,
            'evaluation_bank_id' => isset($attempt->evaluation_bank_id)
                ? (int) $attempt->evaluation_bank_id
                : null,
            'chapter_title' => null,
            'subchapter_id' => null,
            'total_questions' => count($snapshot),
            'correct_answers' => $attempt->correct_count === null
                ? null
                : (int) $attempt->correct_count,
            'score' => (float) $attempt->score,
            'completed_at' => $attempt->submitted_at,
        ];
    }

    /**
     * Snapshot ownership is reliable only for a complete, single-owner 1–15
     * question set. This reads historical evidence without changing the row.
     */
    private function snapshotTeacherId(array $snapshot): ?int
    {
        if (count($snapshot) !== 15) {
            return null;
        }

        $numbers = [];
        $teacherIds = [];
        foreach ($snapshot as $question) {
            if (
                !is_array($question)
                || !isset($question['nomor_soal'], $question['user_id'])
                || !is_numeric($question['nomor_soal'])
                || !is_numeric($question['user_id'])
            ) {
                return null;
            }

            $numbers[] = (int) $question['nomor_soal'];
            $teacherIds[] = (int) $question['user_id'];
        }

        sort($numbers);
        $teacherIds = array_values(array_unique($teacherIds));

        return $numbers === range(1, 15) && count($teacherIds) === 1
            ? $teacherIds[0]
            : null;
    }

    private function bankTeacherId(object $attempt): ?int
    {
        if (
            !isset($attempt->evaluation_bank_id)
            || !is_numeric($attempt->evaluation_bank_id)
            || !Schema::hasTable('evaluation_banks')
            || !Schema::hasColumn('evaluation_banks', 'teacher_id')
        ) {
            return null;
        }

        $teacherId = DB::table('evaluation_banks')
            ->where('id', (int) $attempt->evaluation_bank_id)
            ->value('teacher_id');

        return $teacherId === null ? null : (int) $teacherId;
    }
}
