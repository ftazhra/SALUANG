<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\StudentAssessmentResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StudentGradeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 01. PEMERIKSAAN AKSES GURU
    |--------------------------------------------------------------------------
    */

    private function ensureGuru(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'guru',
            403,
            'Halaman Nilai Siswa hanya tersedia untuk Guru.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 02. PEMERIKSAAN KOLOM DATA SISWA
    |--------------------------------------------------------------------------
    */

    private function studentColumns(): array
    {
        return [
            'has_nisn' => Schema::hasColumn('users', 'nisn'),
            'has_kelas' => Schema::hasColumn('users', 'kelas'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 03. MENGAMBIL HASIL TERBARU
    |--------------------------------------------------------------------------
    */

    private function latestResult(
        Collection $results,
        string $type
    ): ?StudentAssessmentResult {
        return $results
            ->where('assessment_type', $type)
            ->sortByDesc('completed_at')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | 04. MENYUSUN NILAI SISWA
    |--------------------------------------------------------------------------
    */

    private function studentGrade(
        User $student,
        array $columns
    ): array {
        $results = $student->assessmentResults;

        $quiz = $this->latestResult(
            $results,
            'quiz'
        );

        $evaluation = $this->latestResult(
            $results,
            'evaluation'
        );

        return [
            'id' => $student->id,
            'name' => $student->name,

            'nisn' => $columns['has_nisn']
                ? $student->nisn
                : null,

            'class' => $columns['has_kelas']
                ? $student->kelas
                : null,

            'quiz_score' => $quiz !== null
                ? (float) $quiz->score
                : null,

            'evaluation_score' => $evaluation !== null
                ? (float) $evaluation->score
                : null,

            // Belum dihitung sampai aturan penilaian ditetapkan.
            'final_score' => null,
            'completion_status' => 'belum_dinilai',

            'quiz_completed_at' => $quiz?->completed_at,
            'evaluation_completed_at' =>
                $evaluation?->completed_at,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 05. DAFTAR NILAI SISWA
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $this->ensureGuru($request);

        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:200',
            ],
            'class' => [
                'nullable',
                'string',
                'max:100',
            ],
            'completion' => [
                'nullable',
                'in:tuntas,belum_tuntas,belum_dinilai',
            ],
        ]);

        $columns = $this->studentColumns();

        $query = User::query()
            ->where('role', 'siswa')
            ->with([
                'assessmentResults' => function ($builder) {
                    $builder->orderByDesc('completed_at');
                },
            ]);

        // Filter nama siswa atau NISN.
        if (!empty($filters['search'])) {
            $keyword = $filters['search'];

            $query->where(function ($builder) use (
                $keyword,
                $columns
            ) {
                $builder->where(
                    'name',
                    'like',
                    "%{$keyword}%"
                );

                if ($columns['has_nisn']) {
                    $builder->orWhere(
                        'nisn',
                        'like',
                        "%{$keyword}%"
                    );
                }
            });
        }

        // Filter kelas.
        if (!empty($filters['class'])) {
            if ($columns['has_kelas']) {
                $query->where(
                    'kelas',
                    $filters['class']
                );
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $students = $query
            ->orderBy('name')
            ->get();

        $grades = $students->map(
            fn (User $student) =>
                $this->studentGrade($student, $columns)
        );

        // Filter ketuntasan.
        if (!empty($filters['completion'])) {
            $grades = $grades->filter(
                fn (array $grade) =>
                    $grade['completion_status'] ===
                    $filters['completion']
            );
        }

        // Daftar kelas untuk dropdown.
        $classes = $columns['has_kelas']
            ? User::query()
                ->where('role', 'siswa')
                ->whereNotNull('kelas')
                ->where('kelas', '!=', '')
                ->distinct()
                ->orderBy('kelas')
                ->pluck('kelas')
            : collect();

        return response()->json([
            'data' => $grades->values(),
            'total' => $grades->count(),
            'classes' => $classes->values(),
            'grading_configured' => false,
            'message' =>
                'Nilai akhir dan ketuntasan belum dikonfigurasi.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 06. DETAIL NILAI SATU SISWA
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        User $student
    ): JsonResponse {
        $this->ensureGuru($request);

        abort_unless(
            $student->role === 'siswa',
            404,
            'Data siswa tidak ditemukan.'
        );

        $columns = $this->studentColumns();

        $student->load([
            'assessmentResults.chapter',
        ]);

        $results = $student->assessmentResults
            ->sortByDesc('completed_at')
            ->values()
            ->map(function (StudentAssessmentResult $result) {
                return [
                    'id' => $result->id,
                    'assessment_type' =>
                        $result->assessment_type,

                    'chapter_id' => $result->chapter_id,

                    'chapter_name' =>
                        $result->chapter?->title
                        ?? $result->chapter?->name
                        ?? null,

                    'score' => (float) $result->score,

                    'total_questions' =>
                        $result->total_questions,

                    'correct_answers' =>
                        $result->correct_answers,

                    'completed_at' =>
                        $result->completed_at,
                ];
            });

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->name,

                'nisn' => $columns['has_nisn']
                    ? $student->nisn
                    : null,

                'class' => $columns['has_kelas']
                    ? $student->kelas
                    : null,
            ],

            'results' => $results,
        ]);
    }
}