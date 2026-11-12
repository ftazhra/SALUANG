<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Models\EvaluationQuestion;
use App\Services\WebsiteNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        if ($number < 1 || $number > 15) {
            throw ValidationException::withMessages([
                'nomor_soal' => 'Nomor soal harus antara 1 sampai 15.',
            ]);
        }

        return 'pilihan_ganda';
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
                'between:1,15',
                Rule::unique('evaluation_questions', 'nomor_soal')
                    ->where('user_id', $userId)
                    ->ignore($existing?->id),
            ],
            'tipe_soal' => [
                'required',
                Rule::in(['pilihan_ganda']),
            ],
            'pertanyaan' => [
                'required',
                'string',
                'max:5000',
            ],
            'konten_soal' => [
                'required',
                'array:pilihan_a,pilihan_b,pilihan_c,pilihan_d',
            ],
            'konten_soal.pilihan_a' => ['required', 'string', 'max:2000'],
            'konten_soal.pilihan_b' => ['required', 'string', 'max:2000'],
            'konten_soal.pilihan_c' => ['required', 'string', 'max:2000'],
            'konten_soal.pilihan_d' => ['required', 'string', 'max:2000'],
            'kunci_jawaban' => ['required', 'array:jawaban'],
            'kunci_jawaban.jawaban' => [
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
        ]);
        $number = (int) $data['nomor_soal'];
        if ($data['tipe_soal'] !== $this->questionType($number)) {
            throw ValidationException::withMessages([
                'tipe_soal' =>
                "Soal nomor {$number} harus berupa pilihan ganda A–D.",
            ]);
        }
        $data['tipe_soal'] = 'pilihan_ganda';
        return $data;
    }

    private function archiveQuestion(EvaluationQuestion $question, int $teacherId): void
    {
        $snapshot = [
            'id' => $question->id,
            'user_id' => $question->user_id,
            'nomor_soal' => $question->nomor_soal,
            'tipe_soal' => $question->tipe_soal,
            'pertanyaan' => $question->getRawOriginal('pertanyaan'),
            'konten_soal' => $question->getRawOriginal('konten_soal'),
            'kunci_jawaban' => $question->getRawOriginal('kunci_jawaban'),
            'pembahasan' => $question->getRawOriginal('pembahasan'),
            'is_published' => $question->getRawOriginal('is_published'),
            '_attempt_id_cutoff' => Schema::hasTable('evaluation_attempts')
                ? DB::table('evaluation_attempts')->max('id')
                : null,
        ];

        DB::table('evaluation_question_archives')->insert([
            'question_id' => $question->id,
            'teacher_id' => $teacherId,
            'question_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
    public function store(
        Request $request,
        WebsiteNotificationService $notifications
    ): JsonResponse
    {
        $this->ensureGuru($request);
        $data = $this->validateQuestion($request);
        $data['user_id'] = $request->user()->id;
        $question = EvaluationQuestion::create($data);
        if ($question->is_published) {
            $notifications->publishedContent(
                (int) $request->user()->id,
                'evaluation_question',
                (int) $question->id
            );
        }
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
        int $question,
        WebsiteNotificationService $notifications
    ): JsonResponse {
        $this->ensureGuru($request);
        $existing = $this->ownedQuestion($request, $question);
        $data = $this->validateQuestion($request, $existing);
        $wasPublished = (bool) $existing->is_published;
        if ($existing->tipe_soal !== 'pilihan_ganda') {
            if (!Schema::hasTable('evaluation_question_archives')) {
                return response()->json([
                    'message' => 'Konversi belum dapat disimpan. Arsip soal lama belum tersedia.',
                ], 503);
            }

            DB::transaction(function () use ($existing, $data, $request) {
                $this->archiveQuestion($existing, (int) $request->user()->id);
                $existing->update($data);
            });
        } else {
            $existing->update($data);
        }

        if (!$wasPublished && $existing->fresh()->is_published) {
            $notifications->publishedContent(
                (int) $request->user()->id,
                'evaluation_question',
                (int) $existing->id
            );
        }
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
        if (!Schema::hasTable('evaluation_question_archives')) {
            return response()->json([
                'message' => 'Soal belum dapat dihapus. Arsip soal evaluasi belum tersedia.',
            ], 503);
        }

        DB::transaction(function () use ($existing, $request) {
            $this->archiveQuestion($existing, (int) $request->user()->id);
            $existing->delete();
        });
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
