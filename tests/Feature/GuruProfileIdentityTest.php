<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruProfileIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_identity_uses_the_logged_in_teachers_subject_or_falls_back_to_guru(): void
    {
        foreach ([
            ['ipa', 'IPA', 'Guru IPA'],
            ['penjas', 'Penjas', 'Guru Penjas'],
            ['no-subject', null, 'Guru'],
            ['blank-subject', '   ', 'Guru'],
        ] as [$username, $subject, $expectedLabel]) {
            $teacher = $this->teacher($username, $subject);

            $this->actingAs($teacher)
                ->get('/dashboard')
                ->assertOk()
                ->assertSee(
                    '<small id="gdTopbarRole">' . $expectedLabel . '</small>',
                    false
                );
        }
    }

    public function test_profile_subject_update_is_saved_and_returned_for_live_identity_refresh(): void
    {
        $teacher = $this->teacher('profile-subject-update', 'IPA');

        $this->actingAs($teacher)
            ->patchJson('/guru/profile', [
                'name' => $teacher->name,
                'username' => $teacher->username,
                'email' => $teacher->email,
                'mata_pelajaran' => 'Penjas',
                'sekolah' => $teacher->sekolah,
                'nomor_telepon' => $teacher->nomor_telepon,
            ])
            ->assertOk()
            ->assertJsonPath('data.mata_pelajaran', 'Penjas');

        $this->assertSame('Penjas', $teacher->fresh()->mata_pelajaran);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('<small id="gdTopbarRole">Guru Penjas</small>', false);

        $this->patchJson('/guru/profile', [
            'name' => $teacher->name,
            'username' => $teacher->username,
            'email' => $teacher->email,
            'mata_pelajaran' => null,
            'sekolah' => $teacher->sekolah,
            'nomor_telepon' => $teacher->nomor_telepon,
        ])
            ->assertOk()
            ->assertJsonPath('data.mata_pelajaran', null);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('<small id="gdTopbarRole">Guru</small>', false);
    }

    private function teacher(string $username, ?string $subject): User
    {
        return User::create([
            'name' => 'Guru ' . $username,
            'username' => $username,
            'email' => $username . '@example.test',
            'password' => 'secret123',
            'role' => 'guru',
            'mata_pelajaran' => $subject,
        ]);
    }
}
