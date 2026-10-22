<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | AKSES SISWA
    |--------------------------------------------------------------------------
    */

    private function ensureSiswa(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'siswa',
            403,
            'Fitur kuis hanya dapat diakses oleh Siswa.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DAFTAR SOAL BERDASARKAN NOMOR BAB DAN SUBBAB
    |--------------------------------------------------------------------------
    | Menggunakan nomor Bab/Subbab karena navigasi Siswa
    | saat ini masih menggunakan BAB 1, Subbab 1.1, 1.2, 1.3.
    |
    | Hanya mengirim soal yang telah dipublikasikan.
    | Kunci jawaban dan pembahasan TIDAK dikirim.
    */

    public function questions(
        Request $request,
        int $chapterNumber,
        int $subchapterNumber
    ): JsonResponse {
        $this->ensureSiswa($request);

        $chapters = Chapter::query()
            ->where('nomor_bab', $chapterNumber)
            ->where('is_published', true)
            ->whereHas('subchapters', function ($query) use ($subchapterNumber) {
                $query->where('nomor_subbab', $subchapterNumber)
                    ->where('is_published', true);
            })
            ->orderBy('id')
            ->get();

        $questions = collect();

        foreach ($chapters as $chapter) {
            $subchapter = $chapter->subchapters()
                ->where('nomor_subbab', $subchapterNumber)
                ->where('is_published', true)
                ->first();

            if (!$subchapter) {
                continue;
            }

            $chapterQuestions = QuizQuestion::query()
                ->where('subchapter_id', $subchapter->id)
                ->where('is_published', true)
                ->orderBy('nomor_soal')
                ->get([
                    'id',
                    'nomor_soal',
                    'pertanyaan',
                    'pilihan_a',
                    'pilihan_b',
                    'pilihan_c',
                    'pilihan_d',
                ]);

            $questions = $questions->concat($chapterQuestions);
        }

        return response()->json([
            'chapter_number' => $chapterNumber,
            'subchapter_number' => $subchapterNumber,
            'questions' => $questions->values(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PERIKSA JAWABAN SISWA
    |--------------------------------------------------------------------------
    | Siswa mengirim ID soal dan jawaban A/B/C/D.
    | Server memeriksa kunci, lalu mengirim hasilnya.
    */

    public function checkAnswer(
        Request $request,
        int $question
    ): JsonResponse {
        $this->ensureSiswa($request);

        $data = $request->validate([
            'jawaban' => [
                'required',
                Rule::in(['A', 'B', 'C', 'D']),
            ],
        ]);

        $quizQuestion = QuizQuestion::query()
            ->whereKey($question)
            ->where('is_published', true)
            ->whereHas('subchapter', function ($query) {
                $query->where('is_published', true)
                    ->whereHas('chapter', function ($chapterQuery) {
                        $chapterQuery->where('is_published', true);
                    });
            })
            ->firstOrFail();

        $isCorrect = $data['jawaban'] === $quizQuestion->kunci_jawaban;

        return response()->json([
            'correct' => $isCorrect,
            'correct_answer' => $quizQuestion->kunci_jawaban,
            'explanation' => $quizQuestion->pembahasan,
        ]);
    }
}
