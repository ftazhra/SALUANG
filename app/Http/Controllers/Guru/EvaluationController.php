<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\EvaluationQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EvaluationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | AKSES KHUSUS GURU
    |--------------------------------------------------------------------------
    */

    private function ensureGuru(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'guru',
            403,
            'Fitur ini hanya dapat diakses oleh Guru.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MEMASTIKAN SOAL MILIK GURU YANG LOGIN
    |--------------------------------------------------------------------------
    */

    private function ownedQuestion(
        Request $request,
        int $question
    ): EvaluationQuestion {
        return EvaluationQuestion::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($question);
    }

    /*
    |--------------------------------------------------------------------------
    | JENIS SOAL BERDASARKAN NOMOR
    |--------------------------------------------------------------------------
    */

    private function questionType(int $number): string
    {
        return match (true) {
            $number >= 1 && $number <= 15 => 'pilihan_ganda',
            $number >= 16 && $number <= 17 => 'drag_drop',
            $number >= 18 && $number <= 20 => 'menghubungkan',
            default => throw ValidationException::withMessages([
                'nomor_soal' => 'Nomor soal harus antara 1 sampai 20.',
            ]),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI ISI SOAL
    |--------------------------------------------------------------------------
    */

    private function validateQuestion(
        Request $request,
        ?EvaluationQuestion $existing = null
    ): array {
        $userId = $request->user()->id;

        $data = $request->validate([
            'nomor_soal' => [
                'required',
                'integer',
                'between:1,20',
                Rule::unique('evaluation_questions', 'nomor_soal')
                    ->where('user_id', $userId)
                    ->ignore($existing?->id),
            ],
            'tipe_soal' => [
                'required',
                Rule::in([
                    'pilihan_ganda',
                    'drag_drop',
                    'menghubungkan',
                ]),
            ],
            'pertanyaan' => [
                'required',
                'string',
                'max:5000',
            ],
            'konten_soal' => [
                'required',
                'array',
            ],
            'kunci_jawaban' => [
                'required',
                'array',
            ],
            'pembahasan' => [
                'nullable',
                'string',
            ],
            'is_published' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $number = (int) $data['nomor_soal'];
        $expectedType = $this->questionType($number);

        if ($data['tipe_soal'] !== $expectedType) {
            throw ValidationException::withMessages([
                'tipe_soal' =>
                "Soal nomor {$number} harus menggunakan tipe {$expectedType}.",
            ]);
        }

        /*
         * Pilihan ganda membutuhkan 4 pilihan
         * dan satu kunci A, B, C, atau D.
         */
        if ($expectedType === 'pilihan_ganda') {
            $validated = $request->validate([
                'konten_soal.pilihan_a' => [
                    'required',
                    'string',
                    'max:2000',
                ],
                'konten_soal.pilihan_b' => [
                    'required',
                    'string',
                    'max:2000',
                ],
                'konten_soal.pilihan_c' => [
                    'required',
                    'string',
                    'max:2000',
                ],
                'konten_soal.pilihan_d' => [
                    'required',
                    'string',
                    'max:2000',
                ],
                'kunci_jawaban.jawaban' => [
                    'required',
                    Rule::in(['A', 'B', 'C', 'D']),
                ],
            ]);

            $data['konten_soal'] = $validated['konten_soal'];
            $data['kunci_jawaban'] = $validated['kunci_jawaban'];
        }

        /*
         * Soal interaktif:
         *
         * items   = daftar objek/gambar yang dijawab
         * targets = daftar kategori atau pasangan tujuan
         * pairs   = pemetaan ID item ke ID target benar
         *
         * Untuk memilih gambar, gunakan satu item jawaban
         * dengan key "jawaban" pada kunci_jawaban.
         */
        if (in_array($expectedType, [
            'drag_drop',
            'menghubungkan',
        ], true)) {
            $validated = $request->validate([
                'konten_soal.items' => [
                    'required',
                    'array',
                    'min:1',
                ],
                'konten_soal.items.*.id' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'konten_soal.items.*.label' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'konten_soal.items.*.image' => [
                    'nullable',
                    'string',
                    'max:2048',
                ],
                'konten_soal.targets' => [
                    'required',
                    'array',
                    'min:1',
                ],
                'konten_soal.targets.*.id' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'konten_soal.targets.*.label' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'kunci_jawaban.pairs' => [
                    'required',
                    'array',
                    'min:1',
                ],
            ]);

            $items = collect($validated['konten_soal']['items']);
            $targets = collect($validated['konten_soal']['targets']);
            $pairs = $validated['kunci_jawaban']['pairs'];

            $itemIds = $items->pluck('id')->all();
            $targetIds = $targets->pluck('id')->all();

            if (
                count($itemIds) !== count(array_unique($itemIds)) ||
                count($targetIds) !== count(array_unique($targetIds))
            ) {
                throw ValidationException::withMessages([
                    'konten_soal' => 'ID item dan target harus unik.',
                ]);
            }

            if (
                count($pairs) !== count($itemIds) ||
                array_diff($itemIds, array_keys($pairs)) ||
                array_diff(array_keys($pairs), $itemIds) ||
                array_diff(array_values($pairs), $targetIds)
            ) {
                throw ValidationException::withMessages([
                    'kunci_jawaban' =>
                    'Setiap item harus memiliki pasangan target yang valid.',
                ]);
            }

            $data['konten_soal'] = $validated['konten_soal'];
            $data['kunci_jawaban'] = $validated['kunci_jawaban'];
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | 01. DAFTAR SOAL EVALUASI
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $this->ensureGuru($request);

        $questions = EvaluationQuestion::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('nomor_soal')
            ->get();

        return response()->json([
            'questions' => $questions,
            'total' => $questions->count(),
            'published' => $questions
                ->where('is_published', true)
                ->count(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 02. TAMBAH SOAL
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): JsonResponse
    {
        $this->ensureGuru($request);

        $data = $this->validateQuestion($request);

        $data['user_id'] = $request->user()->id;

        $question = EvaluationQuestion::create($data);

        return response()->json([
            'message' => 'Soal evaluasi berhasil ditambahkan.',
            'question' => $question,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | 03. DETAIL SOAL
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        int $question
    ): JsonResponse {
        $this->ensureGuru($request);

        return response()->json([
            'question' => $this->ownedQuestion($request, $question),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 04. EDIT SOAL
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        int $question
    ): JsonResponse {
        $this->ensureGuru($request);

        $existing = $this->ownedQuestion($request, $question);

        $data = $this->validateQuestion($request, $existing);

        $existing->update($data);

        return response()->json([
            'message' => 'Soal evaluasi berhasil diperbarui.',
            'question' => $existing->fresh(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 05. HAPUS SOAL
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        int $question
    ): JsonResponse {
        $this->ensureGuru($request);

        $existing = $this->ownedQuestion($request, $question);

        $existing->delete();

        return response()->json([
            'message' => 'Soal evaluasi berhasil dihapus.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | 06. UPLOAD GAMBAR SOAL EVALUASI
    |--------------------------------------------------------------------------
    */

    public function uploadImage(Request $request): JsonResponse
    {
        $this->ensureGuru($request);

        $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $path = $request->file('image')->store(
            'evaluation-images',
            'public'
        );

        return response()->json([
            'message' => 'Gambar berhasil diunggah.',
            'path' => $path,
            'url' => asset('storage/' . $path),
        ], 201);
    }
}
