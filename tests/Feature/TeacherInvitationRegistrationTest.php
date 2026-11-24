<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\SubchapterMedia;
use App\Models\User;
use App\Services\SchoolAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherInvitationRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_smpn21_registration_code_is_reusable_and_uses_the_school_of_ibu_ayu(): void
    {
        $ibuAyu = User::create([
            'name' => 'Putri Ayu S.B, S.Pd,. Gr.',
            'username' => 'ibu-ayu-school-group',
            'nip_nipk' => 'NIP-AYU-SCHOOL-GROUP',
            'password' => 'secret123',
            'role' => 'guru',
            'sekolah' => 'SMPN 21 Banjarmasin',
            'is_approved' => true,
        ]);

        foreach ([
            ['Guru SMPN21 Pertama', 'smpn21-teacher-one', 'NIP-SMPN21-ONE', 'SMPN21'],
            ['Guru SMPN21 Kedua', 'smpn21-teacher-two', 'NIP-SMPN21-TWO', 'smpn21'],
        ] as [$name, $username, $nip, $code]) {
            $this->post(route('register.store'), [
                'role' => 'guru',
                'name' => $name,
                'username' => $username,
                'nip_nipk' => $nip,
                'guru_token' => $code,
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])->assertRedirect(route('login'));

            $newTeacher = User::query()
                ->where('username', $username)
                ->firstOrFail();
            $this->assertSame(
                'SMP Negeri 21 Banjarmasin',
                $newTeacher->sekolah
            );
            $this->assertTrue(
                app(SchoolAccessService::class)->sameSchool(
                    $ibuAyu,
                    $newTeacher
                )
            );
        }

        $this->assertDatabaseCount('teacher_invitations', 0);
    }

    public function test_registration_page_does_not_disclose_teacher_codes(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(
                'placeholder="Masukkan kode pendaftaran Guru"',
                false
            )
            ->assertDontSee('SMPN21')
            ->assertDontSee('SMPN 21');

        $this->from(route('register'))->post(route('register.store'), [
            'role' => 'guru',
            'name' => 'Guru dengan Kode Salah',
            'username' => 'invalid-code-teacher',
            'nip_nipk' => 'NIP-INVALID-CODE',
            'guru_token' => 'INVALID-CODE-MUST-NOT-BE-ECHOED',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors('guru_token');

        $this->get(route('register'))
            ->assertDontSee('INVALID-CODE-MUST-NOT-BE-ECHOED')
            ->assertDontSee('SMPN21');
    }

    public function test_new_smpn21_teacher_can_log_in_and_read_same_school_materials(): void
    {
        Storage::fake('material_public');

        $ibuAyu = User::create([
            'name' => 'Ibu Ayu',
            'username' => 'ibu-ayu-shared-materials',
            'nip_nipk' => 'NIP-AYU-SHARED-MATERIALS',
            'password' => 'secret123',
            'role' => 'guru',
            'sekolah' => 'SMPN 21 Banjarmasin',
            'is_approved' => true,
        ]);
        $otherSchool = User::create([
            'name' => 'Guru Sekolah Lain',
            'username' => 'other-school-materials',
            'nip_nipk' => 'NIP-OTHER-SCHOOL-MATERIALS',
            'password' => 'secret123',
            'role' => 'guru',
            'sekolah' => 'SMP Negeri 22 Banjarmasin',
            'is_approved' => true,
        ]);

        $sharedChapter = Chapter::create([
            'user_id' => $ibuAyu->id,
            'nomor_bab' => 1,
            'judul' => 'Materi Terbit SMPN 21',
            'gambar_pengenalan_path' => 'shared/chapter-intro.png',
            'is_published' => true,
        ]);
        $sharedSubchapter = $sharedChapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab Terbit SMPN 21',
            'konten' => 'Teks materi bersama.',
            'is_published' => true,
        ]);
        $sharedChapter->subchapters()->create([
            'nomor_subbab' => 2,
            'judul' => 'Draft yang Tidak Dibagikan',
            'is_published' => false,
        ]);
        $sharedVideoChapter = Chapter::create([
            'user_id' => $ibuAyu->id,
            'nomor_bab' => 2,
            'judul' => 'Bab Video Terbit',
            'is_published' => true,
        ]);
        $sharedVideoSubchapter = $sharedVideoChapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Video Terbit',
            'is_published' => true,
        ]);
        $otherChapter = Chapter::create([
            'user_id' => $otherSchool->id,
            'nomor_bab' => 1,
            'judul' => 'Materi Khusus Sekolah Lain',
            'is_published' => true,
        ]);
        $otherSubchapter = $otherChapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab Sekolah Lain',
            'is_published' => true,
        ]);

        Storage::disk('material_public')->put(
            'shared/chapter-intro.png',
            'image'
        );
        Storage::disk('material_public')->put('shared/lesson.png', 'image');
        Storage::disk('material_public')->put('shared/lesson.mp4', 'video');
        Storage::disk('material_public')->put('other/private.mp4', 'video');
        $sharedImage = SubchapterMedia::create([
            'subchapter_id' => $sharedSubchapter->id,
            'media_type' => 'image',
            'source' => 'upload',
            'storage_path' => 'shared/lesson.png',
            'title' => 'Gambar Materi',
        ]);
        $sharedVideo = SubchapterMedia::create([
            'subchapter_id' => $sharedVideoSubchapter->id,
            'media_type' => 'video',
            'source' => 'upload',
            'storage_path' => 'shared/lesson.mp4',
            'title' => 'Video Materi',
        ]);
        $otherVideo = SubchapterMedia::create([
            'subchapter_id' => $otherSubchapter->id,
            'media_type' => 'video',
            'source' => 'upload',
            'storage_path' => 'other/private.mp4',
            'title' => 'Video Sekolah Lain',
        ]);

        $this->post(route('register.store'), [
            'role' => 'guru',
            'name' => 'Guru Baru SMPN21',
            'username' => 'new-smpn21-material-teacher',
            'nip_nipk' => 'NIP-NEW-SMPN21-MATERIAL',
            'guru_token' => 'SMPN21',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('login'));

        $newTeacher = User::query()
            ->where('username', 'new-smpn21-material-teacher')
            ->firstOrFail();
        $this->post('/masuk', [
            'role' => 'guru',
            'identifier' => $newTeacher->nip_nipk,
            'password' => 'secret123',
        ])->assertRedirect(route('dashboard'));

        $this->getJson('/guru/chapters')
            ->assertOk()
            ->assertJsonCount(2, 'chapters')
            ->assertJsonPath('chapters.0.id', $sharedChapter->id)
            ->assertJsonPath(
                'chapters.0.gambar_pengenalan_url',
                Storage::disk('material_public')->url('shared/chapter-intro.png')
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.konten',
                'Teks materi bersama.'
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.media.0.url',
                Storage::disk('material_public')->url('shared/lesson.png')
            )
            ->assertJsonPath(
                'chapters.0.subchapters.0.content_blocks.1.media.url',
                Storage::disk('material_public')->url('shared/lesson.png')
            )
            ->assertJsonPath(
                'chapters.1.subchapters.0.media.0.url',
                Storage::disk('material_public')->url('shared/lesson.mp4')
            )
            ->assertJsonMissing([
                'judul' => 'Draft yang Tidak Dibagikan',
            ])
            ->assertJsonMissing([
                'judul' => 'Materi Khusus Sekolah Lain',
            ])
            ->assertJsonMissing(['judul' => 'Materi Khusus Sekolah Lain'])
            ->assertJsonFragment([
                'id' => $sharedVideo->id,
                'title' => 'Video Materi',
            ])
            ->assertJsonMissing([
                'title' => 'Video Sekolah Lain',
            ]);

        $this->putJson('/guru/chapters/' . $sharedChapter->id, [
            'nomor_bab' => 1,
            'judul' => 'Bab yang Tidak Boleh Diubah',
            'is_published' => false,
        ])->assertNotFound();
        $this->putJson(
            '/guru/chapters/' . $sharedChapter->id
            . '/subchapters/' . $sharedSubchapter->id,
            [
                'nomor_subbab' => 1,
                'judul' => 'Subbab yang Tidak Boleh Diubah',
                'is_published' => false,
            ]
        )->assertNotFound();
        $this->deleteJson(
            '/guru/chapters/' . $sharedChapter->id
            . '/subchapters/' . $sharedSubchapter->id
        )->assertNotFound();

        $ownChapterResponse = $this->postJson('/guru/chapters', [
            'nomor_bab' => 1,
            'judul' => 'Bab Buatan Guru Baru',
            'is_published' => false,
        ])->assertCreated();
        $ownChapterId = $ownChapterResponse->json('chapter.id');
        $this->putJson('/guru/chapters/' . $ownChapterId, [
            'nomor_bab' => 1,
            'judul' => 'Bab Guru Baru Diperbarui',
        ])->assertOk();
        $this->postJson(
            '/guru/chapters/' . $ownChapterId . '/subchapters',
            [
                'nomor_subbab' => 1,
                'judul' => 'Subbab Buatan Guru Baru',
                'konten' => 'Materi milik sendiri.',
                'is_published' => false,
            ]
        )->assertCreated();

        $ownSubchapter = Chapter::findOrFail($ownChapterId)
            ->subchapters()
            ->firstOrFail();
        $this->putJson(
            '/guru/chapters/' . $ownChapterId
            . '/subchapters/' . $ownSubchapter->id,
            [
                'nomor_subbab' => 1,
                'judul' => 'Subbab Milik Guru Baru',
                'video_action' => 'library',
                'video_library_id' => $otherVideo->id,
            ]
        )->assertStatus(422);

        $this->putJson(
            '/guru/chapters/' . $ownChapterId
            . '/subchapters/' . $ownSubchapter->id,
            [
                'nomor_subbab' => 1,
                'judul' => 'Subbab Milik Guru Baru',
                'video_action' => 'library',
                'video_library_id' => $sharedVideo->id,
            ]
        )->assertOk();

        $this->deleteJson('/guru/chapters/' . $ownChapterId)
            ->assertOk();

        $this->assertDatabaseHas('chapters', [
            'id' => $sharedChapter->id,
            'user_id' => $ibuAyu->id,
            'judul' => 'Materi Terbit SMPN 21',
        ]);
        $this->assertDatabaseHas('subchapters', [
            'id' => $sharedSubchapter->id,
            'chapter_id' => $sharedChapter->id,
        ]);
        $this->assertTrue(
            Storage::disk('material_public')->exists('shared/lesson.png')
        );
        $this->assertTrue(
            Storage::disk('material_public')->exists('shared/lesson.mp4')
        );
    }

    public function test_admin_invitation_defaults_to_seven_days(): void
    {
        Carbon::setTestNow('2026-03-01 12:00:00');

        try {
            $this->assertSame(0, Artisan::call(
                'saluang:invite-teacher',
                ['school' => 'Sekolah dengan Undangan Default']
            ));

            $invitation = DB::table('teacher_invitations')->first();
            $this->assertNotNull($invitation);
            $this->assertSame(
                Carbon::now()->addDays(7)->toDateTimeString(),
                Carbon::parse($invitation->expires_at)->toDateTimeString()
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_teacher_registration_uses_school_from_single_use_invitation(): void
    {
        $existingTeacher = User::create([
            'name' => 'Guru Sekolah B',
            'username' => 'existing-school-b',
            'nip_nipk' => 'NIP-EXISTING-B',
            'password' => 'secret123',
            'role' => 'guru',
            'sekolah' => 'Sekolah B',
            'is_approved' => true,
        ]);

        $this->assertSame(0, Artisan::call('saluang:invite-teacher', [
            'school' => 'Sekolah A',
            '--days' => 2,
        ]));

        $output = Artisan::output();
        $this->assertMatchesRegularExpression(
            '/Kode undangan Guru .*: ([A-Z0-9]{16})/',
            $output
        );
        preg_match('/Kode undangan Guru .*: ([A-Z0-9]{16})/', $output, $matches);
        $code = $matches[1];
        $invitation = DB::table('teacher_invitations')
            ->where('code_hash', hash('sha256', $code))
            ->first();
        $this->assertNotNull($invitation);
        $this->assertSame('Sekolah A', $invitation->school_name);
        $this->assertNull($invitation->used_at);

        $this->post(route('register.store'), [
            'role' => 'guru',
            'name' => 'Guru Baru Sekolah A',
            'username' => 'new-teacher-school-a',
            'nip_nipk' => 'NIP-NEW-A',
            'guru_token' => $code,
            'sekolah' => 'Sekolah B',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('login'));

        $newTeacher = User::query()
            ->where('username', 'new-teacher-school-a')
            ->firstOrFail();
        $this->assertSame('Sekolah A', $newTeacher->sekolah);
        $this->assertSame(0, (int) $newTeacher->is_approved);

        $claimedInvitation = DB::table('teacher_invitations')
            ->where('id', $invitation->id)
            ->first();
        $this->assertNotNull($claimedInvitation->used_at);
        $this->assertSame($newTeacher->id, (int) $claimedInvitation->used_by);

        $this->assertSame('Sekolah B', $existingTeacher->fresh()->sekolah);
        $this->assertSame(1, (int) $existingTeacher->fresh()->is_approved);

        $this->from(route('register'))->post(route('register.store'), [
            'role' => 'guru',
            'name' => 'Guru Percobaan Ulang',
            'username' => 'reused-teacher-invitation',
            'nip_nipk' => 'NIP-REUSED',
            'guru_token' => $code,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors('guru_token');

        $this->assertDatabaseMissing('users', [
            'username' => 'reused-teacher-invitation',
        ]);
    }

    public function test_expired_invitation_cannot_register_a_teacher(): void
    {
        $code = 'EXPIREDINVITE123';
        $invitationId = DB::table('teacher_invitations')->insertGetId([
            'school_name' => 'Sekolah A',
            'code_hash' => hash('sha256', $code),
            'expires_at' => Carbon::now()->subMinute(),
            'used_at' => null,
            'used_by' => null,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $this->from(route('register'))->post(route('register.store'), [
            'role' => 'guru',
            'name' => 'Guru Undangan Kedaluwarsa',
            'username' => 'expired-invitation-teacher',
            'nip_nipk' => 'NIP-EXPIRED',
            'guru_token' => $code,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors('guru_token');

        $this->assertDatabaseMissing('users', [
            'username' => 'expired-invitation-teacher',
        ]);
        $this->assertNull(
            DB::table('teacher_invitations')->where('id', $invitationId)->value('used_at')
        );
    }

    public function test_existing_teacher_can_log_in_after_invitation_expires(): void
    {
        $teacher = User::create([
            'name' => 'Guru Sudah Terdaftar',
            'username' => 'teacher-expired-login',
            'nip_nipk' => 'NIP-EXPIRED-LOGIN',
            'password' => 'secret123',
            'role' => 'guru',
            'sekolah' => 'Sekolah A',
        ]);
        DB::table('teacher_invitations')->insert([
            'school_name' => 'Sekolah A',
            'code_hash' => hash('sha256', 'EXPIREDTEACHER123'),
            'expires_at' => Carbon::now()->subDay(),
            'used_at' => Carbon::now()->subDays(2),
            'used_by' => $teacher->id,
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        $this->post('/masuk', [
            'role' => 'guru',
            'identifier' => $teacher->nip_nipk,
            'password' => 'secret123',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($teacher);
    }
}
