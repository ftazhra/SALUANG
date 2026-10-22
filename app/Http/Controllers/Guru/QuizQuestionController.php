<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\QuizQuestion;
use App\Models\Subchapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuizQuestionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 01. PERIKSA AKSES GURU
    |--------------------------------------------------------------------------
    */

    private function ensureGuru(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'guru',
            403,
            'Fitur ini hanya dapat digunakan oleh Guru.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 02. AMBIL SUBBAB MILIK GURU
    |--------------------------------------------------------------------------
    */

    private function getOwnedSubchapter(
        Request $request,
        int $subchapterId
    ): Subchapter {
        return Subchapter::query()
            ->whereKey($subchapterId)
            ->whereHas('chapter', function ($query) use ($request) {
                $query->where(
                    'user_id',
                    $request->user()->id
                );
            })
            ->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | 03. AMBIL SOAL PADA SUBBAB TERTENTU
    |--------------------------------------------------------------------------
    */

    private function getOwnedQuestion(
        Subchapter $subchapter,
        int $questionId
    ): QuizQuestion {
        return QuizQuestion::query()
            ->where('subchapter_id', $subchapter->id)
            ->findOrFail($questionId);
    }

    /*
    |--------------------------------------------------------------------------
    | 04. DAFTAR BAB UNTUK DROPDOWN
    |--------------------------------------------------------------------------
    */

    public function chapters(Request $request): JsonResponse
    {
        $this->ensureGuru($request);

        $chapters = Chapter::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'subchapters' => function ($query) {
                    $query->orderBy('nomor_subbab');
                }
            ])
            ->orderBy('nomor_bab')
            ->get();

        return response()->json([
            'chapters' => $chapters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 05. DAFTAR SOAL BERDASARKAN SUBBAB
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request,
        int $subchapter
    ): JsonResponse {
        $this->ensureGuru($request);

        $ownedSubchapter = $this->getOwnedSubchapter(
            $request,
            $subchapter
        );

        $questions = QuizQuestion::query()
            ->where('subchapter_id', $ownedSubchapter->id)
            ->orderBy('nomor_soal')
            ->get();

        return response()->json([
            'subchapter' => $ownedSubchapter,
            'questions' => $questions,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 06. TAMBAH SOAL
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        int $subchapter
    ): JsonResponse {
        $this->ensureGuru($request);

        $ownedSubchapter = $this->getOwnedSubchapter(
            $request,
            $subchapter
        );

        $data = $request->validate(
            $this->questionRules($ownedSubchapter->id)
        );

        $data['subchapter_id'] = $ownedSubchapter->id;

        $question = QuizQuestion::create($data);

        return response()->json([
            'message' => 'Soal berhasil ditambahkan.',
            'question' => $question,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | 07. DETAIL / PRATINJAU SOAL
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        int $subchapter,
        int $question
    ): JsonResponse {
        $this->ensureGuru($request);

        $ownedSubchapter = $this->getOwnedSubchapter(
            $request,
            $subchapter
        );

        $ownedQuestion = $this->getOwnedQuestion(
            $ownedSubchapter,
            $question
        );

        return response()->json([
            'question' => $ownedQuestion,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 08. EDIT SOAL
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        int $subchapter,
        int $question
    ): JsonResponse {
        $this->ensureGuru($request);

        $ownedSubchapter = $this->getOwnedSubchapter(
            $request,
            $subchapter
        );

        $ownedQuestion = $this->getOwnedQuestion(
            $ownedSubchapter,
            $question
        );

        $data = $request->validate(
            $this->questionRules(
                $ownedSubchapter->id,
                $ownedQuestion->id
            )
        );

        $ownedQuestion->update($data);

        return response()->json([
            'message' => 'Soal berhasil diperbarui.',
            'question' => $ownedQuestion->fresh(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 09. HAPUS SOAL
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        int $subchapter,
        int $question
    ): JsonResponse {
        $this->ensureGuru($request);

        $ownedSubchapter = $this->getOwnedSubchapter(
            $request,
            $subchapter
        );

        $ownedQuestion = $this->getOwnedQuestion(
            $ownedSubchapter,
            $question
        );

        $ownedQuestion->delete();

        return response()->json([
            'message' => 'Soal berhasil dihapus.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 10. VALIDASI TAMBAH DAN EDIT SOAL
    |--------------------------------------------------------------------------
    */

    private function questionRules(
        int $subchapterId,
        ?int $ignoreQuestionId = null
    ): array {
        $uniqueNumber = Rule::unique(
            'quiz_questions',
            'nomor_soal'
        )->where(
            'subchapter_id',
            $subchapterId
        );

        if ($ignoreQuestionId !== null) {
            $uniqueNumber->ignore($ignoreQuestionId);
        }

        return [
            'nomor_soal' => [
                'required',
                'integer',
                'min:1',
                $uniqueNumber,
            ],

            'pertanyaan' => [
                'required',
                'string',
            ],

            'pilihan_a' => [
                'required',
                'string',
            ],

            'pilihan_b' => [
                'required',
                'string',
            ],

            'pilihan_c' => [
                'required',
                'string',
            ],

            'pilihan_d' => [
                'required',
                'string',
            ],

            'kunci_jawaban' => [
                'required',
                Rule::in(['A', 'B', 'C', 'D']),
            ],

            'pembahasan' => [
                'nullable',
                'string',
            ],

            'is_published' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}