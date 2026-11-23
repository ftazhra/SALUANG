<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\SubchapterMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherSharedMaterialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_school_teachers_can_read_published_materials_but_only_owners_can_manage_them(): void
    {
        Storage::fake('material_public');
        $owner = $this->createTeacher('material-owner', 'SMP Negeri 21 Banjarmasin');
        $viewer = $this->createTeacher('material-viewer', 'SMP Negeri 21 Banjarmasin');
        $otherSchoolTeacher = $this->createTeacher('material-other-school', 'Sekolah Lain');

        $sharedChapter = $this->createChapter($owner, 1, true);
        $publishedSubchapter = $sharedChapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Materi terbit',
            'konten' => 'Teks pembelajaran bersama.',
            'is_published' => true,
        ]);
        $sharedChapter->subchapters()->create([
            'nomor_subbab' => 2,
            'judul' => 'Materi draft',
            'konten' => 'Belum dipublikasikan.',
            'is_published' => false,
        ]);
        Storage::disk('material_public')->put('shared/image.png', 'image');
        SubchapterMedia::create([
            'subchapter_id' => $publishedSubchapter->id,
            'media_type' => 'image',
            'source' => 'upload',
            'storage_path' => 'shared/image.png',
            'title' => 'Gambar bersama',
        ]);
        SubchapterMedia::create([
            'subchapter_id' => $publishedSubchapter->id,
            'media_type' => 'video',
            'source' => 'youtube',
            'youtube_video_id' => 'abc123def45',
            'title' => 'Video bersama',
        ]);

        $ownDraft = $this->createChapter($viewer, 2, false);
        $ownDraftSubchapter = $ownDraft->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Draft milik sendiri',
            'konten' => 'Konten pribadi.',
            'is_published' => false,
        ]);
        $this->createChapter($owner, 3, false);
        $this->createChapter($otherSchoolTeacher, 4, true);

        $this->actingAs($viewer)->getJson('/guru/chapters')
            ->assertOk()
            ->assertJsonCount(2, 'chapters')
            ->assertJsonPath('chapters.0.id', $sharedChapter->id)
            ->assertJsonPath('chapters.0.can_manage', false)
            ->assertJsonPath('chapters.0.owner_name', $owner->name)
            ->assertJsonPath('chapters.0.subchapters.0.id', $publishedSubchapter->id)
            ->assertJsonPath('chapters.0.subchapters.0.konten', 'Teks pembelajaran bersama.')
            ->assertJsonPath(
                'chapters.0.subchapters.0.media.0.url',
                Storage::disk('material_public')->url('shared/image.png')
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.media.1.embed_url',
                'https://www.youtube-nocookie.com/embed/abc123def45'
            )
            ->assertJsonCount(1, 'chapters.0.subchapters')
            ->assertJsonPath('chapters.1.id', $ownDraft->id)
            ->assertJsonPath('chapters.1.can_manage', true)
            ->assertJsonPath('chapters.1.subchapters.0.id', $ownDraftSubchapter->id);

        $this->actingAs($viewer)
            ->putJson('/guru/chapters/' . $sharedChapter->id, [
                'nomor_bab' => 1,
                'judul' => 'Tidak boleh diubah',
            ])
            ->assertNotFound();

        $this->actingAs($viewer)
            ->deleteJson('/guru/chapters/' . $sharedChapter->id)
            ->assertNotFound();

        $this->actingAs($viewer)
            ->deleteJson(
                '/guru/chapters/' . $sharedChapter->id
                . '/subchapters/' . $publishedSubchapter->id
            )
            ->assertNotFound();

        $this->assertDatabaseHas('chapters', [
            'id' => $sharedChapter->id,
            'user_id' => $owner->id,
            'judul' => $sharedChapter->judul,
        ]);
        $this->assertDatabaseHas('subchapters', [
            'id' => $publishedSubchapter->id,
            'chapter_id' => $sharedChapter->id,
        ]);
    }

    private function createTeacher(string $username, string $school): User
    {
        return User::create([
            'name' => $username,
            'username' => $username,
            'nip_nipk' => strtoupper($username),
            'password' => 'secret123',
            'role' => 'guru',
            'sekolah' => $school,
            'is_approved' => true,
        ]);
    }

    private function createChapter(
        User $teacher,
        int $number,
        bool $published
    ): Chapter {
        return Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => $number,
            'judul' => 'Bab ' . $number,
            'deskripsi' => 'Deskripsi Bab ' . $number,
            'is_published' => $published,
        ]);
    }
}
