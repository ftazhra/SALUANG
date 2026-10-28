<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EvaluationResultService;
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
    /*
    |--------------------------------------------------------------------------
    | 04. MENYUSUN NILAI SISWA
    |--------------------------------------------------------------------------
    */
    private function studentGrade(
        User $student,
        array $columns,
        ?array $quizResult,
        ?array $evaluationAttempt
    ): array {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'nisn' => $columns['has_nisn']
                ? $student->nisn
                : null,
            'class' => $columns['has_kelas']
                ? $student->kelas
                : null,
            'quiz_score' => $quizResult !== null
                ? (float) $quizResult['score']
                : null,
            'evaluation_score' => $evaluationAttempt !== null
                ? (float) $evaluationAttempt['score']
                : null,
            // Belum dihitung sampai aturan penilaian ditetapkan.
            'final_score' => null,
            'completion_status' => 'belum_dinilai',
            'quiz_completed_at' => $quizResult['completed_at'] ?? null,
            'evaluation_completed_at' => $evaluationAttempt['completed_at'] ?? null,
        ];
    }
    /*
    |--------------------------------------------------------------------------
    | 05. DAFTAR NILAI SISWA
    |--------------------------------------------------------------------------
    */
    public function index(Request $request, EvaluationResultService $evaluationResults): JsonResponse
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
        $teacherId = (int) $request->user()->id;
        $quizResults = $evaluationResults->latestQuizByStudentForTeacher($teacherId);
        $evaluationAttempts = $evaluationResults->latestByStudentForTeacher($teacherId);
        $authorizedStudentIds = array_values(array_unique([
            ...array_keys($quizResults),
            ...array_keys($evaluationAttempts),
        ]));
        $query = User::query()
            ->where('role', 'siswa')
            ->whereIn('id', $authorizedStudentIds);
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
        $grades = collect();
        foreach ($students as $student) {
            if (!$student instanceof User) continue;
            $grades->push($this->studentGrade(
                $student,
                $columns,
                $quizResults[$student->id] ?? null,
                $evaluationAttempts[$student->id] ?? null
            ));
        }
        // Filter ketuntasan.
        if (!empty($filters['completion'])) {
            $grades = $grades->filter(
                fn (array $grade) =>
                    $grade['completion_status'] ===
                    $filters['completion']
            );
        }
        // Daftar kelas untuk dropdown.
        $classes = $grades->pluck('class')->filter()->unique()->sort()->values();
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
        User $student,
        EvaluationResultService $evaluationResults
    ): JsonResponse {
        $this->ensureGuru($request);
        abort_unless(
            $student->role === 'siswa',
            404,
            'Data siswa tidak ditemukan.'
        );
        $columns = $this->studentColumns();
        $teacherId = (int) $request->user()->id;
        $quizResults = collect($evaluationResults->quizResultsForTeacher($teacherId, [(int) $student->id]))
            ->map(fn (array $result) => [
                'id' => $result['id'],
                'assessment_type' => 'quiz',
                'chapter_id' => $result['chapter_id'],
                'subchapter_id' => $result['subchapter_id'],
                'subchapter_name' => $result['subchapter_title'],
                'chapter_name' => $result['chapter_title'],
                'score' => $result['score'],
                'total_questions' => $result['total_questions'],
                'correct_answers' => $result['correct_answers'],
                'completed_at' => $result['completed_at'],
            ]);
        $evaluationAttempts = collect($evaluationResults->completedForTeacher($teacherId, [(int) $student->id]))
            ->map(fn (array $attempt) => [
                'id' => $attempt['id'],
                'assessment_type' => 'evaluation',
                'chapter_id' => $attempt['chapter_id'],
                'subchapter_id' => null,
                'subchapter_name' => null,
                'chapter_name' => $attempt['chapter_title'],
                'score' => $attempt['score'],
                'total_questions' => $attempt['total_questions'],
                'correct_answers' => $attempt['correct_answers'],
                'completed_at' => $attempt['completed_at'],
            ]);
        abort_if($quizResults->isEmpty() && $evaluationAttempts->isEmpty(), 404, 'Hasil siswa tidak ditemukan untuk materi yang dikelola Guru ini.');
        $results = $quizResults->concat($evaluationAttempts)
            ->sortByDesc('completed_at')
            ->values();
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