<?php
namespace App\Http\Controllers\Siswa;
use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\QuizQuestion;
use App\Models\StudentAssessmentResult;
use App\Models\StudentQuizAnswer;
use App\Models\StudentSubchapterProgress;
use App\Models\Subchapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function questionsForSubchapter(
        Request $request,
        Subchapter $subchapter
    ): JsonResponse {
        $this->ensureSiswa($request);
        abort_unless(
            $subchapter->is_published
                && $subchapter->chapter?->is_published
                && $subchapter->chapter?->guru?->role === 'guru',
            404
        );
        $questions = $subchapter->quizQuestions()
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
                'kunci_jawaban',
                'pembahasan',
            ]);
        $answers = StudentQuizAnswer::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('quiz_question_id', $questions->pluck('id'))
            ->get()
            ->keyBy('quiz_question_id');
        $questions->each(function (QuizQuestion $question) use ($answers) {
            $answer = $answers->get($question->id);
            $correctAnswer = $question->kunci_jawaban;
            $explanation = $question->pembahasan;
            $question->makeHidden(['kunci_jawaban', 'pembahasan']);
            if (!$answer) {
                return;
            }
            $question->setAttribute('student_answer', [
                'answer' => $answer->answer,
                'correct' => $answer->is_correct,
                'correct_answer' => $correctAnswer,
                'explanation' => $explanation,
            ]);
        });
        return response()->json([
            'chapter_number' => $subchapter->chapter->nomor_bab,
            'subchapter_number' => $subchapter->nomor_subbab,
            'questions' => $questions,
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
                        $chapterQuery->where('is_published', true)
                            ->whereHas('guru', fn ($guruQuery) => $guruQuery->where('role', 'guru'));
                    });
            })
            ->firstOrFail();
        $subchapter = $quizQuestion->subchapter;
        $isCorrect = $data['jawaban'] === $quizQuestion->kunci_jawaban;
        $result = DB::transaction(function () use (
            $request,
            $quizQuestion,
            $subchapter,
            $data,
            $isCorrect
        ) {
            $progress = StudentSubchapterProgress::query()
                ->where([
                    'user_id' => $request->user()->id,
                    'subchapter_id' => $subchapter->id,
                ])
                ->lockForUpdate()
                ->first();
            abort_unless(
                $progress?->opened_at,
                422,
                'Buka dan pelajari materi Subbab ini sebelum mengerjakan kuis.'
            );
            StudentQuizAnswer::query()->updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'quiz_question_id' => $quizQuestion->id,
                ],
                [
                    'subchapter_id' => $subchapter->id,
                    'answer' => $data['jawaban'],
                    'is_correct' => $isCorrect,
                ]
            );
            $requiredQuestionIds = $subchapter->quizQuestions()
                ->where('is_published', true)
                ->pluck('id');
            $answers = StudentQuizAnswer::query()
                ->where('user_id', $request->user()->id)
                ->where('subchapter_id', $subchapter->id)
                ->whereIn('quiz_question_id', $requiredQuestionIds)
                ->get();
            $totalQuestions = $requiredQuestionIds->count();
            $isComplete = $totalQuestions > 0
                && $answers->pluck('quiz_question_id')->unique()->count() === $totalQuestions;
            if (!$isComplete) {
                return [
                    'completed' => false,
                    'score' => null,
                    'correct_answers' => null,
                    'total_questions' => $totalQuestions,
                ];
            }
            $correctAnswers = $answers->where('is_correct', true)->count();
            $score = round(($correctAnswers / $totalQuestions) * 100, 2);
            $now = now();
            StudentSubchapterProgress::query()
                ->where('user_id', $request->user()->id)
                ->where('subchapter_id', $subchapter->id)
                ->update([
                    'status' => 'completed',
                    'completed_at' => $now,
                    'updated_at' => $now,
                ]);
            $assessment = StudentAssessmentResult::query()
                ->where('user_id', $request->user()->id)
                ->where('assessment_type', 'quiz')
                ->where('subchapter_id', $subchapter->id)
                ->first();
            if (!$assessment) {
                $assessment = new StudentAssessmentResult([
                    'user_id' => $request->user()->id,
                    'assessment_type' => 'quiz',
                    'chapter_id' => $subchapter->chapter_id,
                    'subchapter_id' => $subchapter->id,
                ]);
            }
            $assessment->fill([
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'score' => $score,
                'completed_at' => $now,
            ])->save();
            return [
                'completed' => true,
                'score' => $score,
                'correct_answers' => $correctAnswers,
                'total_questions' => $totalQuestions,
            ];
        });
        return response()->json([
            'correct' => $isCorrect,
            'correct_answer' => $quizQuestion->kunci_jawaban,
            'explanation' => $quizQuestion->pembahasan,
            'completed' => $result['completed'],
            'score' => $result['score'],
            'correct_answers' => $result['correct_answers'],
            'total_questions' => $result['total_questions'],
        ]);
    }
}
