<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EvaluationResultService;
use App\Services\StudentGradeService;
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
    | 03. DAFTAR NILAI SISWA
    |--------------------------------------------------------------------------
    */
    public function index(Request $request, StudentGradeService $grades): JsonResponse
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
        $result = $grades->overview((int) $request->user()->id, $filters);
        return response()->json([
            ...$result,
            'message' => 'Rata-rata Kuis Subbab dan Evaluasi Akhir berbobot 50:50; KKM 70.',
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | 04. DETAIL NILAI SATU SISWA
    |--------------------------------------------------------------------------
    */
    public function show(
        Request $request,
        User $student,
        EvaluationResultService $evaluationResults,
        StudentGradeService $grades
    ): JsonResponse {
        $this->ensureGuru($request);
        abort_unless(
            $student->role === 'siswa',
            404,
            'Siswa tidak ditemukan.'
        );
        $columns = $this->studentColumns();
        $teacherId = (int) $request->user()->id;
        $quizResults = collect($evaluationResults->quizResultsForTeacher($teacherId, [(int) $student->id]))
            ->map(fn(array $result) => [
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
        $evaluationAttempts = collect($evaluationResults->validCompletedForTeacher($teacherId, [(int) $student->id]))
            ->map(fn(array $attempt) => [
                'id' => $attempt['id'],
                'assessment_type' => 'evaluation',
                'chapter_id' => $attempt['chapter_id'],
                'subchapter_id' => null,
                'subchapter_name' => null,
                'chapter_name' => null,
                'teacher_name' => $attempt['teacher_name'],
                'score' => $attempt['score'],
                'total_questions' => $attempt['total_questions'],
                'correct_answers' => $attempt['correct_answers'],
                'completed_at' => $attempt['completed_at'],
                'valid_for_grading' => $attempt['valid_for_grading'],
            ]);
        // Hanya tampilkan hasil terakhir setiap Kuis Subbab dan Evaluasi.
        // Attempt lama tetap tersimpan di database untuk audit dan catatan.
        $results = $quizResults->concat($evaluationAttempts)
            ->sort(function (array $a, array $b): int {
                return [$b['completed_at'], $b['id']]
                    <=> [$a['completed_at'], $a['id']];
            })
            ->unique(function (array $result): string {
                if ($result['assessment_type'] === 'evaluation') {
                    return 'evaluation';
                }

                return 'quiz:' . ($result['subchapter_id'] ?? 'chapter:' . $result['chapter_id']);
            })
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
                'grading' => $grades->summaryForStudent(
                    $teacherId,
                    (int) $student->id
                ),
            ],
            'results' => $results,
        ]);
    }
}
