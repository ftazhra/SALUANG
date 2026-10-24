<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialDashboardDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_chapter_crud_and_publication_are_reflected_in_teacher_and_student_feeds(): void
    {
        $guru = $this->createUser('guru', 'guru-materi');
        $siswa = $this->createUser('siswa', 'siswa-materi');
        $chapterPayload = [
            'nomor_bab' => 6,
            'judul' => 'Ekologi dan Pelestarian Lingkungan',
            'deskripsi' => 'Materi ekologi kelas VII.',
            'tujuan_pembelajaran' => ['Memahami ekologi.'],
            'pertanyaan_pemantik' => 'Bagaimana lingkungan tetap lestari?',
            'is_published' => false,
        ];

        $created = $this->actingAs($guru)->postJson(
            '/guru/chapters',
            $chapterPayload
        );
        $created->assertCreated();
        $chapterId = $created->json('chapter.id');

        $this->getJson('/guru/chapters')
            ->assertOk()
            ->assertJsonPath('chapters.0.judul', $chapterPayload['judul']);

        $this->actingAs($siswa)->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonMissing(['judul' => $chapterPayload['judul']]);

        $updatedTitle = 'Ekologi dan Pelestarian Lingkungan Indonesia';
        $this->actingAs($guru)->putJson(
            '/guru/chapters/' . $chapterId,
            array_merge($chapterPayload, [
                'judul' => $updatedTitle,
                'is_published' => true,
            ])
        )->assertOk();

        $this->actingAs($siswa)->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonPath('chapters.0.judul', $updatedTitle);

        $this->actingAs($guru)->putJson(
            '/guru/chapters/' . $chapterId,
            array_merge($chapterPayload, [
                'judul' => $updatedTitle,
                'is_published' => false,
            ])
        )->assertOk();

        $this->actingAs($siswa)->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonMissing(['judul' => $updatedTitle]);

        $this->actingAs($guru)->deleteJson(
            '/guru/chapters/' . $chapterId
        )->assertOk();

        $this->assertDatabaseMissing('chapters', ['id' => $chapterId]);
        $this->actingAs($guru)->getJson('/guru/chapters')
            ->assertOk()
            ->assertJsonMissing(['id' => $chapterId]);
    }

    public function test_published_subchapter_crud_is_reflected_in_student_feed(): void
    {
        $guru = $this->createUser('guru', 'guru-subbab');
        $siswa = $this->createUser('siswa', 'siswa-subbab');
        $chapter = Chapter::create([
            'user_id' => $guru->id,
            'nomor_bab' => 6,
            'judul' => 'Ekologi dan Pelestarian Lingkungan',
            'deskripsi' => 'Materi ekologi kelas VII.',
            'is_published' => true,
        ]);

        $subchapterPayload = [
            'nomor_subbab' => 1,
            'judul' => 'Hubungan Makhluk Hidup',
            'konten' => 'Materi tentang hubungan makhluk hidup.',
            'is_published' => true,
            'image_action' => 'none',
            'video_action' => 'none',
        ];

        $created = $this->actingAs($guru)->postJson(
            '/guru/chapters/' . $chapter->id . '/subchapters',
            $subchapterPayload
        );
        $created->assertCreated();
        $subchapterId = $created->json('subchapter.id');

        $this->actingAs($siswa)->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonPath(
                'chapters.0.subchapters.0.judul',
                $subchapterPayload['judul']
            );

        $updatedTitle = 'Hubungan Antar Makhluk Hidup';
        $this->actingAs($guru)->putJson(
            '/guru/chapters/' . $chapter->id . '/subchapters/' . $subchapterId,
            array_merge($subchapterPayload, [
                'judul' => $updatedTitle,
            ])
        )->assertOk();

        $this->actingAs($siswa)->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonPath(
                'chapters.0.subchapters.0.judul',
                $updatedTitle
            );

        $this->actingAs($guru)->deleteJson(
            '/guru/chapters/' . $chapter->id . '/subchapters/' . $subchapterId
        )->assertOk();

        $this->actingAs($siswa)->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonPath('chapters.0.subchapters', []);
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
