<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\SubchapterMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuruSubchapterMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_subchapter_migration_matches_the_verified_schema_and_cascades(): void
    {
        $columns = collect(Schema::getColumns('subchapters'))
            ->keyBy('name');
        $indexes = collect(Schema::getIndexes('subchapters'));
        $foreignKeys = collect(Schema::getForeignKeys('subchapters'));

        $this->assertSame(
            [
                'id',
                'chapter_id',
                'nomor_subbab',
                'judul',
                'konten',
                'is_published',
                'created_at',
                'updated_at',
            ],
            $columns->keys()
                ->reject(fn (string $name) => $name === 'content_blocks_enabled')
                ->values()
                ->all()
        );
        $this->assertArrayHasKey('content_blocks_enabled', $columns);
        $this->assertTrue($columns['konten']['nullable']);
        $this->assertSame(
            '0',
            trim((string) $columns['content_blocks_enabled']['default'], "'\"")
        );
        $this->assertSame(
            '0',
            trim((string) $columns['is_published']['default'], "'\"")
        );
        $this->assertTrue($indexes->contains(
            fn (array $index) =>
                $index['unique']
                && $index['columns'] === ['chapter_id', 'nomor_subbab']
        ));
        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey) =>
                $foreignKey['columns'] === ['chapter_id']
                && $foreignKey['foreign_table'] === 'chapters'
                && $foreignKey['foreign_columns'] === ['id']
                && $foreignKey['on_delete'] === 'cascade'
        ));

        $guru = $this->createUser('guru');
        $chapter = $this->createChapter($guru);
        $subchapter = $chapter->subchapters()->create(
            $this->subchapterPayload()
        );

        $chapter->delete();

        $this->assertDatabaseMissing('subchapters', [
            'id' => $subchapter->id,
        ]);
    }

    public function test_guru_can_upload_an_image_and_save_a_youtube_video(): void
    {
        Storage::fake('public');

        $guru = $this->createUser('guru');
        $chapter = $this->createChapter($guru, true);

        $response = $this->actingAs($guru)
            ->withHeader('Accept', 'application/json')
            ->post(
            "/guru/chapters/{$chapter->id}/subchapters",
            array_merge($this->subchapterPayload(), [
                'image_action' => 'upload',
                'image_file' => UploadedFile::fake()->image('ekosistem.png'),
                'image_title' => 'Ekosistem sungai',
                'image_caption' => 'Ilustrasi sungai.',
                'video_action' => 'youtube',
                'youtube_url' => 'https://youtu.be/abcdefghijk',
                'video_title' => 'Video pengantar',
                'video_caption' => 'Tonton sebelum membaca.',
            ])
        );

        $response->assertCreated();
        $this->assertDatabaseHas('subchapter_media', [
            'media_type' => 'image',
            'source' => 'upload',
            'title' => 'Ekosistem sungai',
        ]);
        $this->assertDatabaseHas('subchapter_media', [
            'media_type' => 'video',
            'source' => 'youtube',
            'youtube_video_id' => 'abcdefghijk',
        ]);

        $image = SubchapterMedia::query()
            ->where('media_type', 'image')
            ->firstOrFail();
        Storage::disk('public')->assertExists($image->storage_path);
    }

    public function test_guru_can_replace_uploaded_video_without_losing_old_file_before_save(): void
    {
        Storage::fake('public');

        $guru = $this->createUser('guru');
        $chapter = $this->createChapter($guru, true);
        $subchapter = $chapter->subchapters()->create(
            $this->subchapterPayload()
        );
        $oldPath = 'subchapter-media/old-video.mp4';
        Storage::disk('public')->put($oldPath, 'old video');

        $subchapter->media()->create([
            'media_type' => 'video',
            'source' => 'upload',
            'storage_path' => $oldPath,
            'title' => 'Video lama',
        ]);

        $invalidReplacement = $this->actingAs($guru)
            ->withHeader('Accept', 'application/json')
            ->post(
            "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
            array_merge($this->subchapterPayload(), [
                '_method' => 'PUT',
                'image_action' => 'none',
                'video_action' => 'upload',
                'video_file' => UploadedFile::fake()->create(
                    'dokumen.pdf',
                    10,
                    'application/pdf'
                ),
            ])
        );

        $invalidReplacement->assertUnprocessable();
        Storage::disk('public')->assertExists($oldPath);

        $replacement = $this->actingAs($guru)
            ->withHeader('Accept', 'application/json')
            ->post(
            "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
            array_merge($this->subchapterPayload(), [
                '_method' => 'PUT',
                'image_action' => 'none',
                'video_action' => 'upload',
                'video_file' => UploadedFile::fake()->create(
                    'video-baru.mp4',
                    100,
                    'video/mp4'
                ),
                'video_title' => 'Video pengganti',
            ])
        );

        $replacement->assertOk();
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertDatabaseHas('subchapter_media', [
            'subchapter_id' => $subchapter->id,
            'media_type' => 'video',
            'title' => 'Video pengganti',
        ]);
    }

    public function test_guru_can_remove_media_from_owned_subchapter(): void
    {
        Storage::fake('public');

        $guru = $this->createUser('guru');
        $chapter = $this->createChapter($guru, true);
        $subchapter = $chapter->subchapters()->create(
            $this->subchapterPayload()
        );
        $imagePath = 'subchapter-media/gambar-lama.png';
        Storage::disk('public')->put($imagePath, 'image');

        $subchapter->media()->create([
            'media_type' => 'image',
            'source' => 'upload',
            'storage_path' => $imagePath,
            'title' => 'Gambar lama',
        ]);

        $response = $this->actingAs($guru)
            ->post(
                "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
                array_merge($this->subchapterPayload(), [
                    '_method' => 'PUT',
                    'image_action' => 'remove',
                    'video_action' => 'none',
                ])
            );

        $response->assertOk();
        $this->assertDatabaseMissing('subchapter_media', [
            'subchapter_id' => $subchapter->id,
            'media_type' => 'image',
        ]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_guru_cannot_upload_an_unsupported_image_or_video(): void
    {
        Storage::fake('public');

        $guru = $this->createUser('guru');
        $chapter = $this->createChapter($guru);

        $response = $this->actingAs($guru)
            ->withHeader('Accept', 'application/json')
            ->post(
            "/guru/chapters/{$chapter->id}/subchapters",
            array_merge($this->subchapterPayload(), [
                'image_action' => 'upload',
                'image_file' => UploadedFile::fake()->create(
                    'vektor.svg',
                    5,
                    'image/svg+xml'
                ),
                'video_action' => 'upload',
                'video_file' => UploadedFile::fake()->create(
                    'video.webm',
                    20,
                    'video/webm'
                ),
            ])
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'image_file',
                'video_file',
            ]);
        $this->assertDatabaseCount('subchapters', 0);
        $this->assertDatabaseCount('subchapter_media', 0);
    }

    public function test_guru_cannot_use_a_non_youtube_url(): void
    {
        $guru = $this->createUser('guru');
        $chapter = $this->createChapter($guru);

        $response = $this->actingAs($guru)->postJson(
            "/guru/chapters/{$chapter->id}/subchapters",
            array_merge($this->subchapterPayload(), [
                'image_action' => 'none',
                'video_action' => 'youtube',
                'youtube_url' => 'https://example.com/watch?v=abcdefghijk',
            ])
        );

        $response->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Gunakan URL video YouTube yang valid dan dapat dikenali.'
            );
        $this->assertDatabaseCount('subchapters', 0);
    }

    public function test_guru_can_save_standard_short_and_shorts_youtube_urls(): void
    {
        $guru = $this->createUser('guru');
        $chapter = $this->createChapter($guru);
        $subchapter = $chapter->subchapters()->create(
            $this->subchapterPayload()
        );

        foreach ([
            'https://www.youtube.com/watch?v=abcdefghijk',
            'https://youtu.be/abcdefghijk',
            'https://www.youtube.com/shorts/abcdefghijk',
        ] as $youtubeUrl) {
            $response = $this->actingAs($guru)->putJson(
                "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
                array_merge($this->subchapterPayload(), [
                    'image_action' => 'none',
                    'video_action' => 'youtube',
                    'youtube_url' => $youtubeUrl,
                ])
            );

            $response->assertOk();
            $this->assertDatabaseHas('subchapter_media', [
                'subchapter_id' => $subchapter->id,
                'media_type' => 'video',
                'source' => 'youtube',
                'youtube_video_id' => 'abcdefghijk',
            ]);
        }
    }

    public function test_only_published_subchapter_media_is_returned_to_students(): void
    {
        Storage::fake('public');

        $guru = $this->createUser('guru');
        $student = $this->createUser('siswa');
        $publishedChapter = $this->createChapter($guru, true);
        $publishedSubchapter = $publishedChapter->subchapters()->create(
            array_merge($this->subchapterPayload(), [
                'is_published' => true,
            ])
        );
        $publishedSubchapter->media()->create([
            'media_type' => 'video',
            'source' => 'youtube',
            'youtube_video_id' => 'abcdefghijk',
            'title' => 'Video publik',
        ]);

        $draftChapter = $this->createChapter($guru, false, 2);
        $draftSubchapter = $draftChapter->subchapters()->create(
            array_merge($this->subchapterPayload(2), [
                'is_published' => false,
            ])
        );
        $draftSubchapter->media()->create([
            'media_type' => 'video',
            'source' => 'youtube',
            'youtube_video_id' => 'lmnopqrstuv',
            'title' => 'Video draft',
        ]);

        $response = $this->actingAs($student)->getJson('/siswa/materials');

        $response->assertOk()
            ->assertJsonPath(
                'chapters.0.subchapters.0.media.0.embed_url',
                'https://www.youtube-nocookie.com/embed/abcdefghijk'
            )
            ->assertJsonMissing(['title' => 'Video draft'])
            ->assertJsonMissingPath(
                'chapters.0.subchapters.0.media.0.youtube_video_id'
            );
    }

    public function test_guru_cannot_edit_another_gurus_subchapter_media(): void
    {
        $owner = $this->createUser('guru', 'guru-pemilik');
        $otherGuru = $this->createUser('guru', 'guru-lain');
        $chapter = $this->createChapter($owner);
        $subchapter = $chapter->subchapters()->create(
            $this->subchapterPayload()
        );

        $response = $this->actingAs($otherGuru)->post(
            "/guru/chapters/{$chapter->id}/subchapters/{$subchapter->id}",
            array_merge($this->subchapterPayload(), [
                '_method' => 'PUT',
                'image_action' => 'none',
                'video_action' => 'none',
            ])
        );

        $response->assertNotFound();
        $this->assertDatabaseCount('subchapter_media', 0);
    }

    private function createUser(string $role, string $username = ''): User
    {
        static $userNumber = 0;
        $userNumber++;

        return User::create([
            'name' => ucfirst($role) . ' SALUANG',
            'username' => $username ?: "{$role}-media-{$userNumber}",
            'password' => 'secret123',
            'role' => $role,
            'email' => null,
        ]);
    }

    private function createChapter(
        User $guru,
        bool $published = false,
        int $number = 1
    ): Chapter {
        return Chapter::create([
            'user_id' => $guru->id,
            'nomor_bab' => $number,
            'judul' => 'Materi IPA',
            'deskripsi' => 'Materi kelas VII.',
            'pengantar' => 'Pengantar Bab.',
            'tujuan_pembelajaran' => ['Memahami materi.'],
            'pertanyaan_pemantik' => 'Apa yang kamu ketahui?',
            'is_published' => $published,
        ]);
    }

    private function subchapterPayload(int $number = 1): array
    {
        return [
            'nomor_subbab' => $number,
            'judul' => 'Subbab IPA',
            'konten' => '<p>Isi materi dari Guru.</p>',
            'is_published' => false,
        ];
    }
}
