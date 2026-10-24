<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Subchapter;
use App\Models\SubchapterMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubchapterContentBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_subchapter_form_only_exposes_content_block_media_controls(): void
    {
        $guru = $this->createUser('guru');

        $response = $this->actingAs($guru)->get('/dashboard');

        $response->assertOk()
            ->assertSee('gmContentBlockType')
            ->assertSee('gmBlockMediaSource')
            ->assertSee('gmBlockMediaFile')
            ->assertSee('gmBlockYoutubeUrl')
            ->assertDontSee('Media Pembelajaran')
            ->assertDontSee('gmImageActionChoice')
            ->assertDontSee('gmVideoActionChoice');
    }

    public function test_content_block_migration_has_ordered_cascading_relations(): void
    {
        $columns = collect(Schema::getColumns('subchapter_content_blocks'))
            ->keyBy('name');
        $subchapterColumns = collect(Schema::getColumns('subchapters'))
            ->keyBy('name');
        $indexes = collect(Schema::getIndexes('subchapter_content_blocks'));
        $foreignKeys = collect(Schema::getForeignKeys('subchapter_content_blocks'));
        $mediaIndexes = collect(Schema::getIndexes('subchapter_media'));

        $this->assertSame(
            [
                'id',
                'subchapter_id',
                'type',
                'position',
                'payload',
                'media_id',
                'created_at',
                'updated_at',
            ],
            $columns->keys()->all()
        );
        $this->assertTrue($columns['payload']['nullable']);
        $this->assertTrue($indexes->contains(
            fn (array $index) =>
                $index['unique']
                && $index['columns'] === ['subchapter_id', 'position']
        ));
        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey) =>
                $foreignKey['columns'] === ['subchapter_id']
                && $foreignKey['foreign_table'] === 'subchapters'
                && $foreignKey['on_delete'] === 'cascade'
        ));
        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey) =>
                $foreignKey['columns'] === ['media_id']
                && $foreignKey['foreign_table'] === 'subchapter_media'
                && $foreignKey['on_delete'] === 'set null'
        ));
        $this->assertSame(
            '0',
            trim(
                (string) $subchapterColumns['content_blocks_enabled']['default'],
                "'\""
            )
        );
        $this->assertTrue($mediaIndexes->contains(
            fn (array $index) =>
                !$index['unique']
                && $index['columns'] === ['subchapter_id', 'media_type']
        ));
        $this->assertFalse($mediaIndexes->contains(
            fn (array $index) =>
                $index['unique']
                && $index['columns'] === ['subchapter_id', 'media_type']
        ));

        $subchapter = $this->createChapter($this->createUser('guru'))
            ->subchapters()
            ->create([
                'nomor_subbab' => 1,
                'judul' => 'Subbab migration',
            ]);
        $subchapter->contentBlocks()->create([
            'type' => 'text',
            'position' => 1,
            'payload' => ['content' => 'Jangan dihapus saat rollback.'],
        ]);

        $migration = require database_path(
            'migrations/2026_10_22_150800_create_subchapter_content_blocks_table.php'
        );
        try {
            $migration->down();
            $this->fail('Rollback must not discard saved content blocks.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString(
                'Export or remove content blocks',
                $exception->getMessage()
            );
        }
        $this->assertTrue(Schema::hasTable('subchapter_content_blocks'));
        $this->assertTrue(
            Schema::hasColumn('subchapters', 'content_blocks_enabled')
        );
    }

    public function test_legacy_content_is_projected_as_virtual_blocks_without_writes(): void
    {
        $guru = $this->createUser('guru');
        $student = $this->createUser('siswa');
        $chapter = $this->createChapter($guru, true);
        $subchapter = $chapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab lama',
            'konten' => '<p>Materi lama tetap disimpan.</p>',
            'is_published' => true,
        ]);
        $subchapter->media()->create([
            'media_type' => 'image',
            'source' => 'library',
            'library_path' => 'images/legacy.png',
            'title' => 'Gambar lama',
        ]);
        $subchapter->media()->create([
            'media_type' => 'video',
            'source' => 'youtube',
            'youtube_video_id' => 'abcdefghijk',
            'title' => 'Video lama',
        ]);

        $this->actingAs($student)
            ->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.0.type',
                'text'
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.0.payload.content',
                '<p>Materi lama tetap disimpan.</p>'
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.1.type',
                'image'
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.2.type',
                'video'
            );

        $this->assertDatabaseHas('subchapters', [
            'id' => $subchapter->id,
            'konten' => '<p>Materi lama tetap disimpan.</p>',
            'content_blocks_enabled' => false,
        ]);
        $this->assertDatabaseCount('subchapter_content_blocks', 0);
        $this->assertDatabaseCount('subchapter_media', 2);
    }

    public function test_guru_can_save_ordered_blocks_without_changing_legacy_data(): void
    {
        $guru = $this->createUser('guru');
        $student = $this->createUser('siswa');
        $chapter = $this->createChapter($guru, true);
        $subchapter = $chapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab campuran',
            'konten' => '<p>Konten legacy tidak diubah.</p>',
            'is_published' => true,
        ]);
        $image = $subchapter->media()->create([
            'media_type' => 'image',
            'source' => 'library',
            'library_path' => 'images/lesson.png',
            'title' => 'Ilustrasi',
        ]);
        $video = $subchapter->media()->create([
            'media_type' => 'video',
            'source' => 'youtube',
            'youtube_video_id' => 'abcdefghijk',
            'title' => 'Video pembelajaran',
        ]);

        $response = $this->actingAs($guru)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
                $this->subchapterPayload([
                    ['type' => 'text', 'content' => '<p>Pembuka</p>'],
                    ['type' => 'image', 'media_id' => $image->id],
                    ['type' => 'video', 'media_id' => $video->id],
                    ['type' => 'callout', 'title' => 'Kesimpulan', 'content' => 'Ringkasan akhir.'],
                    ['type' => 'text', 'content' => '<p>Penutup</p>'],
                ], true)
            );

        $response->assertOk()
            ->assertJsonPath('subchapter.content_blocks_enabled', true)
            ->assertJsonPath('subchapter.content_blocks.0.type', 'text')
            ->assertJsonPath('subchapter.content_blocks.1.type', 'image')
            ->assertJsonPath('subchapter.content_blocks.2.type', 'video')
            ->assertJsonPath('subchapter.content_blocks.3.type', 'callout')
            ->assertJsonPath('subchapter.content_blocks.4.type', 'text');

        $this->assertDatabaseHas('subchapters', [
            'id' => $subchapter->id,
            'konten' => '<p>Konten legacy tidak diubah.</p>',
            'content_blocks_enabled' => true,
        ]);
        $this->assertDatabaseCount('subchapter_media', 2);
        $this->assertDatabaseCount('subchapter_content_blocks', 5);

        $this->actingAs($student)
            ->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.0.payload.content',
                '<p>Pembuka</p>'
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.1.media.id',
                $image->id
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.2.media.embed_url',
                'https://www.youtube-nocookie.com/embed/abcdefghijk'
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.3.payload.title',
                'Kesimpulan'
            )
            ->assertJsonMissingPath(
                'chapters.0.subchapters.0.content_blocks.1.media.storage_path'
            )
            ->assertJsonMissingPath(
                'chapters.0.subchapters.0.content_blocks.2.media.youtube_video_id'
            )
            ->assertJsonCount(
                5,
                'chapters.0.subchapters.0.content_blocks'
            );
    }

    public function test_adjacent_text_blocks_remain_independent_in_guru_and_student_feeds(): void
    {
        $guru = $this->createUser('guru');
        $student = $this->createUser('siswa');
        $chapter = $this->createChapter($guru, true);
        $subchapter = $chapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Pengaruh lingkungan',
            'is_published' => true,
            'content_blocks_enabled' => true,
        ]);
        $contents = [
            '<p><strong>Ayo Mengamati!</strong></p><p>Amati lingkungan rawa.</p>',
            '<h2>Pengaruh Lingkungan terhadap Kehidupan Organisme</h2><p>Lingkungan berada di sekitar makhluk hidup.</p>',
            '<h3>1. Komponen Lingkungan Abiotik</h3><p>Komponen abiotik adalah unsur tak hidup.</p>',
        ];

        foreach ($contents as $position => $content) {
            $subchapter->contentBlocks()->create([
                'type' => 'text',
                'position' => $position + 1,
                'payload' => ['content' => $content],
            ]);
        }

        $this->actingAs($guru)
            ->getJson('/guru/chapters')
            ->assertOk()
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.0.payload.content',
                $contents[0]
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.1.payload.content',
                $contents[1]
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.2.payload.content',
                $contents[2]
            );

        $this->actingAs($student)
            ->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.0.payload.content',
                $contents[0]
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.1.payload.content',
                $contents[1]
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.2.payload.content',
                $contents[2]
            )
            ->assertJsonCount(
                3,
                'chapters.0.subchapters.0.content_blocks'
            );
    }

    public function test_removing_all_blocks_keeps_block_mode_and_legacy_media(): void
    {
        $guru = $this->createUser('guru');
        $student = $this->createUser('siswa');
        $chapter = $this->createChapter($guru, true);
        $subchapter = $chapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab kosong',
            'konten' => '<p>Jangan tampilkan kembali.</p>',
            'is_published' => true,
            'content_blocks_enabled' => true,
        ]);
        $subchapter->media()->create([
            'media_type' => 'image',
            'source' => 'library',
            'library_path' => 'images/retained.png',
            'title' => 'Media tetap ada',
        ]);
        $subchapter->contentBlocks()->create([
            'type' => 'text',
            'position' => 1,
            'payload' => ['content' => '<p>Blok lama.</p>'],
        ]);

        $this->actingAs($guru)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
                $this->subchapterPayload([], true)
            )
            ->assertOk()
            ->assertJsonCount(0, 'subchapter.content_blocks');

        $this->assertDatabaseHas('subchapters', [
            'id' => $subchapter->id,
            'content_blocks_enabled' => true,
            'konten' => '<p>Jangan tampilkan kembali.</p>',
        ]);
        $this->assertDatabaseCount('subchapter_content_blocks', 0);
        $this->assertDatabaseCount('subchapter_media', 1);

        $this->actingAs($student)
            ->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonCount(
                0,
                'chapters.0.subchapters.0.content_blocks'
            );
    }

    public function test_editing_blocks_retains_existing_media_and_uses_the_same_public_url(): void
    {
        Storage::fake('public');

        $guru = $this->createUser('guru');
        $student = $this->createUser('siswa');
        $chapter = $this->createChapter($guru, true);
        $subchapter = $chapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab media tersimpan',
            'is_published' => true,
            'content_blocks_enabled' => true,
        ]);
        $path = 'subchapter-media/gambar-tersimpan.jpg';
        Storage::disk('public')->put($path, 'saved image bytes');
        $media = $subchapter->media()->create([
            'media_type' => 'image',
            'source' => 'upload',
            'storage_path' => $path,
            'title' => 'Ilustrasi tersimpan',
            'caption' => 'Keterangan gambar.',
        ]);
        $subchapter->contentBlocks()->create([
            'type' => 'image',
            'position' => 1,
            'payload' => [
                'title' => 'Ilustrasi tersimpan',
                'caption' => 'Keterangan gambar.',
            ],
            'media_id' => $media->id,
        ]);

        $updated = $this->actingAs($guru)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
                array_merge(
                    $this->subchapterPayload([
                        ['type' => 'text', 'content' => '<p>Materi diperbarui.</p>'],
                        ['type' => 'image', 'media_id' => $media->id],
                    ], true),
                    [
                        'image_title' => 'Ilustrasi tersimpan',
                        'image_caption' => 'Keterangan gambar.',
                    ]
                )
            );

        $updated->assertOk()
            ->assertJsonPath(
                'subchapter.content_blocks.1.media_id',
                $media->id
            )
            ->assertJsonPath(
                'subchapter.content_blocks.1.media.url',
                Storage::disk('public')->url($path)
            );

        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('subchapter_content_blocks', [
            'subchapter_id' => $subchapter->id,
            'position' => 2,
            'media_id' => $media->id,
        ]);

        $studentResponse = $this->actingAs($student)
            ->getJson('/siswa/materials')
            ->assertOk();

        $studentResponse->assertJsonPath(
            'chapters.0.subchapters.0.content_blocks.1.media.url',
            Storage::disk('public')->url($path)
        );
        $this->assertFalse(
            str_starts_with(
                $studentResponse->json(
                    'chapters.0.subchapters.0.content_blocks.1.media.url'
                ),
                'blob:'
            )
        );
    }

    public function test_block_only_edit_preserves_unreferenced_legacy_media(): void
    {
        Storage::fake('public');

        $guru = $this->createUser('guru');
        $student = $this->createUser('siswa');
        $chapter = $this->createChapter($guru, true);
        $subchapter = $chapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab dengan media lama',
            'is_published' => true,
            'content_blocks_enabled' => true,
        ]);
        $path = 'subchapter-media/media-lama.png';
        Storage::disk('public')->put($path, 'existing image');
        $media = $subchapter->media()->create([
            'media_type' => 'image',
            'source' => 'upload',
            'storage_path' => $path,
            'title' => 'Gambar lama',
        ]);

        $payload = $this->subchapterPayload([
            ['type' => 'text', 'content' => '<p>Konten baru.</p>'],
            ['type' => 'image', 'media_id' => $media->id],
        ], true);
        unset($payload['image_action'], $payload['video_action']);

        $response = $this->actingAs($guru)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
                $payload
            );

        $response->assertOk()
            ->assertJsonPath('subchapter.content_blocks.1.media_id', $media->id)
            ->assertJsonPath(
                'subchapter.content_blocks.1.media.url',
                Storage::disk('public')->url($path)
            );
        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('subchapter_media', [
            'id' => $media->id,
            'storage_path' => $path,
        ]);

        $this->actingAs($student)
            ->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.1.media.url',
                Storage::disk('public')->url($path)
            );
    }

    public function test_media_blocks_can_upload_more_than_one_asset_and_deleting_blocks_keeps_files(): void
    {
        Storage::fake('public');

        $guru = $this->createUser('guru');
        $chapter = $this->createChapter($guru);
        $subchapter = $chapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab dengan beberapa gambar',
            'konten' => '<p>Konten lama.</p>',
        ]);

        $response = $this->actingAs($guru)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
                array_merge(
                    $this->subchapterPayload([
                        [
                            'type' => 'image',
                            'source' => 'upload',
                            'title' => 'Gambar pertama',
                        ],
                        [
                            'type' => 'image',
                            'source' => 'upload',
                            'title' => 'Gambar kedua',
                        ],
                        [
                            'type' => 'video',
                            'source' => 'youtube',
                            'youtube_url' => 'https://youtu.be/abcdefghijk',
                        ],
                    ], true),
                    [
                        'content_block_files' => [
                            0 => UploadedFile::fake()->image('pertama.png'),
                            1 => UploadedFile::fake()->image('kedua.png'),
                        ],
                    ]
                )
            );

        $response->assertOk()
            ->assertJsonPath('subchapter.content_blocks.0.type', 'image')
            ->assertJsonPath('subchapter.content_blocks.1.type', 'image')
            ->assertJsonPath('subchapter.content_blocks.2.type', 'video');
        $this->assertDatabaseCount('subchapter_media', 3);

        $storedPaths = SubchapterMedia::query()
            ->where('media_type', 'image')
            ->pluck('storage_path')
            ->all();
        foreach ($storedPaths as $path) {
            Storage::disk('public')->assertExists($path);
        }

        $this->post(
            "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
            $this->subchapterPayload([], true)
        )->assertOk();

        $this->assertDatabaseCount('subchapter_content_blocks', 0);
        $this->assertDatabaseCount('subchapter_media', 3);
        foreach ($storedPaths as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_invalid_block_types_and_repeated_media_are_rejected(): void
    {
        $guru = $this->createUser('guru');
        $chapter = $this->createChapter($guru);
        $subchapter = $chapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab validasi',
            'konten' => '<p>Lama</p>',
        ]);
        $image = $subchapter->media()->create([
            'media_type' => 'image',
            'source' => 'library',
            'library_path' => 'images/one.png',
            'title' => 'Satu gambar',
        ]);

        $invalidType = $this->actingAs($guru)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
                $this->subchapterPayload([
                    ['type' => 'model3d', 'content' => 'belum tersedia'],
                ], true)
            );
        $invalidType->assertUnprocessable();

        $missingText = $this->post(
            "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
            $this->subchapterPayload([
                ['type' => 'text', 'content' => '   '],
            ], true)
        );
        $missingText->assertUnprocessable();

        $repeatedMedia = $this->post(
            "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
            $this->subchapterPayload([
                ['type' => 'image', 'media_id' => $image->id],
                ['type' => 'image', 'media_id' => $image->id],
            ], true)
        );
        $repeatedMedia->assertUnprocessable();
        $this->assertDatabaseCount('subchapter_content_blocks', 0);
        $this->assertDatabaseCount('subchapter_media', 1);
    }

    public function test_draft_chapters_and_subchapters_do_not_publish_their_blocks(): void
    {
        $guru = $this->createUser('guru');
        $student = $this->createUser('siswa');
        $publishedChapter = $this->createChapter($guru, true);
        $publishedSubchapter = $publishedChapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab terbit',
            'is_published' => true,
            'content_blocks_enabled' => true,
        ]);
        $publishedSubchapter->contentBlocks()->create([
            'type' => 'text',
            'position' => 1,
            'payload' => ['content' => 'Materi untuk siswa.'],
        ]);

        $draftChapter = Chapter::create([
            'user_id' => $guru->id,
            'nomor_bab' => 2,
            'judul' => 'Bab draft',
            'deskripsi' => 'Belum dipublikasikan.',
            'is_published' => false,
        ]);
        $draftSubchapter = $draftChapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab draft',
            'is_published' => true,
            'content_blocks_enabled' => true,
        ]);
        $draftSubchapter->contentBlocks()->create([
            'type' => 'text',
            'position' => 1,
            'payload' => ['content' => 'Draft tidak boleh muncul.'],
        ]);

        $this->actingAs($student)
            ->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonCount(1, 'chapters')
            ->assertJsonCount(1, 'chapters.0.subchapters')
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.0.payload.content',
                'Materi untuk siswa.'
            )
            ->assertJsonMissing(['content' => 'Draft tidak boleh muncul.']);
    }

    public function test_guru_cannot_edit_another_gurus_subchapter_blocks(): void
    {
        $owner = $this->createUser('guru');
        $otherGuru = $this->createUser('guru');
        $chapter = $this->createChapter($owner);
        $subchapter = $chapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab privat',
        ]);

        $this->actingAs($otherGuru)
            ->withHeader('Accept', 'application/json')
            ->post(
                "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
                $this->subchapterPayload([
                    ['type' => 'text', 'content' => 'Tidak boleh disimpan.'],
                ], true)
            )
            ->assertNotFound();

        $this->assertDatabaseCount('subchapter_content_blocks', 0);
    }

    private function createUser(string $role): User
    {
        static $userNumber = 0;
        $userNumber++;

        return User::create([
            'name' => ucfirst($role) . ' SALUANG',
            'username' => "{$role}-blocks-{$userNumber}",
            'password' => 'secret123',
            'role' => $role,
            'email' => null,
        ]);
    }

    private function createChapter(
        User $guru,
        bool $published = false
    ): Chapter {
        return Chapter::create([
            'user_id' => $guru->id,
            'nomor_bab' => 1,
            'judul' => 'Materi IPA',
            'deskripsi' => 'Materi kelas VII.',
            'pengantar' => 'Pengantar Bab.',
            'tujuan_pembelajaran' => ['Memahami materi.'],
            'pertanyaan_pemantik' => 'Apa yang kamu ketahui?',
            'is_published' => $published,
        ]);
    }

    private function subchapterPayload(
        array $blocks,
        bool $withBlocks = false
    ): array {
        $payload = [
            '_method' => 'PUT',
            'nomor_subbab' => 1,
            'judul' => 'Subbab IPA',
            'konten' => '<p>Kolom legacy tidak ditimpa.</p>',
            'is_published' => true,
            'image_action' => 'keep',
            'video_action' => 'none',
        ];

        if ($withBlocks) {
            $payload['content_blocks'] = json_encode(
                $blocks,
                JSON_THROW_ON_ERROR
            );
        }

        return $payload;
    }
}
