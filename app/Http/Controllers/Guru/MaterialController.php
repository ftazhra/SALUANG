<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Subchapter;
use App\Models\SubchapterMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class MaterialController extends Controller
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
            'Halaman ini hanya untuk Guru.'
        );
    }

    private function introductionImageValidationRules(): array
    {
        return [
            'introduction_image' => [
                'sometimes',
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp',
                'max:4096',
            ],
            'remove_introduction_image' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | AMBIL BAB MILIK GURU
    |--------------------------------------------------------------------------
    */

    private function chapterOwnedByGuru(
        Request $request,
        int $chapter
    ): Chapter {
        return Chapter::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($chapter);
    }

    /*
    |--------------------------------------------------------------------------
    | 01. DAFTAR BAB
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $this->ensureGuru($request);

        $chapters = Chapter::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'subchapters.media',
                'subchapters.contentBlocks.media',
            ])
            ->orderBy('nomor_bab')
            ->get();
        $chapters->each(fn (Chapter $chapter) =>
            $this->attachMaterialBlocks($chapter)
        );

        return response()->json([
            'chapters' => $chapters,
            'image_library' => $this->imageLibrary(),
            'video_library' => $this->videoLibrary(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 02. TAMBAH BAB
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): JsonResponse
    {
        $this->ensureGuru($request);

        $data = $request->validate([
            'nomor_bab' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('chapters', 'nomor_bab')
                    ->where(
                        'user_id',
                        $request->user()->id
                    ),
            ],
            'judul' => [
                'required',
                'string',
                'max:255',
            ],
            'deskripsi' => [
                'nullable',
                'string',
            ],
            'gambar_pengenalan_judul' => [
                'nullable',
                'string',
                'max:255',
            ],
            'gambar_pengenalan_keterangan' => [
                'nullable',
                'string',
            ],
            'pengantar' => [
                'nullable',
                'string',
            ],
            'tujuan_pembelajaran' => [
                'nullable',
                'array',
            ],
            'tujuan_pembelajaran.*' => [
                'required',
                'string',
                'max:1000',
            ],
            'pertanyaan_pemantik' => [
                'nullable',
                'string',
                'not_regex:/[\r\n]/',
            ],
            'is_published' => [
                'sometimes',
                'boolean',
            ],
            ...$this->introductionImageValidationRules(),
        ]);

        $data['user_id'] = $request->user()->id;

        $introductionImage = $data['introduction_image'] ?? null;
        unset($data['introduction_image'], $data['remove_introduction_image']);

        $storedPath = $introductionImage
            ? $introductionImage->store('chapters/introduction', 'public')
            : null;
        if ($introductionImage && !is_string($storedPath)) {
            throw new RuntimeException(
                'Gambar Pengenalan Bab gagal disimpan ke storage.'
            );
        }

        if ($storedPath) {
            $data['gambar_pengenalan_path'] = $storedPath;
        }

        try {
            $chapter = Chapter::create($data);
        } catch (Throwable $exception) {
            $this->deleteStoredFiles([$storedPath]);
            throw $exception;
        }

        return response()->json([
            'message' => 'Bab berhasil ditambahkan.',
            'chapter' => $chapter,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | 03. DETAIL BAB
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        int $chapter
    ): JsonResponse {
        $this->ensureGuru($request);

        $chapter = $this->chapterOwnedByGuru(
            $request,
            $chapter
        );

        $chapter->load([
            'subchapters.media',
            'subchapters.contentBlocks.media',
        ]);
        $this->attachMaterialBlocks($chapter);

        return response()->json([
            'chapter' => $chapter,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 04. EDIT BAB
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        int $chapter
    ): JsonResponse {
        $this->ensureGuru($request);

        $chapter = $this->chapterOwnedByGuru(
            $request,
            $chapter
        );

        $data = $request->validate([
            'nomor_bab' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('chapters', 'nomor_bab')
                    ->where(
                        'user_id',
                        $request->user()->id
                    )
                    ->ignore($chapter->id),
            ],
            'judul' => [
                'required',
                'string',
                'max:255',
            ],
            'deskripsi' => [
                'nullable',
                'string',
            ],
            'gambar_pengenalan_judul' => [
                'nullable',
                'string',
                'max:255',
            ],
            'gambar_pengenalan_keterangan' => [
                'nullable',
                'string',
            ],
            'pengantar' => [
                'nullable',
                'string',
            ],
            'tujuan_pembelajaran' => [
                'nullable',
                'array',
            ],
            'tujuan_pembelajaran.*' => [
                'required',
                'string',
                'max:1000',
            ],
            'pertanyaan_pemantik' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($chapter) {
                    if (
                        is_string($value)
                        && $value !== $chapter->pertanyaan_pemantik
                        && preg_match('/[\r\n]/', $value) === 1
                    ) {
                        $fail('Pertanyaan pemantik hanya boleh berisi satu pertanyaan.');
                    }
                },
            ],
            'is_published' => [
                'sometimes',
                'boolean',
            ],
            ...$this->introductionImageValidationRules(),
        ]);

        $introductionImage = $data['introduction_image'] ?? null;
        $removeIntroductionImage = filter_var(
            $data['remove_introduction_image'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
        unset($data['introduction_image'], $data['remove_introduction_image']);

        $storedPath = $introductionImage
            ? $introductionImage->store('chapters/introduction', 'public')
            : null;
        if ($introductionImage && !is_string($storedPath)) {
            throw new RuntimeException(
                'Gambar Pengenalan Bab gagal disimpan ke storage.'
            );
        }

        $oldPath = $chapter->gambar_pengenalan_path;
        if ($storedPath) {
            $data['gambar_pengenalan_path'] = $storedPath;
        } elseif ($removeIntroductionImage) {
            $data['gambar_pengenalan_path'] = null;
        }

        try {
            $chapter->update($data);
        } catch (Throwable $exception) {
            $this->deleteStoredFiles([$storedPath]);
            throw $exception;
        }

        if (
            ($storedPath || $removeIntroductionImage)
            && $oldPath
            && $oldPath !== $storedPath
        ) {
            $this->deleteOrphanedFiles([$oldPath]);
        }

        return response()->json([
            'message' => 'Bab berhasil diperbarui.',
            'chapter' => $chapter->fresh(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 05. HAPUS BAB
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        int $chapter
    ): JsonResponse {
        $this->ensureGuru($request);

        $chapter = $this->chapterOwnedByGuru(
            $request,
            $chapter
        );

        $chapter->load('subchapters.media');
        $storagePaths = $chapter->subchapters
            ->flatMap(fn (Subchapter $subchapter) =>
                $subchapter->media->pluck('storage_path')
            )
            ->push($chapter->gambar_pengenalan_path)
            ->filter()
            ->all();

        $chapter->delete();
        $this->deleteOrphanedFiles($storagePaths);

        return response()->json([
            'message' => 'Bab berhasil dihapus.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 06. TAMBAH SUBBAB
    |--------------------------------------------------------------------------
    */

    public function storeSubchapter(
        Request $request,
        int $chapter
    ): JsonResponse {
        $this->ensureGuru($request);

        $chapter = $this->chapterOwnedByGuru(
            $request,
            $chapter
        );

        $data = $request->validate(
            $this->subchapterRules($chapter->id)
        );
        $blocks = $this->validatedContentBlocks($request, $data);

        $media = $this->prepareMedia($request, $data);
        try {
            $blockMedia = $blocks === null
                ? ['media_data' => [], 'new_paths' => []]
                : $this->prepareContentBlockMedia($request, $blocks);
        } catch (Throwable $exception) {
            $this->deleteStoredFiles($media['new_paths']);
            throw $exception;
        }
        $subchapterData = $this->subchapterData($data);
        if ($blocks !== null) {
            unset($subchapterData['konten']);
        }

        try {
            $subchapter = DB::transaction(function () use (
                $chapter,
                $subchapterData,
                $media,
                $blockMedia,
                $blocks
            ) {
                $subchapter = $chapter->subchapters()
                    ->create($subchapterData);

                $this->syncMedia($subchapter, $media['operations']);
                if ($blocks !== null) {
                    $this->syncContentBlocks(
                        $subchapter,
                        $blocks,
                        $blockMedia['media_data']
                    );
                }

                return $subchapter;
            });
        } catch (Throwable $exception) {
            $this->deleteStoredFiles([
                ...$media['new_paths'],
                ...$blockMedia['new_paths'],
            ]);
            throw $exception;
        }

        $subchapter->load(['media', 'contentBlocks.media']);
        $this->attachMaterialBlocks($subchapter);

        return response()->json([
            'message' => 'Subbab berhasil ditambahkan.',
            'subchapter' => $subchapter,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | 07. EDIT SUBBAB
    |--------------------------------------------------------------------------
    */

    public function updateSubchapter(
        Request $request,
        int $chapter,
        int $subchapter
    ): JsonResponse {
        $this->ensureGuru($request);

        $chapter = $this->chapterOwnedByGuru(
            $request,
            $chapter
        );

        $subchapter = $chapter->subchapters()
            ->findOrFail($subchapter);

        $data = $request->validate(
            $this->subchapterRules($chapter->id, $subchapter)
        );
        $blocks = $this->validatedContentBlocks(
            $request,
            $data,
            $subchapter
        );
        $media = $this->prepareMedia($request, $data);
        try {
            $blockMedia = $blocks === null
                ? ['media_data' => [], 'new_paths' => []]
                : $this->prepareContentBlockMedia($request, $blocks);
        } catch (Throwable $exception) {
            $this->deleteStoredFiles($media['new_paths']);
            throw $exception;
        }
        $subchapterData = $this->subchapterData($data);
        if ($blocks !== null) {
            unset($subchapterData['konten']);
        }

        try {
            [$subchapter, $oldPaths] = DB::transaction(function () use (
                $subchapter,
                $subchapterData,
                $media,
                $blockMedia,
                $blocks
            ) {
                $subchapter->update($subchapterData);

                $oldPaths = $this->syncMedia(
                    $subchapter,
                    $media['operations']
                );
                if ($blocks !== null) {
                    $this->syncContentBlocks(
                        $subchapter,
                        $blocks,
                        $blockMedia['media_data']
                    );
                }

                return [$subchapter, $oldPaths];
            });
        } catch (Throwable $exception) {
            $this->deleteStoredFiles([
                ...$media['new_paths'],
                ...$blockMedia['new_paths'],
            ]);
            throw $exception;
        }

        $this->deleteOrphanedFiles($oldPaths);
        $subchapter->load(['media', 'contentBlocks.media']);
        $this->attachMaterialBlocks($subchapter);

        return response()->json([
            'message' => 'Subbab berhasil diperbarui.',
            'subchapter' => $subchapter,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 08. HAPUS SUBBAB
    |--------------------------------------------------------------------------
    */

    public function destroySubchapter(
        Request $request,
        int $chapter,
        int $subchapter
    ): JsonResponse {
        $this->ensureGuru($request);

        $chapter = $this->chapterOwnedByGuru(
            $request,
            $chapter
        );

        $subchapter = $chapter->subchapters()
            ->with('media')
            ->findOrFail($subchapter);

        $storagePaths = $subchapter->media
            ->pluck('storage_path')
            ->filter()
            ->all();

        $subchapter->delete();
        $this->deleteOrphanedFiles($storagePaths);

        return response()->json([
            'message' => 'Subbab berhasil dihapus.',
        ]);
    }

    private function subchapterRules(
        int $chapterId,
        ?Subchapter $subchapter = null
    ): array {
        $imagePaths = array_column($this->imageLibrary(), 'path');
        $numberRule = Rule::unique('subchapters', 'nomor_subbab')
            ->where('chapter_id', $chapterId);

        if ($subchapter) {
            $numberRule->ignore($subchapter->id);
        }

        return [
            'nomor_subbab' => [
                'required',
                'integer',
                'min:1',
                $numberRule,
            ],
            'judul' => [
                'required',
                'string',
                'max:255',
            ],
            'konten' => [
                'nullable',
                'string',
            ],
            'content_blocks' => [
                'sometimes',
                'required',
                'json',
                'max:200000',
            ],
            'is_published' => [
                'sometimes',
                'boolean',
            ],
            'image_action' => [
                'sometimes',
                Rule::in(['keep', 'none', 'remove', 'library', 'upload']),
            ],
            'image_file' => [
                'required_if:image_action,upload',
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'image_library_path' => [
                'required_if:image_action,library',
                'nullable',
                'string',
                Rule::in($imagePaths),
            ],
            'image_title' => ['nullable', 'string', 'max:160'],
            'image_caption' => ['nullable', 'string', 'max:500'],
            'video_action' => [
                'sometimes',
                Rule::in(['keep', 'none', 'remove', 'upload', 'library', 'youtube']),
            ],
            'video_file' => [
                'required_if:video_action,upload',
                'nullable',
                'file',
                'mimetypes:video/mp4',
                'mimes:mp4',
                'extensions:mp4',
                'max:30720',
            ],
            'video_library_id' => [
                'required_if:video_action,library',
                'nullable',
                'integer',
            ],
            'youtube_url' => [
                'required_if:video_action,youtube',
                'nullable',
                'string',
                'url',
                'max:2048',
            ],
            'video_title' => ['nullable', 'string', 'max:160'],
            'video_caption' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function validatedContentBlocks(
        Request $request,
        array $data,
        ?Subchapter $subchapter = null
    ): ?array {
        if (!array_key_exists('content_blocks', $data)) {
            return null;
        }

        try {
            $blocks = json_decode(
                $data['content_blocks'],
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                'content_blocks' => 'Susunan blok materi tidak valid.',
            ]);
        }

        $validated = Validator::make(
            ['content_blocks' => $blocks],
            [
                'content_blocks' => ['array', 'max:100'],
                'content_blocks.*' => [
                    'required',
                    'array:type,content,title,media_id,legacy_media_type,source,youtube_url,caption',
                ],
                'content_blocks.*.type' => [
                    'required',
                    Rule::in(['text', 'image', 'video', 'callout']),
                ],
                'content_blocks.*.content' => [
                    'sometimes',
                    'string',
                    'max:50000',
                ],
                'content_blocks.*.title' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:160',
                ],
                'content_blocks.*.media_id' => [
                    'sometimes',
                    'nullable',
                    'integer',
                ],
                'content_blocks.*.legacy_media_type' => [
                    'sometimes',
                    Rule::in(['image', 'video']),
                ],
                'content_blocks.*.source' => [
                    'sometimes',
                    Rule::in(['existing', 'legacy', 'upload', 'youtube']),
                ],
                'content_blocks.*.youtube_url' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'url',
                    'max:2048',
                ],
                'content_blocks.*.caption' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:500',
                ],
            ]
        )->validate()['content_blocks'];

        $seenMedia = [];
        $uploadIndexes = [];
        $youtubeIds = [];

        foreach ($validated as $position => $block) {
            $type = $block['type'];
            $contentRequired = in_array($type, ['text', 'callout'], true);

            if (
                $contentRequired
                && (!isset($block['content']) || trim($block['content']) === '')
            ) {
                throw ValidationException::withMessages([
                    "content_blocks.$position.content" =>
                        'Isi teks blok wajib diisi.',
                ]);
            }

            if (!in_array($type, ['image', 'video'], true)) {
                continue;
            }

            $mediaId = $block['media_id'] ?? null;
            $legacyMediaType = $block['legacy_media_type'] ?? null;
            $source = $block['source'] ?? (
                $mediaId !== null
                    ? 'existing'
                    : ($legacyMediaType !== null ? 'legacy' : null)
            );

            if ($source === 'existing') {
                if ($mediaId === null || $legacyMediaType !== null) {
                    throw ValidationException::withMessages([
                        "content_blocks.$position.media_id" =>
                            'Pilih media yang tersedia di Subbab ini.',
                    ]);
                }

                $media = $subchapter?->media()
                    ->whereKey($mediaId)
                    ->first();

                if (!$media || $media->media_type !== $type) {
                    throw ValidationException::withMessages([
                        "content_blocks.$position.media_id" =>
                            'Media yang dipilih tidak tersedia di Subbab ini.',
                    ]);
                }
            } elseif ($source === 'legacy') {
                if ($mediaId !== null || $legacyMediaType !== $type) {
                    throw ValidationException::withMessages([
                        "content_blocks.$position.media_id" =>
                            'Pilih media yang sesuai untuk blok ini.',
                    ]);
                }
                $mediaId = 'legacy:' . $legacyMediaType;
            } elseif ($source === 'upload') {
                if ($mediaId !== null || $legacyMediaType !== null) {
                    throw ValidationException::withMessages([
                        "content_blocks.$position.media_id" =>
                            'Unggahan baru tidak dapat memakai referensi media lama.',
                    ]);
                }

                $file = $request->file("content_block_files.$position");
                $fileRules = $type === 'image'
                    ? [
                        'required',
                        'file',
                        'image',
                        'mimes:jpg,jpeg,png,webp',
                        'extensions:jpg,jpeg,png,webp',
                        'max:5120',
                    ]
                    : [
                        'required',
                        'file',
                        'mimetypes:video/mp4',
                        'mimes:mp4',
                        'extensions:mp4',
                        'max:30720',
                    ];
                Validator::make(
                    ['file' => $file],
                    ['file' => $fileRules]
                )->validate();
                $uploadIndexes[(string) $position] = true;
            } elseif ($source === 'youtube') {
                if (
                    $type !== 'video'
                    || $mediaId !== null
                    || $legacyMediaType !== null
                    || !isset($block['youtube_url'])
                ) {
                    throw ValidationException::withMessages([
                        "content_blocks.$position.youtube_url" =>
                            'Tautan YouTube hanya berlaku untuk blok video.',
                    ]);
                }

                $youtubeId = $this->youtubeVideoId($block['youtube_url']);
                if ($youtubeId === null) {
                    throw ValidationException::withMessages([
                        "content_blocks.$position.youtube_url" =>
                            'Gunakan URL video YouTube yang valid dan dapat dikenali.',
                    ]);
                }
                if (isset($youtubeIds[$youtubeId])) {
                    throw ValidationException::withMessages([
                        'content_blocks' =>
                            'Video yang sama tidak dapat ditampilkan lebih dari satu kali.',
                    ]);
                }
                $youtubeIds[$youtubeId] = true;
            } else {
                throw ValidationException::withMessages([
                    "content_blocks.$position.media_id" =>
                        'Pilih media yang sesuai untuk blok ini.',
                ]);
            }

            if (
                $source !== 'upload'
                && $source !== 'youtube'
                && isset($seenMedia[$mediaId])
            ) {
                throw ValidationException::withMessages([
                    'content_blocks' =>
                        'Media yang sama tidak dapat ditampilkan lebih dari satu kali.',
                ]);
            }

            if ($source !== 'upload' && $source !== 'youtube') {
                $seenMedia[$mediaId] = true;
            }
        }

        foreach ($request->file('content_block_files', []) as $position => $file) {
            if (!isset($uploadIndexes[(string) $position])) {
                throw ValidationException::withMessages([
                    'content_block_files' =>
                        'Berkas harus dipasangkan dengan blok gambar atau video.',
                ]);
            }
        }

        return $validated;
    }

    private function prepareContentBlockMedia(
        Request $request,
        array $blocks
    ): array {
        $mediaData = [];
        $newPaths = [];

        try {
            foreach ($blocks as $position => $block) {
                if (!in_array($block['type'], ['image', 'video'], true)) {
                    continue;
                }

                $source = $block['source'] ?? (
                    isset($block['media_id'])
                        ? 'existing'
                        : (isset($block['legacy_media_type']) ? 'legacy' : null)
                );
                if (!in_array($source, ['upload', 'youtube'], true)) {
                    continue;
                }

                $media = [
                    'media_type' => $block['type'],
                    'title' => $block['title'] ?? null,
                    'caption' => $block['caption'] ?? null,
                    'source' => $source,
                ];

                if ($source === 'upload') {
                    $file = $request->file("content_block_files.$position");
                    $path = $file instanceof UploadedFile
                        ? $file->storePublicly('subchapter-media', 'public')
                        : false;

                    if (!$path) {
                        throw new RuntimeException(
                            'Berkas blok gagal disimpan. Periksa ruang dan izin storage.'
                        );
                    }

                    $newPaths[] = $path;
                    $media['storage_path'] = $path;
                } else {
                    $youtubeId = $this->youtubeVideoId($block['youtube_url']);
                    if ($youtubeId === null) {
                        throw ValidationException::withMessages([
                            "content_blocks.$position.youtube_url" =>
                                'Gunakan URL video YouTube yang valid dan dapat dikenali.',
                        ]);
                    }
                    $media['youtube_video_id'] = $youtubeId;
                }

                $mediaData[$position] = $media;
            }
        } catch (Throwable $exception) {
            $this->deleteStoredFiles($newPaths);
            throw $exception;
        }

        return [
            'media_data' => $mediaData,
            'new_paths' => $newPaths,
        ];
    }

    private function syncContentBlocks(
        Subchapter $subchapter,
        array $blocks,
        array $mediaData
    ): void {
        $resolvedMedia = [];

        foreach ($blocks as $position => $block) {
            if (!in_array($block['type'], ['image', 'video'], true)) {
                $resolvedMedia[$position] = null;
                continue;
            }

            $media = isset($mediaData[$position])
                ? $subchapter->media()->create($mediaData[$position])
                : (isset($block['media_id'])
                    ? $subchapter->media()->whereKey($block['media_id'])->first()
                    : $subchapter->media()
                        ->where('media_type', $block['legacy_media_type'])
                        ->first());

            if (!$media || $media->media_type !== $block['type']) {
                throw ValidationException::withMessages([
                    'content_blocks' =>
                        'Media untuk salah satu blok tidak tersedia. Periksa kembali pilihan media Subbab.',
                ]);
            }

            if (in_array($media->id, $resolvedMedia, true)) {
                throw ValidationException::withMessages([
                    'content_blocks' =>
                        'Media yang sama tidak dapat ditampilkan lebih dari satu kali.',
                ]);
            }

            $resolvedMedia[$position] = $media->id;
        }

        $subchapter->contentBlocks()->delete();

        foreach ($blocks as $position => $block) {
            $payload = match ($block['type']) {
                'text' => ['content' => $block['content']],
                'callout' => [
                    'title' => $block['title'] ?? null,
                    'content' => $block['content'],
                ],
                'image', 'video' => [
                    'title' => $block['title'] ?? null,
                    'caption' => $block['caption'] ?? null,
                ],
            };

            $subchapter->contentBlocks()->create([
                'type' => $block['type'],
                'position' => $position + 1,
                'payload' => $payload,
                'media_id' => $resolvedMedia[$position],
            ]);
        }

        $subchapter->update(['content_blocks_enabled' => true]);
    }

    private function attachMaterialBlocks(Chapter|Subchapter $subject): void
    {
        if ($subject instanceof Chapter) {
            $subject->subchapters->each(function (Subchapter $subchapter) {
                $blocks = $subchapter->materialBlocks();
                $subchapter->makeHidden('contentBlocks');
                $subchapter->setAttribute('content_blocks', $blocks);
            });

            return;
        }

        $blocks = $subject->materialBlocks();
        $subject->makeHidden('contentBlocks');
        $subject->setAttribute('content_blocks', $blocks);
    }

    private function subchapterData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'nomor_subbab',
            'judul',
            'konten',
            'is_published',
        ]));
    }

    private function prepareMedia(Request $request, array $data): array
    {
        $operations = [];
        $newPaths = [];

        $mediaTypes = array_filter(
            ['image', 'video'],
            fn (string $mediaType) =>
                array_key_exists($mediaType . '_action', $data)
        );

        if ($mediaTypes === []) {
            return [
                'operations' => [],
                'new_paths' => [],
            ];
        }

        if (($data['image_action'] ?? null) === 'library') {
            abort_unless(
                in_array(
                    $data['image_library_path'] ?? null,
                    array_column($this->imageLibrary(), 'path'),
                    true
                ),
                422,
                'Gambar pilihan tidak tersedia di pustaka SALUANG.'
            );
        }

        if (($data['video_action'] ?? null) === 'library') {
            $libraryVideo = SubchapterMedia::query()
                ->whereKey($data['video_library_id'])
                ->where('media_type', 'video')
                ->whereIn('source', ['upload', 'library'])
                ->whereNotNull('storage_path')
                ->first();

            abort_unless(
                $libraryVideo
                    && Storage::disk('public')->exists($libraryVideo->storage_path),
                422,
                'Video pilihan tidak tersedia di pustaka SALUANG.'
            );
        }

        if (($data['video_action'] ?? null) === 'youtube') {
            $youtubeId = $this->youtubeVideoId($data['youtube_url']);

            abort_unless(
                $youtubeId !== null,
                422,
                'Gunakan URL video YouTube yang valid dan dapat dikenali.'
            );
        }

        try {
            foreach ($mediaTypes as $mediaType) {
                $action = $data[$mediaType . '_action'];
                $operation = [
                    'action' => $action,
                    'title' => $data[$mediaType . '_title'] ?? null,
                    'caption' => $data[$mediaType . '_caption'] ?? null,
                ];

                if ($action === 'upload') {
                    $file = $request->file($mediaType . '_file');
                    $path = $file instanceof UploadedFile
                        ? $file->storePublicly('subchapter-media', 'public')
                        : false;

                    if (!$path) {
                        throw new RuntimeException(
                            'Berkas media gagal disimpan. Periksa ruang dan izin storage.'
                        );
                    }

                    $newPaths[] = $path;
                    $operation['source'] = 'upload';
                    $operation['storage_path'] = $path;
                } elseif ($mediaType === 'image' && $action === 'library') {
                    $operation['source'] = 'library';
                    $operation['library_path'] = $data['image_library_path'];
                } elseif ($mediaType === 'video' && $action === 'library') {
                    $operation['source'] = 'library';
                    $operation['storage_path'] = $libraryVideo->storage_path;
                } elseif ($mediaType === 'video' && $action === 'youtube') {
                    $operation['source'] = 'youtube';
                    $operation['youtube_video_id'] = $youtubeId;
                }

                $operations[$mediaType] = $operation;
            }
        } catch (Throwable $exception) {
            $this->deleteStoredFiles($newPaths);
            throw $exception;
        }

        return [
            'operations' => $operations,
            'new_paths' => $newPaths,
        ];
    }

    private function syncMedia(
        Subchapter $subchapter,
        array $operations
    ): array {
        $oldPaths = [];

        foreach ($operations as $mediaType => $operation) {
            $media = $subchapter->media()
                ->where('media_type', $mediaType)
                ->first();

            if ($operation['action'] === 'keep') {
                if ($media) {
                    $media->update([
                        'title' => $operation['title'],
                        'caption' => $operation['caption'],
                    ]);
                }

                continue;
            }

            if ($operation['action'] === 'none') {
                continue;
            }

            if ($media && $media->storage_path) {
                $oldPaths[] = $media->storage_path;
            }

            if ($operation['action'] === 'remove') {
                $media?->delete();
                continue;
            }

            $subchapter->media()->updateOrCreate(
                ['media_type' => $mediaType],
                [
                    'source' => $operation['source'],
                    'storage_path' => $operation['storage_path'] ?? null,
                    'library_path' => $operation['library_path'] ?? null,
                    'youtube_video_id' => $operation['youtube_video_id'] ?? null,
                    'title' => $operation['title'],
                    'caption' => $operation['caption'],
                ]
            );
        }

        return $oldPaths;
    }

    private function imageLibrary(): array
    {
        $files = glob(public_path('images/*'));

        if ($files === false) {
            throw new RuntimeException(
                'Pustaka gambar SALUANG tidak dapat dibaca.'
            );
        }

        $images = [];

        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if (
                !is_file($file)
                || !in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)
            ) {
                continue;
            }

            $path = 'images/' . basename($file);

            $images[] = [
                'path' => $path,
                'url' => asset($path),
                'name' => pathinfo($file, PATHINFO_FILENAME),
            ];
        }

        return $images;
    }

    private function videoLibrary(): array
    {
        return SubchapterMedia::query()
            ->where('media_type', 'video')
            ->whereIn('source', ['upload', 'library'])
            ->whereNotNull('storage_path')
            ->latest('id')
            ->limit(100)
            ->get(['id', 'title', 'storage_path'])
            ->filter(
                fn (SubchapterMedia $media) =>
                    Storage::disk('public')->exists($media->storage_path)
            )
            ->map(fn (SubchapterMedia $media) => [
                'id' => $media->id,
                'title' => $media->title ?: 'Video tersimpan ' . $media->id,
            ])
            ->values()
            ->all();
    }

    private function youtubeVideoId(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (!is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https') {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');
        $videoId = null;

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            if ($path === 'watch') {
                parse_str($parts['query'] ?? '', $query);
                $videoId = $query['v'] ?? null;
            } elseif (preg_match('~^(?:embed|shorts|live)/([^/]+)$~', $path, $matches)) {
                $videoId = $matches[1];
            }
        } elseif (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $videoId = $path;
        }

        return is_string($videoId)
            && preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) === 1
                ? $videoId
                : null;
    }

    private function deleteOrphanedFiles(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (
                !$path
                || SubchapterMedia::query()
                    ->where('storage_path', $path)
                    ->exists()
                || Chapter::query()
                    ->where('gambar_pengenalan_path', $path)
                    ->exists()
            ) {
                continue;
            }

            $disk = Storage::disk('public');

            if ($disk->exists($path) && !$disk->delete($path)) {
                throw new RuntimeException(
                    'Media lama sudah diganti, tetapi berkas lamanya gagal dihapus.'
                );
            }
        }
    }

    private function deleteStoredFiles(array $paths): void
    {
        $disk = Storage::disk('public');

        foreach (array_unique($paths) as $path) {
            if ($path && $disk->exists($path) && !$disk->delete($path)) {
                throw new RuntimeException(
                    'Pembersihan berkas sementara gagal. Periksa izin storage.'
                );
            }
        }
    }
}