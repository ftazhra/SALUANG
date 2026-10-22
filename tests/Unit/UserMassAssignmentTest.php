<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'is_approved' => false,
        ]);

        $this->assertSame('siswa', $user->role);
        $this->assertSame('siswa01', $user->username);
        $this->assertSame('1234567890', $user->nisn);
        $this->assertSame('VII A', $user->kelas);
        $this->assertSame('SMP Negeri 21 Banjarmasin', $user->sekolah);
    }
}
