<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ParentDashboardService
{
    public function __construct(
        private EvaluationResultService $evaluationResults,
        private StudentGradeService $studentGrades
    ) {
    }

    private function has(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    public function dashboard(User $parent, ?int $selectedChildId = null): array
    {
        abort_unless($parent->role === 'orang_tua', 403);

        if (!Schema::hasTable('student_guardians')) {
            return $this->emptyDashboard($parent);
        }

        if (!$this->has('student_guardians', 'status') || !$this->has('student_guardians', 'student_id') || !$this->has('student_guardians', 'guardian_id')) return $this->emptyDashboard($parent);

        $relation = DB::table('student_guardians')
            ->join('users as siswa', 'siswa.id', '=', 'student_guardians.student_id')
            ->where('student_guardians.guardian_id', $parent->id)
            ->where('siswa.role', 'siswa')
            ->whereNull('siswa.deleted_at')
            ->when(
                $selectedChildId !== null,
                fn ($query) => $query->where('student_guardians.student_id', $selectedChildId)
            )
            ->orderByRaw("CASE WHEN student_guardians.status = 'approved' THEN 0 ELSE 1 END")
            ->select('student_guardians.student_id', 'student_guardians.status')
            ->first();

        $approved = $relation
            && $relation->status === 'approved'
            && ($selectedChildId === null || (int) $relation->student_id === $selectedChildId);
        $child = $approved ? User::where('role', 'siswa')->find($relation->student_id) : null;
        $chapters = $this->chaptersForStudent($child ? (int) $child->id : null);
        $total = array_sum(array_column($chapters, 'total'));
        $completed = array_sum(array_column($chapters, 'finished'));
        $completedMaterials = array_sum(array_column($chapters, 'material_finished'));
        $completedQuizzes = array_sum(array_column($chapters, 'quiz_finished'));
        $finalGrades = $child
            ? $this->studentGrades->summariesForAssignedTeachers($child)
            : [];
        $finalScores = collect($finalGrades)
            ->pluck('final_score')
            ->filter(fn ($score) => is_numeric($score))
            ->map(fn ($score) => (float) $score)
            ->all();

        return [
            'parent' => $parent,
            'child' => $child,
            'relationStatus' => $relation?->status,
            'stats' => [
                'materials' => $completedMaterials,
                'quizzes' => $completedQuizzes,
                'average' => $finalScores
                    ? round(array_sum($finalScores) / count($finalScores), 1)
                    : null,
                'completion' => $total ? (int) round($completed * 100 / $total) : 0,
                'completed_activities' => $completed,
                'total_activities' => $total,
            ],
            'chapters' => $chapters,
            'activities' => $child ? $this->activities((int) $child->id) : [],
            'activityHistory' => $child ? $this->activityHistory((int) $child->id, $chapters) : [],
            'teacherNotes' => $child
                ? $this->teacherNotes((int) $child->id)
                : [
                    'assessments' => [],
                    'general' => [],
                    'unavailable' => [],
                ],
            'learningResults' => $child ? $this->learningResults((int) $child->id, $chapters) : [],
            'finalGrades' => $finalGrades,
        ];
    }


    private function emptyDashboard(User $parent): array
    {
        return [
            'parent' => $parent,
            'child' => null,
            'relationStatus' => null,
            'stats' => [
                'materials' => 0,
                'quizzes' => 0,
                'average' => null,
                'completion' => 0,
                'completed_activities' => 0,
                'total_activities' => 0,
            ],
            'chapters' => $this->chaptersForStudent(null),
            'activities' => [],
            'activityHistory' => [],
            'teacherNotes' => [
                'assessments' => [],
                'general' => [],
                'unavailable' => [],
            ],
            'learningResults' => [],
            'finalGrades' => [],
        ];
    }

    private function chaptersForStudent(?int $studentId): array
    {
        if (!Schema::hasTable('chapters') || !Schema::hasTable('subchapters')) return [];

        $chapters = DB::table('chapters as chapter')
            ->leftJoin('users as teacher', function ($join) {
                $join->on('teacher.id', '=', 'chapter.user_id')
                    ->where('teacher.role', '=', 'guru');
            })
            ->select('chapter.*', 'teacher.id as teacher_id', 'teacher.name as teacher_name');
        if ($this->has('chapters', 'is_published')) $chapters->where('is_published', true);
        $chapters->orderBy('chapter.id');

        $result = [];
        foreach ($chapters->get() as $chapter) {
            $subQuery = DB::table('subchapters')->where('chapter_id', $chapter->id);
            if ($this->has('subchapters', 'is_published')) $subQuery->where('is_published', true);
            $subQuery->orderBy($this->has('subchapters', 'nomor_subbab') ? 'nomor_subbab' : 'id');

            $subchapterRows = $subQuery->get();
            $quizCompletedSubchapters = $studentId !== null
                ? $this->completedQuizSubchapterIds(
                    $studentId,
                    $subchapterRows->pluck('id')->map(fn ($id) => (int) $id)->all()
                )
                : [];
            $subchapters = [];
            foreach ($subchapterRows as $subchapter) {
                $subchapterId = (int) $subchapter->id;
                $quizDone = in_array($subchapterId, $quizCompletedSubchapters, true);
                $materialDone = $studentId !== null
                    ? $this->isSubchapterDone($studentId, $subchapterId)
                    : false;
                $subchapters[] = [
                    'id' => $subchapterId,
                    'number' => isset($subchapter->nomor_subbab) ? (int) $subchapter->nomor_subbab : null,
                    'title' => $subchapter->judul ?? $subchapter->title ?? $subchapter->name ?? ('Subbab ' . $subchapter->id),
                    'material_recorded_done' => $materialDone,
                    'done' => $materialDone || $quizDone,
                    'quiz_done' => $quizDone,
                ];
            }
            if (!$subchapters) continue;
            $materialFinished = count(array_filter($subchapters, fn ($subchapter) => $subchapter['done']));
            $quizFinished = count(array_filter($subchapters, fn ($subchapter) => $subchapter['quiz_done']));
            $finished = $materialFinished + $quizFinished;
            $total = count($subchapters) * 2;
            $result[] = [
                'id' => (int) $chapter->id,
                'teacher_id' => $chapter->teacher_id === null ? null : (int) $chapter->teacher_id,
                'teacher_name' => $chapter->teacher_name,
                'number' => isset($chapter->nomor_bab) ? (int) $chapter->nomor_bab : (isset($chapter->chapter_number) ? (int) $chapter->chapter_number : null),
                'title' => $chapter->judul ?? $chapter->title ?? $chapter->name ?? 'Bab VI',
                'subchapters' => $subchapters,
                'finished' => $finished,
                'total' => $total,
                'material_finished' => $materialFinished,
                'quiz_finished' => $quizFinished,
                'percentage' => (int) round($finished * 100 / $total),
            ];
        }
        return $result;
    }

    private function isSubchapterDone(int $studentId, int $subchapterId): bool
    {
        $table = 'student_subchapter_progress';
        if (!Schema::hasTable($table) || !$this->has($table, 'user_id') || !$this->has($table, 'subchapter_id')) return false;
        $row = DB::table($table)->where('user_id', $studentId)->where('subchapter_id', $subchapterId)->first();
        return $row ? $this->isProgressRowCompleted($row) : false;
    }

    private function isProgressRowCompleted(object $row): bool
    {
        if (property_exists($row, 'is_completed') && $row->is_completed !== null) return (bool) $row->is_completed;
        if (property_exists($row, 'completed') && $row->completed !== null) return (bool) $row->completed;
        if (property_exists($row, 'completed_at') && $row->completed_at !== null) return true;
        if (property_exists($row, 'status')) return in_array(strtolower((string) $row->status), ['completed', 'selesai', 'done'], true);
        return false;
    }

    private function completedQuizSubchapterIds(int $studentId, array $subchapterIds): array
    {
        $table = 'student_assessment_results';
        foreach ([
            'user_id',
            'assessment_type',
            'subchapter_id',
            'score',
            'completed_at',
            'total_questions',
            'correct_answers',
        ] as $column) {
            if (!$this->has($table, $column)) {
                return [];
            }
        }

        if ($subchapterIds === []) {
            return [];
        }

        return DB::table($table)
            ->where('user_id', $studentId)
            ->where('assessment_type', 'quiz')
            ->whereIn('subchapter_id', $subchapterIds)
            ->whereNotNull('score')
            ->whereBetween('score', [0, 100])
            ->whereNotNull('completed_at')
            ->where('total_questions', '>', 0)
            ->where('correct_answers', '>=', 0)
            ->whereColumn('correct_answers', '<=', 'total_questions')
            ->distinct()
            ->pluck('subchapter_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function activities(int $studentId): array
    {
        $table = 'student_subchapter_progress';
        if (!Schema::hasTable($table) || !$this->has($table, 'user_id')) return [];
        $query = DB::table($table)->where('user_id', $studentId);
        if ($this->has($table, 'updated_at')) $query->orderByDesc('updated_at');
        return $query->limit(5)->get()->map(fn($row) => [
            'title' => 'Aktivitas belajar pada materi',
            'date' => $row->updated_at ?? $row->created_at ?? null,
        ])->all();
    }

    private function activityHistory(int $studentId, array $chapters): array
    {
        $events = [];
        $chapterMap = [];
        $chapterNumberMap = [];
        $subchapterMap = [];
        $forumSubchapterMap = [];
        foreach ($chapters as $chapter) {
            $chapterId = (int) $chapter['id'];
            $chapterMap[$chapterId] = $chapter['title'];
            if (isset($chapter['number'])) {
                $chapterNumberMap[(int) $chapter['number']] = $chapterId;
            }
            foreach ($chapter['subchapters'] as $subchapter) {
                $subchapterMap[(int) $subchapter['id']] = [
                    'chapter_id' => $chapterId,
                    'chapter_title' => $chapter['title'],
                    'subchapter_title' => $subchapter['title'],
                    'teacher_id' => $chapter['teacher_id'] ?? null,
                    'teacher_name' => $chapter['teacher_name'] ?? null,
                ];
                if (isset($chapter['number'], $subchapter['number'])) {
                    $forumSubchapterMap[(int) $chapter['number'] . '.' . (int) $subchapter['number']] = (int) $subchapter['id'];
                }
            }
        }
        $forumContext = function (?string $chapterCode, ?string $subchapterCode) use ($chapterMap, $chapterNumberMap, $forumSubchapterMap, $subchapterMap): array {
            $chapterId = null;
            $chapterNumber = null;
            if (is_string($chapterCode) && preg_match('/^bab-(\d+)$/i', $chapterCode, $chapterMatch) === 1) {
                $chapterNumber = (int) $chapterMatch[1];
                $chapterId = $chapterNumberMap[$chapterNumber] ?? null;
            }

            $subchapterId = null;
            if (
                $chapterId !== null
                && $chapterNumber !== null
                && is_string($subchapterCode)
                && preg_match('/^(\d+)\.(\d+)$/', $subchapterCode, $subchapterMatch) === 1
                && (int) $subchapterMatch[1] === $chapterNumber
            ) {
                $candidateId = $forumSubchapterMap[$chapterNumber . '.' . (int) $subchapterMatch[2]] ?? null;
                if ($candidateId !== null && ($subchapterMap[$candidateId]['chapter_id'] ?? null) === $chapterId) {
                    $subchapterId = $candidateId;
                }
            }

            return [
                'chapter_id' => $chapterId,
                'chapter_title' => $chapterId === null ? null : ($chapterMap[$chapterId] ?? null),
                'subchapter_id' => $subchapterId,
                'subchapter_title' => $subchapterId === null ? null : ($subchapterMap[$subchapterId]['subchapter_title'] ?? null),
            ];
        };

        if (
            Schema::hasTable('student_subchapter_progress') &&
            $this->has('student_subchapter_progress', 'user_id') &&
            $this->has('student_subchapter_progress', 'subchapter_id')
        ) {
            foreach (
                DB::table('student_subchapter_progress')->where('user_id', $studentId)
                    ->whereIn('subchapter_id', array_keys($subchapterMap))->get() as $row
            ) {
                $id = (int) $row->subchapter_id;
                $meta = $subchapterMap[$id];
                $done = $this->isProgressRowCompleted($row);
                $date = $row->completed_at ?? $row->updated_at ?? $row->created_at ?? null;
                $events[] = array_merge($meta, [
                    'id' => (int) $row->id,
                    'subchapter_id' => $id,
                    'type' => 'materi',
                    'title' => $done ? 'Menyelesaikan materi' : 'Membuka materi',
                    'detail' => $done ? 'Materi selesai' : 'Materi dipelajari',
                    'status' => $done ? 'completed' : 'in_progress',
                    'date' => $date,
                ]);
            }
        }

        // Kuis hanya ditampilkan bila relasi soal -> subbab dapat dipastikan.
        if (
            Schema::hasTable('student_quiz_answers') && Schema::hasTable('quiz_questions') &&
            $this->has('student_quiz_answers', 'user_id') && $this->has('quiz_questions', 'subchapter_id')
        ) {
            $answerKey = $this->has('student_quiz_answers', 'quiz_question_id') ? 'quiz_question_id' : ($this->has('student_quiz_answers', 'question_id') ? 'question_id' : null);
            if ($answerKey) {
                $query = DB::table('student_quiz_answers as a')
                    ->join('quiz_questions as q', 'q.id', '=', 'a.' . $answerKey)
                    ->where('a.user_id', $studentId)
                    ->whereIn('q.subchapter_id', array_keys($subchapterMap))
                    ->select('a.*', 'q.subchapter_id as linked_subchapter_id');
                foreach ($query->get() as $row) {
                    $id = (int) $row->linked_subchapter_id;
                    $events[] = array_merge($subchapterMap[$id], [
                        'id' => (int) $row->id,
                        'subchapter_id' => $id,
                        'type' => 'kuis',
                        'title' => 'Menjawab soal kuis',
                        'detail' => (bool) $row->is_correct ? 'Jawaban benar' : 'Jawaban perlu dipelajari',
                        'status' => (bool) $row->is_correct ? 'correct' : 'incorrect',
                        'date' => $row->updated_at ?? $row->created_at ?? null,
                    ]);
                }
            }
        }

        foreach ($this->evaluationResults->validCompletedForStudent($studentId) as $attempt) {
            $attribution = $attempt['teacher_id'] === null
                ? 'Atribusi Guru belum terverifikasi'
                : 'Guru ' . $attempt['teacher_name'];
            $events[] = [
                'id' => $attempt['id'],
                'teacher_id' => $attempt['teacher_id'],
                'teacher_name' => $attempt['teacher_name'],
                'attribution_status' => $attempt['attribution_status'],
                'chapter_id' => null,
                'chapter_title' => null,
                'subchapter_id' => null,
                'subchapter_title' => null,
                'type' => 'evaluasi',
                'title' => 'Menyelesaikan evaluasi',
                'detail' => $attribution . ' · Nilai '
                    . rtrim(rtrim(number_format($attempt['score'], 2, '.', ''), '0'), '.')
                    . ' · ' . $attempt['correct_answers'] . ' dari ' . $attempt['total_questions'] . ' benar',
                'status' => 'completed',
                'score' => $attempt['score'],
                'date' => $attempt['completed_at'],
            ];
        }

        if (
            Schema::hasTable('discussions')
            && $this->has('discussions', 'user_id')
            && $this->has('discussions', 'chapter')
            && $this->has('discussions', 'subchapter')
        ) {
            foreach (DB::table('discussions')->where('user_id', $studentId)->orderBy('created_at')->get() as $row) {
                $events[] = array_merge($forumContext($row->chapter, $row->subchapter), [
                    'id' => (int) $row->id,
                    'type' => 'diskusi',
                    'title' => 'Membuat diskusi',
                    'detail' => (string) $row->title,
                    'status' => 'created',
                    'date' => $row->created_at,
                ]);
            }
        }

        if (
            Schema::hasTable('discussion_replies')
            && Schema::hasTable('discussions')
            && $this->has('discussion_replies', 'user_id')
            && $this->has('discussion_replies', 'discussion_id')
        ) {
            foreach (
                DB::table('discussion_replies as reply')
                    ->join('discussions as discussion', 'discussion.id', '=', 'reply.discussion_id')
                    ->where('reply.user_id', $studentId)
                    ->orderBy('reply.created_at')
                    ->get([
                        'reply.id',
                        'reply.created_at',
                        'discussion.chapter',
                        'discussion.subchapter',
                        'discussion.title',
                    ]) as $row
            ) {
                $events[] = array_merge($forumContext($row->chapter, $row->subchapter), [
                    'id' => (int) $row->id,
                    'type' => 'diskusi',
                    'title' => 'Menanggapi diskusi',
                    'detail' => (string) $row->title,
                    'status' => 'replied',
                    'date' => $row->created_at,
                ]);
            }
        }

        usort($events, fn($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
        return $events;
    }

    private function learningResults(int $studentId, array $chapters): array
    {
        $table = 'student_assessment_results';

        $rows = [];
        if (Schema::hasTable($table) && $this->has($table, 'user_id')) {
            $scoreColumn = null;
            foreach (['score', 'nilai', 'final_score', 'percentage'] as $column) {
                if ($this->has($table, $column)) {
                    $scoreColumn = $column;
                    break;
                }
            }
            if ($scoreColumn) {
                $rows = $this->assessmentLearningResults($studentId, $chapters, $table, $scoreColumn);
            }
        }

        $student = User::query()->where('role', 'siswa')->findOrFail($studentId);
        $quizResults = $this->evaluationResults->quizResultsForStudent($studentId);
        foreach ($quizResults as $result) {
            $existingKey = null;
            foreach ($rows as $index => $row) {
                if ($row['type'] === 'kuis' && (int) $row['id'] === $result['id']) {
                    $existingKey = $index;
                    break;
                }
            }

            $quizRow = [
                'id' => $result['id'],
                'name' => 'Kuis Subbab',
                'type' => 'kuis',
                'teacher_id' => $result['teacher_id'],
                'teacher_name' => $result['teacher_name'],
                'chapter_id' => $result['chapter_id'],
                'chapter_title' => $result['chapter_title'],
                'subchapter_id' => $result['subchapter_id'],
                'subchapter_title' => $result['subchapter_title'],
                'score' => $result['score'],
                'status' => $result['score'] >= 70 ? 'Tuntas' : 'Belum tuntas',
                'date' => $result['completed_at'],
            ];
            if ($existingKey === null) {
                $rows[] = $quizRow;
            } else {
                $rows[$existingKey] = array_merge($rows[$existingKey], $quizRow);
            }
        }

        foreach ($this->evaluationResults->validCompletedForStudent($studentId) as $attempt) {
            $verified = $attempt['teacher_id'] !== null;
            $rows[] = [
                'id' => $attempt['id'],
                'name' => $verified
                    ? 'Evaluasi Akhir · ' . $attempt['teacher_name']
                    : 'Evaluasi Akhir · atribusi Guru belum terverifikasi',
                'type' => 'evaluasi',
                'teacher_id' => $attempt['teacher_id'],
                'teacher_name' => $attempt['teacher_name'],
                'attribution_status' => $attempt['attribution_status'],
                'chapter_id' => null,
                'chapter_title' => null,
                'subchapter_id' => null,
                'subchapter_title' => null,
                'score' => $attempt['score'],
                'status' => $attempt['score'] >= 70 ? 'Tuntas' : 'Belum tuntas',
                'date' => $attempt['completed_at'],
            ];
        }

        usort($rows, fn(array $a, array $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
        return $rows;
    }

    private function assessmentLearningResults(int $studentId, array $chapters, string $table, string $scoreColumn): array
    {
        if (!$this->has($table, 'chapter_id') && !$this->has($table, 'subchapter_id')) return [];

        $chapterMap = [];
        $subchapterMap = [];
        foreach ($chapters as $chapter) {
            $chapterMap[(int) $chapter['id']] = $chapter;
            foreach (($chapter['subchapters'] ?? []) as $subchapter) {
                $subchapterMap[(int) $subchapter['id']] = [
                    'chapter_id' => (int) $chapter['id'],
                    'chapter_title' => $chapter['title'],
                    'subchapter_title' => $subchapter['title'],
                ];
            }
        }
        if (!$chapterMap) return [];

        $hasSubchapter = $this->has($table, 'subchapter_id');
        $hasChapter = $this->has($table, 'chapter_id');
        $hasType = $this->has($table, 'assessment_type') || $this->has($table, 'type') || $this->has($table, 'jenis');

        // Hasil yang tidak memiliki hubungan Bab VI yang dapat diverifikasi tidak ditampilkan.
        if (!$hasSubchapter && !$hasChapter) return [];

        $query = DB::table($table)->where('user_id', $studentId)->whereNotNull($scoreColumn);
        $query->whereBetween($scoreColumn, [0, 100]);
        if ($this->has($table, 'completed_at')) {
            $query->whereNotNull('completed_at');
        }
        if ($this->has($table, 'total_questions')) {
            $query->where('total_questions', '>', 0);
        }
        if (
            $this->has($table, 'correct_answers')
            && $this->has($table, 'total_questions')
        ) {
            $query->where('correct_answers', '>=', 0)
                ->whereColumn('correct_answers', '<=', 'total_questions');
        }
        if ($this->has($table, 'assessment_type')) {
            $query->where('assessment_type', '!=', 'evaluation');
        }
        if ($hasSubchapter && $hasChapter) {
            $query->where(function ($q) use ($subchapterMap, $chapterMap) {
                $q->whereIn('subchapter_id', array_keys($subchapterMap))
                    ->orWhereIn('chapter_id', array_keys($chapterMap));
            });
        } elseif ($hasSubchapter) {
            $query->whereIn('subchapter_id', array_keys($subchapterMap));
        } else {
            $query->whereIn('chapter_id', array_keys($chapterMap));
        }
        if ($this->has($table, 'created_at')) $query->orderByDesc('created_at');
        $rows = [];
        foreach ($query->limit(200)->get() as $row) {
            $subchapterId = $hasSubchapter && $row->subchapter_id !== null ? (int) $row->subchapter_id : null;
            $chapterId = $hasChapter && $row->chapter_id !== null ? (int) $row->chapter_id : null;
            if ($subchapterId !== null && isset($subchapterMap[$subchapterId])) {
                $chapterId = $subchapterMap[$subchapterId]['chapter_id'];
            }
            if (!$chapterId || !isset($chapterMap[$chapterId])) continue;
            if ($subchapterId !== null && !isset($subchapterMap[$subchapterId])) $subchapterId = null;

            $rawType = strtolower((string) ($row->assessment_type ?? $row->type ?? $row->jenis ?? ''));
            if (str_contains($rawType, 'evaluasi') || str_contains($rawType, 'evaluation') || str_contains($rawType, 'exam')) {
                continue;
            } elseif (str_contains($rawType, 'kuis') || str_contains($rawType, 'quiz')) {
                $type = 'kuis';
            } else {
                // Tanpa tipe eksplisit, jangan memberi label kuis/evaluasi yang tidak terbukti.
                $type = 'lainnya';
            }
            $score = (float) $row->{$scoreColumn};
            $rows[] = [
                'id' => (int) $row->id,
                'name' => $type === 'evaluasi' ? 'Evaluasi Pembelajaran' : ($type === 'kuis' ? 'Kuis Subbab' : 'Hasil Penilaian'),
                'type' => $type,
                'chapter_id' => $chapterId,
                'chapter_title' => $chapterMap[$chapterId]['title'],
                'subchapter_id' => $subchapterId,
                'subchapter_title' => $subchapterId ? $subchapterMap[$subchapterId]['subchapter_title'] : null,
                'score' => $score,
                'status' => $score >= 70 ? 'Tuntas' : 'Belum tuntas',
                'date' => $row->created_at ?? $row->updated_at ?? null,
            ];
        }
        return $rows;
    }



    private function teacherNotes(int $studentId): array
    {
        $table = 'student_teacher_note_messages';
        $empty = [
            'assessments' => [],
            'general' => [],
            'unavailable' => [],
        ];

        if (!Schema::hasTable($table)) {
            return $empty;
        }

        $hasAssessmentContext = $this->has($table, 'assessment_type')
            && $this->has($table, 'assessment_result_id');
        $messages = DB::table($table)
            ->where('student_id', $studentId)
            ->where('audience', 'orang_tua')
            ->whereNotNull('published_at')
            ->where('note', '!=', '')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        if ($messages->isEmpty()) {
            return $empty;
        }

        $resultsByKey = [];
        if ($hasAssessmentContext) {
            foreach ($this->evaluationResults->quizResultsForStudent($studentId) as $result) {
                $resultsByKey['quiz:' . $result['id']] = $result;
            }

            foreach ($this->evaluationResults->validCompletedForStudent($studentId) as $result) {
                if ($result['teacher_id'] !== null) {
                    $resultsByKey['evaluation:' . $result['id']] = $result;
                }
            }
        }

        $assessments = [];
        $general = [];
        $unavailable = [];

        foreach ($messages as $message) {
            $body = trim((string) $message->note);
            if ($body === '') {
                continue;
            }

            $assessmentType = $hasAssessmentContext
                ? ($message->assessment_type ?? null)
                : null;
            $assessmentResultId = $hasAssessmentContext
                ? ($message->assessment_result_id ?? null)
                : null;
            $note = [
                'id' => (int) $message->id,
                'body' => $body,
                'date' => $message->published_at,
            ];

            if ($assessmentType === null && $assessmentResultId === null) {
                $general[] = $note;
                continue;
            }

            $resultKey = is_numeric($assessmentResultId)
                ? $assessmentType . ':' . (int) $assessmentResultId
                : null;
            $result = $resultKey === null ? null : ($resultsByKey[$resultKey] ?? null);

            if (
                !in_array($assessmentType, ['quiz', 'evaluation'], true)
                || $result === null
                || (
                    (int) ($result['teacher_id'] ?? 0) !== (int) $message->teacher_id
                    || (
                        $assessmentType === 'evaluation'
                        && empty($result['teacher_name'])
                    )
                    || (
                        $assessmentType === 'quiz'
                        && empty($result['chapter_title'])
                    )
                )
            ) {
                $unavailable[] = $note;
                continue;
            }

            if (!isset($assessments[$resultKey])) {
                $assessments[$resultKey] = [
                    'assessment_type' => $assessmentType,
                    'type_label' => $assessmentType === 'quiz' ? 'Kuis Subbab' : 'Evaluasi Akhir',
                    'teacher_name' => $assessmentType === 'evaluation'
                        ? $result['teacher_name']
                        : null,
                    'chapter_title' => $result['chapter_title'],
                    'subchapter_title' => $result['subchapter_title'] ?? null,
                    'score' => $result['score'],
                    'total_questions' => $result['total_questions'],
                    'correct_answers' => $result['correct_answers'],
                    'completed_at' => $result['completed_at'],
                    'notes' => [],
                ];
            }

            $assessments[$resultKey]['notes'][] = $note;
        }

        $assessments = array_values($assessments);
        usort($assessments, function (array $first, array $second): int {
            return strcmp(
                (string) ($second['notes'][0]['date'] ?? ''),
                (string) ($first['notes'][0]['date'] ?? '')
            );
        });

        return [
            'assessments' => $assessments,
            'general' => $general,
            'unavailable' => $unavailable,
        ];
    }
}
