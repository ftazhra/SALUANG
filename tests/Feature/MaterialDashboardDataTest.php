<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Subchapter;
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

    public function test_old_and_new_students_see_each_teachers_published_content_without_assignments(): void
    {
        $firstTeacher = $this->createUser('guru', 'guru-global-material-a');
        $firstTeacher->update(['name' => 'Guru Global A', 'sekolah' => 'Sekolah A']);
        $secondTeacher = $this->createUser('guru', 'guru-global-material-b');
        $secondTeacher->update(['name' => 'Guru Global B', 'sekolah' => 'Sekolah B']);
        $existingStudent = $this->createUser('siswa', 'existing-global-material');
        $existingStudent->update(['kelas' => 'VII A', 'sekolah' => 'Sekolah A']);

        $firstChapter = Chapter::create([
            'user_id' => $firstTeacher->id,
            'nomor_bab' => 6,
            'judul' => 'Materi Guru A',
            'is_published' => true,
        ]);
        $secondChapter = Chapter::create([
            'user_id' => $secondTeacher->id,
            'nomor_bab' => 6,
            'judul' => 'Materi Guru B',
            'is_published' => true,
        ]);
        $draftChapter = Chapter::create([
            'user_id' => $secondTeacher->id,
            'nomor_bab' => 7,
            'judul' => 'Materi Draf',
            'is_published' => false,
        ]);
        $firstSubchapter = Subchapter::create([
            'chapter_id' => $firstChapter->id,
            'nomor_subbab' => 1,
            'judul' => 'Subbab Guru A',
            'konten' => '<p>Teks Guru A</p>',
            'is_published' => true,
        ]);
        $secondSubchapter = Subchapter::create([
            'chapter_id' => $secondChapter->id,
            'nomor_subbab' => 1,
            'judul' => 'Subbab Guru B',
            'konten' => '<p>Teks Guru B</p>',
            'content_blocks_enabled' => true,
            'is_published' => true,
        ]);
        $image = $secondSubchapter->media()->create([
            'media_type' => 'image',
            'source' => 'library',
            'library_path' => 'images/global-material.png',
            'title' => 'Ilustrasi Guru B',
            'caption' => 'Keterangan ilustrasi',
        ]);
        $secondSubchapter->contentBlocks()->create([
            'type' => 'text',
            'position' => 1,
            'payload' => ['content' => '<p>Konten blok Guru B</p>'],
        ]);
        $secondSubchapter->contentBlocks()->create([
            'type' => 'image',
            'position' => 2,
            'media_id' => $image->id,
            'payload' => ['layout' => 'right'],
        ]);

        $this->post(route('register.store'), [
            'role' => 'siswa',
            'name' => 'Siswa Baru Materi',
            'username' => 'new-global-material',
            'nisn' => '9876543210',
            'email' => 'new-global-material@example.test',
            'kelas' => 'IX C',
            'sekolah' => 'Sekolah C',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('login'));
        $newStudent = User::query()
            ->where('username', 'new-global-material')
            ->firstOrFail();

        foreach ([$existingStudent, $newStudent] as $student) {
            $response = $this->actingAs($student)
                ->getJson('/siswa/materials')
                ->assertOk()
                ->assertJsonCount(2, 'chapters')
                ->assertJsonMissing(['judul' => 'Materi Draf']);
            $chapters = collect($response->json('chapters'))->keyBy('id');
            $this->assertSame('Guru Global A', $chapters[$firstChapter->id]['guru']['name']);
            $this->assertSame('Guru Global B', $chapters[$secondChapter->id]['guru']['name']);
            $this->assertSame(
                '<p>Teks Guru A</p>',
                $chapters[$firstChapter->id]['subchapters'][0]['content_blocks'][0]['payload']['content']
            );
            $secondBlocks = $chapters[$secondChapter->id]['subchapters'][0]['content_blocks'];
            $this->assertSame('Konten blok Guru B', strip_tags($secondBlocks[0]['payload']['content']));
            $this->assertSame('Ilustrasi Guru B', $secondBlocks[1]['media']['title']);
            $this->assertSame('Keterangan ilustrasi', $secondBlocks[1]['media']['caption']);
        }

        $this->assertDatabaseMissing('teacher_class_assignments', [
            'teacher_id' => $firstTeacher->id,
            'class_name' => 'IX C',
        ]);
        $this->assertDatabaseMissing('teacher_class_assignments', [
            'teacher_id' => $secondTeacher->id,
            'class_name' => 'IX C',
        ]);
        $this->assertDatabaseHas('subchapters', ['id' => $firstSubchapter->id]);
        $this->assertDatabaseHas('chapters', ['id' => $draftChapter->id, 'is_published' => false]);
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
