<?php

namespace Tests\Unit;

use App\Http\Controllers\PasswordRecoveryController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class UserMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_saluang_registration_fields(): void
    {
        $user = User::create([
            'name' => 'Siswa Baru',
            'email' => 'siswa@example.com',
            'password' => 'secret123',
            'username' => 'siswa01',
            'role' => 'siswa',
            'nisn' => '1234567890',
            'kelas' => 'VII A',
            'sekolah' => 'SMP Negeri 21 Banjarmasin',
            'profile_photo_path' => 'profile-photos/test.jpg',
            'is_approved' => false,
        ]);

        $this->assertSame('siswa', $user->role);
        $this->assertSame('siswa01', $user->username);
        $this->assertSame('1234567890', $user->nisn);
        $this->assertSame('VII A', $user->kelas);
        $this->assertSame('SMP Negeri 21 Banjarmasin', $user->sekolah);
        $this->assertSame('profile-photos/test.jpg', $user->profile_photo_path);
    }

    public function test_password_recovery_lookup_ignores_email_case(): void
    {
        $user = User::create([
            'name' => 'Siswa Baru',
            'email' => 'Siswa@Example.com',
            'password' => 'secret123',
            'username' => 'siswa02',
            'role' => 'siswa',
            'nisn' => '1234567891',
            'kelas' => 'VIII B',
            'sekolah' => 'SMP Negeri 21 Banjarmasin',
        ]);

        $request = Request::create('/lupa-password', 'POST', [
            'email' => 'siswa@example.com',
        ]);

        $response = app(PasswordRecoveryController::class)->store($request);

        $this->assertTrue($response->isRedirect());
        $this->assertDatabaseHas('password_recovery_requests', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }
}
