<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_roles_log_out_through_post_and_return_to_login(): void
    {
        foreach (['guru', 'siswa', 'orang_tua'] as $role) {
            $user = User::create([
                'name' => 'Akun ' . $role,
                'username' => $role . '-logout',
                'email' => $role . '-logout@example.test',
                'password' => 'secret123',
                'role' => $role,
            ]);

            $this->actingAs($user)
                ->post('/keluar')
                ->assertRedirect(route('login'));

            $this->assertGuest();
        }
    }

    public function test_logout_does_not_accept_get_requests(): void
    {
        $this->get('/keluar?i=1')->assertMethodNotAllowed();
    }
}
