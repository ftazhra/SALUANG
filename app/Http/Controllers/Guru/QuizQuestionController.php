<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\QuizQuestion;
use App\Models\StudentAssessmentResult;
use App\Models\StudentQuizAttempt;
use App\Models\StudentQuizAnswer;
use App\Models\StudentQuizActivityAnswer;
use App\Models\Subchapter;
use App\Services\WebsiteNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class QuizQuestionController extends Controller
{
    private const ATTEMPT_QUESTION_SNAPSHOTS = '_question_snapshots';

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

    public function uploadActivityImage(
        Request $request,
        int $subchapter
    ): JsonResponse {
        $this->ensureGuru($request);
        $this->getOwnedSubchapter($request, $subchapter);
        $data = $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:4096',
            ],
        ]);
        $path = $data['image']->store('formative-activities', 'public');

        return response()->json([
            'message' => 'Gambar aktivitas berhasil diunggah.',
            'path' => $path,
            'url' => '/storage/' . $path,
        ], 201);
    }
    /*
    |--------------------------------------------------------------------------
    | 06. TAMBAH SOAL
    |--------------------------------------------------------------------------
    */
    public function store(
        Request $request,
        int $subchapter,
        WebsiteNotificationService $notifications
    ): JsonResponse {
        $this->ensureGuru($request);
        $ownedSubchapter = $this->getOwnedSubchapter(
            $request,
            $subchapter
        );
        $type = $request->input('activity_type', 'multiple_choice');
        $type = is_string($type) ? $type : '';
        $request->merge(['activity_type' => $type]);
        $data = $request->validate($this->questionRules(
            $ownedSubchapter->id,
            null,
            $type
        ));
        $data['subchapter_id'] = $ownedSubchapter->id;
        $data = $this->prepareQuestionData($data, $type);
        $question = QuizQuestion::create($data);
        if ($ownedSubchapter->is_published && $ownedSubchapter->chapter?->is_published && $question->is_published) {
            $notifications->publishedContent(
                (int) $request->user()->id,
                'quiz_question',
                (int) $question->id
            );
        }
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
        int $question,
        WebsiteNotificationService $notifications
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
        $type = $request->input(
            'activity_type',
            $ownedQuestion->activity_type ?? 'multiple_choice'
        );
        $type = is_string($type) ? $type : '';
        $request->merge(['activity_type' => $type]);
        abort_unless(
            $type === ($ownedQuestion->activity_type ?? 'multiple_choice'),
            422,
            'Jenis kegiatan tidak dapat diubah setelah dibuat.'
        );
        $data = $request->validate($this->questionRules(
            $ownedSubchapter->id,
            $ownedQuestion->id,
            $type
        ));
        $data['subchapter_id'] = $ownedSubchapter->id;
        $data = $this->prepareQuestionData($data, $type);
        $wasPublished = (bool) $ownedQuestion->is_published;
        $ownedQuestion->update($data);
        if (
            !$wasPublished
            && $ownedQuestion->fresh()->is_published
            && $ownedSubchapter->is_published
            && $ownedSubchapter->chapter?->is_published
        ) {
            $notifications->publishedContent(
                (int) $request->user()->id,
                'quiz_question',
                (int) $ownedQuestion->id
            );
        }
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
        $action = DB::transaction(function () use ($ownedSubchapter, $question): string {
            Subchapter::query()
                ->whereKey($ownedSubchapter->id)
                ->lockForUpdate()
                ->firstOrFail();

            $ownedQuestion = QuizQuestion::query()
                ->where('subchapter_id', $ownedSubchapter->id)
                ->lockForUpdate()
                ->findOrFail($question);

            $activeAttempts = StudentQuizAttempt::query()
                ->where('subchapter_id', $ownedSubchapter->id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->get();
            $activeAttemptReferencesQuestion = false;
            foreach ($activeAttempts as $attempt) {
                $review = $attempt->review ?? [];
                $snapshots = $review[self::ATTEMPT_QUESTION_SNAPSHOTS] ?? null;

                if (!is_array($snapshots) && $ownedQuestion->is_published) {
                    $snapshots = QuizQuestion::query()
                        ->where('subchapter_id', $ownedSubchapter->id)
                        ->where('is_published', true)
                        ->orderBy('nomor_soal')
                        ->get()
                        ->map(static fn (QuizQuestion $item) => $item->getAttributes())
                        ->all();
                    $review[self::ATTEMPT_QUESTION_SNAPSHOTS] = $snapshots;
                    $attempt->review = $review;
                    $attempt->save();
                }

                if (!is_array($snapshots)) {
                    continue;
                }

                $activeAttemptReferencesQuestion = $activeAttemptReferencesQuestion
                    || collect($snapshots)->contains(
                        fn ($snapshot) => is_array($snapshot)
                            && (int) ($snapshot['id'] ?? 0) === (int) $ownedQuestion->id
                    );
            }

            $hasStoredAnswer = StudentQuizAnswer::query()
                ->where('quiz_question_id', $ownedQuestion->id)
                ->exists()
                || StudentQuizActivityAnswer::query()
                    ->where('quiz_question_id', $ownedQuestion->id)
                    ->exists();
            $hasAttemptAnswer = StudentQuizAttempt::query()
                ->where('subchapter_id', $ownedQuestion->subchapter_id)
                ->where(function ($query) use ($ownedQuestion) {
                    $query->whereNotNull('answers->' . $ownedQuestion->id)
                        ->orWhere('status', 'completed');
                })
                ->lockForUpdate()
                ->get(['id'])
                ->isNotEmpty();
            $hasLegacyResult = StudentAssessmentResult::query()
                ->where('subchapter_id', $ownedSubchapter->id)
                ->where('assessment_type', 'quiz')
                ->whereNotNull('completed_at')
                ->exists();

            if (
                $hasStoredAnswer
                || $hasAttemptAnswer
                || $hasLegacyResult
                || $activeAttemptReferencesQuestion
            ) {
                abort_unless(
                    $ownedQuestion->delete(),
                    500,
                    'Soal gagal diarsipkan.'
                );

                return 'archived';
            }

            abort_unless(
                $ownedQuestion->forceDelete(),
                500,
                'Soal gagal dihapus.'
            );

            return 'deleted';
        });

        return response()->json([
            'message' => $action === 'archived'
                ? 'Soal berhasil diarsipkan. Riwayat dan nilai siswa tetap tersimpan.'
                : 'Soal berhasil dihapus.',
            'action' => $action,
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | 10. VALIDASI TAMBAH DAN EDIT SOAL
    |--------------------------------------------------------------------------
    */
    private function questionRules(
        int $subchapterId,
        ?int $ignoreQuestionId = null,
        string $type = 'multiple_choice'
    ): array {
        $uniqueNumber = Rule::unique(
            'quiz_questions',
            'nomor_soal'
        )->where(
            'subchapter_id',
            $subchapterId
        )->whereNull('deleted_at');
        if ($ignoreQuestionId !== null) {
            $uniqueNumber->ignore($ignoreQuestionId);
        }
        $rules = [
            'nomor_soal' => [
                'required',
                'integer',
                'min:1',
                $uniqueNumber,
            ],
            'activity_type' => [
                'required',
                Rule::in(['multiple_choice', 'drag_drop', 'matching']),
            ],
            'pertanyaan' => [
                'required',
                'string',
            ],
            'is_published' => [
                'sometimes',
                'boolean',
            ],
        ];
        if ($type === 'multiple_choice') {
            return $rules + [
                'pilihan_a' => ['required', 'string'],
                'pilihan_b' => ['required', 'string'],
                'pilihan_c' => ['required', 'string'],
                'pilihan_d' => ['required', 'string'],
                'kunci_jawaban' => [
                    'required',
                    Rule::in(['A', 'B', 'C', 'D']),
                ],
                'pembahasan' => ['nullable', 'string'],
            ];
        }
        $rules['activity_instruction'] = ['nullable', 'string', 'max:500'];
        if ($type === 'drag_drop') {
            return $rules + [
                'items' => ['required', 'array', 'min:2', 'max:12'],
                'items.*.label' => ['required', 'string', 'max:180'],
                'items.*.image_url' => ['nullable', 'string', 'max:2048'],
                'items.*.category_id' => ['nullable', 'string', 'max:80', 'regex:/\A[a-zA-Z0-9_-]+\z/'],
                'items.*.category_index' => ['nullable', 'integer', 'min:0'],
                'categories' => ['required', 'array', 'min:2', 'max:8'],
                'categories.*.id' => ['nullable', 'string', 'max:80', 'regex:/\A[a-zA-Z0-9_-]+\z/'],
                'categories.*.label' => ['required', 'string', 'max:100'],
            ];
        }
        return $rules + [
            'left_items' => ['required', 'array', 'min:2', 'max:12'],
            'left_items.*.label' => ['required', 'string', 'max:180'],
            'left_items.*.image_url' => ['nullable', 'string', 'max:2048'],
            'left_items.*.right_index' => ['nullable', 'integer', 'min:0'],
            'left_items.*.right_indices' => ['nullable', 'array', 'min:1', 'max:12'],
            'left_items.*.right_indices.*' => ['required', 'integer', 'min:0'],
            'right_items' => ['required', 'array', 'min:2', 'max:12'],
            'right_items.*.label' => ['required', 'string', 'max:180'],
            'right_items.*.image_url' => ['nullable', 'string', 'max:2048'],
        ];
    }

    private function prepareQuestionData(array $data, string $type): array
    {
        $data['activity_type'] = $type;
        if ($type === 'multiple_choice') {
            $data['activity_payload'] = null;
            return $data;
        }

        $this->validateActivityReferences($data, $type);
        $payload = [
            'instruction' => trim($data['activity_instruction'] ?? ''),
        ];
        if ($type === 'drag_drop') {
            $payload['items'] = [];
            $payload['categories'] = [];
            $payload['assignments'] = [];
            $categoryIds = [];
            foreach ($data['categories'] as $index => $category) {
                $categoryId = trim((string) ($category['id'] ?? ''))
                    ?: 'category_' . ($index + 1);
                if (isset($categoryIds[$categoryId])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'categories' => 'Setiap kategori harus memiliki identitas berbeda.',
                    ]);
                }
                $categoryIds[$categoryId] = true;
                $payload['categories'][] = [
                    'id' => $categoryId,
                    'label' => trim($category['label']),
                ];
            }
            foreach ($data['items'] as $index => $item) {
                $itemId = 'item_' . ($index + 1);
                $categoryId = $item['category_id'] ?? null;
                if (!$categoryId && isset($item['category_index'])) {
                    $categoryId = $payload['categories'][(int) $item['category_index']]['id'] ?? null;
                }
                if (!$categoryId || !isset($categoryIds[$categoryId])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}.category_id" => 'Pilih kembali kategori untuk objek ini.',
                    ]);
                }
                $payload['items'][] = [
                    'id' => $itemId,
                    'label' => trim($item['label']),
                    'image_url' => $this->cleanImageUrl($item['image_url'] ?? null),
                ];
                $payload['assignments'][$itemId] = $categoryId;
            }
        } else {
            $payload['left_items'] = [];
            $payload['right_items'] = [];
            $payload['pairs'] = [];
            foreach ($data['right_items'] as $index => $item) {
                $payload['right_items'][] = [
                    'id' => 'right_' . ($index + 1),
                    'label' => trim($item['label']),
                    'image_url' => $this->cleanImageUrl($item['image_url'] ?? null),
                ];
            }
            foreach ($data['left_items'] as $index => $item) {
                $leftId = 'left_' . ($index + 1);
                $payload['left_items'][] = [
                    'id' => $leftId,
                    'label' => trim($item['label']),
                    'image_url' => $this->cleanImageUrl($item['image_url'] ?? null),
                ];
                $rightIndices = $item['right_indices']
                    ?? (isset($item['right_index']) ? [$item['right_index']] : []);
                foreach (array_unique($rightIndices) as $rightIndex) {
                    $payload['pairs'][] = [
                        'left_id' => $leftId,
                        'right_id' => 'right_' . ((int) $rightIndex + 1),
                    ];
                }
            }
        }

        $data['activity_payload'] = $payload;
        $data['pilihan_a'] = '';
        $data['pilihan_b'] = '';
        $data['pilihan_c'] = '';
        $data['pilihan_d'] = '';
        $data['kunci_jawaban'] = 'A';
        $data['pembahasan'] = null;
        unset(
            $data['activity_instruction'],
            $data['items'],
            $data['categories'],
            $data['left_items'],
            $data['right_items']
        );
        return $data;
    }

    private function validateActivityReferences(array $data, string $type): void
    {
        $errors = [];
        $imageCollections = $type === 'drag_drop'
            ? ['items']
            : ['left_items', 'right_items'];
        foreach ($imageCollections as $collection) {
            foreach ($data[$collection] as $index => $item) {
                if (!$this->isSafeImageUrl($item['image_url'] ?? null)) {
                    $errors["{$collection}.{$index}.image_url"] =
                        'Gunakan URL HTTPS atau path gambar /images/ dan /storage/.';
                }
            }
        }
        if ($type === 'drag_drop') {
            $categoryCount = count($data['categories']);
            $categoryIds = collect($data['categories'])
                ->map(fn (array $category, int $index) =>
                    trim((string) ($category['id'] ?? '')) ?: 'category_' . ($index + 1)
                )
                ->all();
            foreach ($data['items'] as $index => $item) {
                $categoryId = $item['category_id'] ?? null;
                if ($categoryId !== null && $categoryId !== '') {
                    if (!in_array($categoryId, $categoryIds, true)) {
                        $errors["items.{$index}.category_id"] =
                            'Pilih kembali kategori yang tersedia.';
                    }
                } elseif (
                    !isset($item['category_index'])
                    || (int) $item['category_index'] >= $categoryCount
                ) {
                    $errors["items.{$index}.category_index"] =
                        'Pilih kategori yang tersedia.';
                }
            }
        } else {
            $rightCount = count($data['right_items']);
            foreach ($data['left_items'] as $index => $item) {
                $rightIndices = $item['right_indices']
                    ?? (isset($item['right_index']) ? [$item['right_index']] : []);
                if (!$rightIndices) {
                    $errors["left_items.{$index}.right_indices"] =
                        'Pilih minimal satu pasangan untuk setiap objek.';
                    continue;
                }
                foreach ($rightIndices as $rightIndex) {
                    if ((int) $rightIndex >= $rightCount) {
                        $errors["left_items.{$index}.right_indices"] =
                            'Pilih pasangan yang tersedia.';
                    }
                }
            }
        }
        if ($errors) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    private function isSafeImageUrl(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return true;
        }
        $value = trim($value);
        if (
            preg_match('#^/(?:images|storage)/[^\\\\]+$#', $value) === 1
            && !preg_match('#(?:^|/)\.\.(?:/|$)#', $value)
        ) {
            return true;
        }
        $parts = parse_url($value);
        return is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && !empty($parts['host'])
            && !isset($parts['user'])
            && !isset($parts['pass']);
    }

    private function cleanImageUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}