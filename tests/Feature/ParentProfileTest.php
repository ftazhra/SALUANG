<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParentProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_update_own_profile_and_password(): void
    {
        $parent = $this->user('orang_tua', 'parent-profile-update');

        $this->actingAs($parent)
            ->patchJson('/orang-tua/profile', [
                'name' => 'Nama Orang Tua Baru',
                'username' => 'parent-profile-updated',
                'email' => 'parent-updated@example.test',
                'nomor_telepon' => '08123456789',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nama Orang Tua Baru');

        $this->actingAs($parent)
            ->patchJson('/orang-tua/profile/password', [
                'current_password' => 'secret123',
                'password' => 'new-secret-123',
                'password_confirmation' => 'new-secret-123',
            ])
            ->assertOk();

        $parent->refresh();
        $this->assertSame('parent-profile-updated', $parent->username);
        $this->assertTrue(Hash::check('new-secret-123', $parent->password));
    }

    public function test_non_parent_cannot_update_parent_profile(): void
    {
        $teacher = $this->user('guru', 'teacher-not-parent-profile');

        $this->actingAs($teacher)
            ->patchJson('/orang-tua/profile', [
                'name' => 'Nama Terlarang',
                'username' => 'forbidden-profile',
            ])
            ->assertForbidden();
    }

    public function test_parent_dashboard_uses_working_profile_dropdown_and_shared_logout_confirmation(): void
    {
        $parent = $this->user('orang_tua', 'parent-dashboard-ui');

        $this->actingAs($parent)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Profil Saya')
            ->assertSee('Pengaturan')
            ->assertSee('parentNotifMenu')
            ->assertSee('gdLogoutModal')
            ->assertSee('Yakin ingin keluar?');
    }

    private function user(string $role, string $username): User
    {
        return User::create([
            'name' => 'Akun Uji',
            'username' => $username,
            'email' => $username . '@example.test',
            'password' => 'secret123',
            'role' => $role,
        ]);
    }
}
