<?php

namespace Tests\Feature;

use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentLinkCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_parent_can_verify_a_child_link_and_wrong_code_is_reported(): void
    {
        $student = $this->createUser('siswa', 'parent-link-student');
        $parent = $this->createUser('orang_tua', 'parent-link-parent');
        $link = StudentGuardian::query()->create([
            'student_id' => $student->id,
            'guardian_id' => $parent->id,
            'relationship' => 'orang_tua',
            'status' => 'pending',
        ]);

        $generated = $this->actingAs($student)
            ->postJson('/siswa/kode-orang-tua')
            ->assertOk();
        $code = $generated->json('code');

        $this->postJson('/orang-tua/verifikasi-anak', ['code' => $code])
            ->assertForbidden();

        $this->actingAs($parent)
            ->postJson('/orang-tua/verifikasi-anak', ['code' => '000000'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->assertSame('pending', $link->fresh()->status);

        $this->post('/orang-tua/verifikasi-anak', ['code' => $code])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('approved', $link->fresh()->status);
    }

    private function createUser(string $role, string $username): User
    {
        return User::create([
            'name' => ucfirst($role) . ' SALUANG',
            'username' => $username,
            'password' => 'secret123',
            'role' => $role,
            'email' => null,
            'sekolah' => $role === 'siswa' ? 'Sekolah Uji' : null,
        ]);
    }
}
