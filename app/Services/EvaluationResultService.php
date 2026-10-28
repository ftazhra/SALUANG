<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EvaluationResultService
{
    public function quizResultsForTeacher(int $teacherId, ?array $studentIds = null): array
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
            ->where('result.assessment_type', 'quiz')
            ->where(function ($chapters) use ($teacherId) {
                $chapters->where('direct_chapter.user_id', $teacherId)
                    ->orWhere('subchapter_chapter.user_id', $teacherId);
            })
            ->orderByDesc('result.completed_at')
            ->orderByDesc('result.id');

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
            'subchapter_chapter.id as subchapter_chapter_id',
            'subchapter_chapter.judul as subchapter_chapter_title',
            'subchapter.judul as subchapter_title',
        ])->map(fn (object $result) => [
            'id' => (int) $result->id,
            'user_id' => (int) $result->user_id,
            'assessment_type' => 'quiz',
            'chapter_id' => (int) ($result->chapter_id ?? $result->subchapter_chapter_id),
            'chapter_title' => $result->direct_chapter_title ?? $result->subchapter_chapter_title,
            'subchapter_id' => $result->subchapter_id === null ? null : (int) $result->subchapter_id,
            'subchapter_title' => $result->subchapter_title,
            'total_questions' => (int) $result->total_questions,
            'correct_answers' => (int) $result->correct_answers,
            'score' => (float) $result->score,
            'completed_at' => $result->completed_at,
        ])->all();
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
        if (!Schema::hasTable('evaluation_attempts')) {
            return [];
        }

        if ($studentIds !== null && $studentIds === []) {
            return [];
        }

        $query = DB::table('evaluation_attempts')
            ->whereNotNull('submitted_at')
            ->whereNotNull('score')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        if ($studentIds !== null) {
            $query->whereIn('user_id', $studentIds);
        }

        return $query->get()
            ->map(fn (object $attempt) => $this->project($attempt))
            ->filter(fn (array $result) => $result['teacher_id'] === $teacherId)
            ->values()
            ->all();
    }

    public function latestByStudentForTeacher(int $teacherId): array
    {
        $latest = [];

        foreach ($this->completedForTeacher($teacherId) as $result) {
            $studentId = $result['user_id'];
            if (!array_key_exists($studentId, $latest)) {
                $latest[$studentId] = $result;
            }
        }

        return $latest;
    }

    public function completedForStudent(int $studentId): array
    {
        if (!Schema::hasTable('evaluation_attempts')) {
            return [];
        }

        return DB::table('evaluation_attempts')
            ->where('user_id', $studentId)
            ->whereNotNull('submitted_at')
            ->whereNotNull('score')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (object $attempt) => $this->project($attempt))
            ->all();
    }

    private function project(object $attempt): array
    {
        $snapshot = json_decode((string) $attempt->question_snapshot, true) ?: [];
        $teacherIds = collect($snapshot)
            ->pluck('user_id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $teacherId = $teacherIds->count() === 1 ? $teacherIds->first() : null;
        $chapter = null;

        if ($teacherId !== null && Schema::hasTable('chapters')) {
            $chapterQuery = DB::table('chapters')
                ->where('user_id', $teacherId)
                ->where('nomor_bab', 6);
            if (Schema::hasColumn('chapters', 'is_published')) {
                $chapterQuery->where('is_published', true);
            }
            $chapter = $chapterQuery->first();
        }

        return [
            'id' => (int) $attempt->id,
            'user_id' => (int) $attempt->user_id,
            'teacher_id' => $teacherId,
            'assessment_type' => 'evaluation',
            'chapter_id' => $chapter ? (int) $chapter->id : null,
            'chapter_title' => $chapter->judul ?? $chapter->title ?? null,
            'subchapter_id' => null,
            'total_questions' => count($snapshot),
            'correct_answers' => (int) $attempt->correct_count,
            'score' => (float) $attempt->score,
            'completed_at' => $attempt->submitted_at,
        ];
    }
}