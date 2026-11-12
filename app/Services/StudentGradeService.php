<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentGradeService
{
    public function __construct(
        private EvaluationResultService $evaluationResults,
        private TeacherStudentAccessService $teacherStudentAccess
    ) {
    }

    public function overview(int $teacherId, array $filters = []): array
    {
        User::query()->where('role', 'guru')->findOrFail($teacherId);
        $query = User::query()->where('role', 'siswa');
        $classes = (clone $query)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->all();

        $this->applyStudentFilters($query, $filters);

        $students = $query->orderBy('name')->get();
        $summaries = $this->summariesForStudents(
            $teacherId,
            $students->modelKeys()
        );

        $data = $students->map(function (User $student) use ($summaries) {
            return array_merge([
                'id' => $student->id,
                'name' => $student->name,
                'nisn' => $student->nisn,
                'class' => $student->kelas,
            ], $summaries[$student->id] ?? $this->emptySummary());
        });

        if (!empty($filters['completion'])) {
            $data = $data->where(
                'completion_status',
                $filters['completion']
            )->values();
        }

        return [
            'data' => $data,
            'total' => $data->count(),
            'classes' => $classes,
            'grading_configured' => true,
        ];
    }

    public function summaryForStudent(int $teacherId, int $studentId): array
    {
        return $this->summariesForStudents($teacherId, [$studentId])[$studentId]
            ?? $this->emptySummary();
    }

    public function summariesForAssignedTeachers(User $student): array
    {
        if ($student->role !== 'siswa') {
            return [];
        }

        $teacherIds = $this->teacherStudentAccess->teacherIdsForStudent($student);
        foreach ($this->evaluationResults->quizResultsForStudent((int) $student->id) as $result) {
            if ($result['teacher_id'] !== null) {
                $teacherIds[] = $result['teacher_id'];
            }
        }
        foreach ($this->evaluationResults->validCompletedForStudent((int) $student->id) as $result) {
            if ($result['teacher_id'] !== null) {
                $teacherIds[] = $result['teacher_id'];
            }
        }
        $teacherIds = array_values(array_unique($teacherIds));

        return User::query()
            ->where('role', 'guru')
            ->whereIn('id', $teacherIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (object $teacher) => [
                'teacher_id' => (int) $teacher->id,
                'teacher_name' => $teacher->name,
                ...$this->summaryForStudent((int) $teacher->id, (int) $student->id),
            ])
            ->all();
    }

    public function studentIsAssignedToTeacher(int $teacherId, User $student): bool
    {
        $teacher = User::query()->where('role', 'guru')->find($teacherId);

        return $teacher !== null
            && $this->teacherStudentAccess->isAssignedToTeacher($student, $teacher);
    }

    private function applyStudentFilters(
        Builder $query,
        array $filters
    ): void {
        if (!empty($filters['search'])) {
            $keyword = $filters['search'];
            $hasNisn = Schema::hasColumn('users', 'nisn');

            $query->where(function (Builder $builder) use ($keyword, $hasNisn) {
                $builder->where('name', 'like', "%{$keyword}%");
                if ($hasNisn) {
                    $builder->orWhere('nisn', 'like', "%{$keyword}%");
                }
            });
        }

        if (!empty($filters['class'])) {
            $normalizedFilter = mb_strtolower(trim($filters['class']));
            $query->whereRaw('LOWER(TRIM(kelas)) = ?', [$normalizedFilter]);
        }
    }

    private function summariesForStudents(
        int $teacherId,
        array $studentIds
    ): array {
        if ($studentIds === []) {
            return [];
        }

        $requiredSubchapterIds = $this->requiredQuizSubchapterIds($teacherId);
        $requiredSet = array_fill_keys($requiredSubchapterIds, true);
        $quizResults = [];

        foreach ($this->evaluationResults->quizResultsForTeacher($teacherId, $studentIds) as $result) {
            $subchapterId = $result['subchapter_id'];
            if ($subchapterId === null || !isset($requiredSet[$subchapterId])) {
                continue;
            }

            $studentId = $result['user_id'];
            $existing = $quizResults[$studentId][$subchapterId] ?? null;
            if (
                $existing === null
                || [$result['completed_at'], $result['id']] > [$existing['completed_at'], $existing['id']]
            ) {
                $quizResults[$studentId][$subchapterId] = $result;
            }
        }

        $evaluationResults = $this->evaluationResults->latestByStudentForTeacher(
            $teacherId,
            $studentIds
        );
        $summaries = [];

        foreach ($studentIds as $studentId) {
            $completedQuizzes = $quizResults[$studentId] ?? [];
            $quizAverage = $completedQuizzes
                ? round(
                    array_sum(array_column($completedQuizzes, 'score'))
                        / count($completedQuizzes),
                    2
                )
                : null;
            $evaluation = $evaluationResults[$studentId] ?? null;
            $allRequiredQuizzesComplete = count($requiredSubchapterIds) > 0
                && count($completedQuizzes) === count($requiredSubchapterIds);
            $complete = $allRequiredQuizzesComplete && $evaluation !== null;
            $finalScore = $complete
                ? round(($quizAverage + (float) $evaluation['score']) / 2, 2)
                : null;

            $summaries[$studentId] = [
                'quiz_score' => $quizAverage,
                'evaluation_score' => $evaluation === null
                    ? null
                    : (float) $evaluation['score'],
                'final_score' => $finalScore,
                'completion_status' => !$complete
                    ? 'belum_dinilai'
                    : ($finalScore >= 70 ? 'tuntas' : 'belum_tuntas'),
                'quiz_completed_count' => count($completedQuizzes),
                'quiz_required_count' => count($requiredSubchapterIds),
                'evaluation_completed' => $evaluation !== null,
            ];
        }

        return $summaries;
    }

    private function requiredQuizSubchapterIds(int $teacherId): array
    {
        if (
            !Schema::hasTable('chapters')
            || !Schema::hasTable('subchapters')
            || !Schema::hasTable('quiz_questions')
        ) {
            return [];
        }

        $query = DB::table('subchapters as subchapter')
            ->join('chapters as chapter', 'chapter.id', '=', 'subchapter.chapter_id')
            ->where('chapter.user_id', $teacherId)
            ->where('chapter.is_published', true)
            ->where('subchapter.is_published', true)
            ->whereExists(function ($questions) {
                $questions->selectRaw('1')
                    ->from('quiz_questions')
                    ->whereColumn('quiz_questions.subchapter_id', 'subchapter.id')
                    ->where('quiz_questions.is_published', true);

                if (Schema::hasColumn('quiz_questions', 'activity_type')) {
                    $questions->where('quiz_questions.activity_type', 'multiple_choice');
                }
            });

        return $query->orderBy('subchapter.id')
            ->pluck('subchapter.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function emptySummary(): array
    {
        return [
            'quiz_score' => null,
            'evaluation_score' => null,
            'final_score' => null,
            'completion_status' => 'belum_dinilai',
            'quiz_completed_count' => 0,
            'quiz_required_count' => 0,
            'evaluation_completed' => false,
        ];
    }
}
