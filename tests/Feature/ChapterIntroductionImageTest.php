<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChapterIntroductionImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_chapter_image_is_shared_with_students_and_preserved_or_replaced_safely(): void
    {
        Storage::fake('public');
        $guru = $this->createUser('guru', 'guru-image');
        $siswa = $this->createUser('siswa', 'siswa-image');
        $payload = [
            'nomor_bab' => 6,
            'judul' => 'Ekologi dan Pelestarian Lingkungan',
            'deskripsi' => 'Pengenalan ekologi.',
            'gambar_pengenalan_judul' => 'Ekosistem Lahan Basah',
            'gambar_pengenalan_keterangan' => 'Ilustrasi flora dan fauna.',
            'tujuan_pembelajaran' => ['Memahami ekologi.'],
            'pertanyaan_pemantik' => 'Bagaimana lingkungan dijaga?',
            'is_published' => '1',
            'introduction_image' => UploadedFile::fake()
                ->image('bab-enam.png')
                ->size(120),
        ];

        $created = $this->actingAs($guru)->post('/guru/chapters', $payload);
        $created->assertCreated();
        $chapterId = $created->json('chapter.id');
        $firstPath = Chapter::findOrFail($chapterId)->gambar_pengenalan_path;
        Storage::disk('public')->assertExists($firstPath);
        $firstUrl = Storage::disk('public')->url($firstPath);
        $created->assertJsonPath('chapter.gambar_pengenalan_url', $firstUrl);
        $created->assertJsonPath(
            'chapter.gambar_pengenalan_judul',
            'Ekosistem Lahan Basah'
        );
        $created->assertJsonPath(
            'chapter.gambar_pengenalan_keterangan',
            'Ilustrasi flora dan fauna.'
        );

        $this->actingAs($guru)->getJson('/guru/chapters')
            ->assertOk()
            ->assertJsonPath(
                'chapters.0.gambar_pengenalan_url',
                $firstUrl
            )
            ->assertJsonPath(
                'chapters.0.gambar_pengenalan_judul',
                'Ekosistem Lahan Basah'
            )
            ->assertJsonPath(
                'chapters.0.gambar_pengenalan_keterangan',
                'Ilustrasi flora dan fauna.'
            );
        $this->actingAs($guru)->getJson('/guru/chapters/' . $chapterId)
            ->assertOk()
            ->assertJsonPath('chapter.gambar_pengenalan_url', $firstUrl);

        $this->actingAs($siswa)->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonPath(
                'chapters.0.gambar_pengenalan_url',
                $firstUrl
            )
            ->assertJsonPath(
                'chapters.0.gambar_pengenalan_judul',
                'Ekosistem Lahan Basah'
            )
            ->assertJsonPath(
                'chapters.0.gambar_pengenalan_keterangan',
                'Ilustrasi flora dan fauna.'
            )
            ->assertJsonMissingPath('chapters.0.gambar_pengenalan_path');

        $this->actingAs($guru)->postJson(
            '/guru/chapters/' . $chapterId,
            [
                '_method' => 'PUT',
                'nomor_bab' => 6,
                'judul' => 'Ekologi dan Pelestarian Lingkungan',
                'deskripsi' => 'Deskripsi diperbarui.',
                'gambar_pengenalan_judul' => 'Judul hasil edit',
                'gambar_pengenalan_keterangan' => 'Keterangan hasil edit.',
                'tujuan_pembelajaran' => ['Memahami ekologi.'],
                'pertanyaan_pemantik' => 'Bagaimana lingkungan dijaga?',
                'is_published' => '1',
            ]
        )->assertOk()
            ->assertJsonPath('chapter.gambar_pengenalan_url', $firstUrl)
            ->assertJsonPath(
                'chapter.gambar_pengenalan_judul',
                'Judul hasil edit'
            )
            ->assertJsonPath(
                'chapter.gambar_pengenalan_keterangan',
                'Keterangan hasil edit.'
            );
        Storage::disk('public')->assertExists($firstPath);
        $this->assertDatabaseHas('chapters', [
            'id' => $chapterId,
            'gambar_pengenalan_judul' => 'Judul hasil edit',
            'gambar_pengenalan_keterangan' => 'Keterangan hasil edit.',
        ]);

        $this->actingAs($siswa)->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonPath(
                'chapters.0.gambar_pengenalan_judul',
                'Judul hasil edit'
            )
            ->assertJsonPath(
                'chapters.0.gambar_pengenalan_keterangan',
                'Keterangan hasil edit.'
            );

        $replacement = UploadedFile::fake()
            ->image('bab-enam-baru.webp')
            ->size(150);
        $updated = $this->actingAs($guru)->post(
            '/guru/chapters/' . $chapterId,
            [
                '_method' => 'PUT',
                'nomor_bab' => 6,
                'judul' => 'Ekologi dan Pelestarian Lingkungan',
                'deskripsi' => 'Deskripsi diperbarui.',
                'tujuan_pembelajaran' => ['Memahami ekologi.'],
                'pertanyaan_pemantik' => 'Bagaimana lingkungan dijaga?',
                'is_published' => '1',
                'introduction_image' => $replacement,
            ]
        );
        $updated->assertOk();
        $secondPath = Chapter::findOrFail($chapterId)->gambar_pengenalan_path;
        Storage::disk('public')->assertExists($secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        $this->assertDatabaseHas('chapters', [
            'id' => $chapterId,
            'gambar_pengenalan_judul' => 'Judul hasil edit',
            'gambar_pengenalan_keterangan' => 'Keterangan hasil edit.',
        ]);
        $secondUrl = Storage::disk('public')->url($secondPath);

        $this->actingAs($siswa)->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonPath(
                'chapters.0.gambar_pengenalan_url',
                $secondUrl
            )
            ->assertJsonPath(
                'chapters.0.gambar_pengenalan_judul',
                'Judul hasil edit'
            )
            ->assertJsonPath(
                'chapters.0.gambar_pengenalan_keterangan',
                'Keterangan hasil edit.'
            );
    }

    public function test_chapter_image_is_optional_and_file_validation_enforces_format_and_size(): void
    {
        Storage::fake('public');
        $guru = $this->createUser('guru', 'guru-validation');
        $payload = [
            'nomor_bab' => 1,
            'judul' => 'Bab Tanpa Gambar',
            'deskripsi' => 'Deskripsi.',
            'is_published' => '0',
        ];

        $this->actingAs($guru)->post('/guru/chapters', $payload)
            ->assertCreated()
            ->assertJsonPath('chapter.gambar_pengenalan_url', null)
            ->assertJsonPath('chapter.gambar_pengenalan_judul', null)
            ->assertJsonPath('chapter.gambar_pengenalan_keterangan', null);

        $this->actingAs($guru)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(
            '/guru/chapters',
            array_merge($payload, [
                'nomor_bab' => 2,
                'introduction_image' => UploadedFile::fake()
                    ->create('not-an-image.pdf', 10, 'application/pdf'),
            ])
        )->assertUnprocessable()
            ->assertJsonValidationErrors('introduction_image');

        $this->actingAs($guru)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(
            '/guru/chapters',
            array_merge($payload, [
                'nomor_bab' => 3,
                'introduction_image' => UploadedFile::fake()
                    ->image('too-large.jpg')
                    ->size(4097),
            ])
        )->assertUnprocessable()
            ->assertJsonValidationErrors('introduction_image');
    }

    public function test_explicit_image_removal_preserves_files_still_referenced_by_another_chapter(): void
    {
        Storage::fake('public');
        $guru = $this->createUser('guru', 'guru-shared-image');
        $image = UploadedFile::fake()->image('shared.png');
        $created = $this->actingAs($guru)->post('/guru/chapters', [
            'nomor_bab' => 1,
            'judul' => 'Bab Pertama',
            'is_published' => '0',
            'introduction_image' => $image,
        ]);
        $created->assertCreated();

        $firstChapter = Chapter::findOrFail($created->json('chapter.id'));
        $secondChapter = Chapter::create([
            'user_id' => $guru->id,
            'nomor_bab' => 2,
            'judul' => 'Bab Kedua',
            'gambar_pengenalan_path' => $firstChapter->gambar_pengenalan_path,
            'is_published' => false,
        ]);

        $this->actingAs($guru)->post(
            '/guru/chapters/' . $firstChapter->id,
            [
                '_method' => 'PUT',
                'nomor_bab' => 1,
                'judul' => 'Bab Pertama',
                'is_published' => '0',
                'remove_introduction_image' => '1',
            ]
        )->assertOk()
            ->assertJsonPath('chapter.gambar_pengenalan_url', null);
        Storage::disk('public')->assertExists(
            $secondChapter->gambar_pengenalan_path
        );

        $this->actingAs($guru)->post(
            '/guru/chapters/' . $secondChapter->id,
            [
                '_method' => 'PUT',
                'nomor_bab' => 2,
                'judul' => 'Bab Kedua',
                'is_published' => '0',
                'remove_introduction_image' => '1',
            ]
        )->assertOk();
        Storage::disk('public')->assertMissing(
            $secondChapter->gambar_pengenalan_path
        );
    }

    private function createUser(string $role, string $username): User
    {
        return User::create([
            'name' => ucfirst($role) . ' SALUANG',
            'username' => $username,
            'password' => 'secret123',
            'role' => $role,
            'email' => null,
        ]);
    }
}
