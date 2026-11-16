<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherInvitationRegistrationTest extends TestCase
{
    use RefreshDatabase;

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
}
