<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanupTrialTeachersTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_command_is_a_dry_run_and_lists_each_guru(): void
    {
        $protected = $this->createTeacher('Ibu Ayu', 'ayu-protected');
        $trial = $this->createTeacher('Guru Percobaan', 'trial-one');

        $this->artisan('saluang:cleanup-trial-teachers')
            ->expectsOutputToContain('DRY RUN')
            ->expectsOutputToContain((string) $protected->id)
            ->expectsOutputToContain((string) $trial->id)
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $protected->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('users', ['id' => $trial->id, 'deleted_at' => null]);
    }

    public function test_execution_preserves_protected_guru_and_rejects_accounts_with_relations(): void
    {
        $protected = $this->createTeacher('Ibu Ayu', 'ayu-verified');
        $first = $this->createTeacher('Guru Percobaan Satu', 'trial-one-verified');
        $second = $this->createTeacher('Guru Percobaan Dua', 'trial-two-verified');
        Chapter::create([
            'user_id' => $first->id,
            'nomor_bab' => 6,
            'judul' => 'Bab milik Guru percobaan',
            'is_published' => false,
        ]);
        $targetIds = [$first->id, $second->id];
        sort($targetIds);

        $this->artisan('saluang:cleanup-trial-teachers', [
            '--teacher' => [
                "{$first->id}:{$first->username}",
                "{$second->id}:{$second->username}",
            ],
            '--protect' => "{$protected->id}:{$protected->username}",
            '--protect-name' => $protected->name,
            '--execute' => true,
            '--confirm' => 'HAPUS-GURU-' . implode('-', $targetIds) . '-SETELAH-BACKUP',
        ])->expectsOutputToContain('Penghapusan ditolak')
            ->assertExitCode(1);

        $this->assertDatabaseHas('users', ['id' => $protected->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('users', ['id' => $first->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('users', ['id' => $second->id, 'deleted_at' => null]);
    }

    public function test_deactivation_soft_deletes_only_verified_targets_and_preserves_relations(): void
    {
        $protected = $this->createTeacher('Ibu Ayu', 'ayu-deactivation-protected');
        $first = $this->createTeacher('Guru Percobaan Satu', 'trial-one-deactivation');
        $second = $this->createTeacher('Guru Percobaan Dua', 'trial-two-deactivation');
        $chapter = Chapter::create([
            'user_id' => $first->id,
            'nomor_bab' => 6,
            'judul' => 'Bab yang tetap disimpan',
            'is_published' => false,
        ]);
        $targetIds = [$first->id, $second->id];
        sort($targetIds);

        $this->artisan('saluang:cleanup-trial-teachers', [
            '--teacher' => [
                "{$first->id}:{$first->username}",
                "{$second->id}:{$second->username}",
            ],
            '--protect' => "{$protected->id}:{$protected->username}",
            '--protect-name' => $protected->name,
            '--execute' => true,
            '--deactivate' => true,
            '--confirm' => 'NONAKTIFKAN-GURU-' . implode('-', $targetIds) . '-SETELAH-BACKUP',
        ])->assertExitCode(0);

        $this->assertSoftDeleted('users', ['id' => $first->id]);
        $this->assertSoftDeleted('users', ['id' => $second->id]);
        $this->assertDatabaseHas('users', ['id' => $protected->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('chapters', ['id' => $chapter->id, 'user_id' => $first->id]);
    }

    public function test_execution_requires_exact_protected_identity_and_removes_only_unreferenced_targets(): void
    {
        $protected = $this->createTeacher('Ibu Ayu', 'ayu-explicit');
        $first = $this->createTeacher('Guru Percobaan Satu', 'trial-one-explicit');
        $second = $this->createTeacher('Guru Percobaan Dua', 'trial-two-explicit');
        $targetIds = [$first->id, $second->id];
        sort($targetIds);
        $options = [
            '--teacher' => [
                "{$first->id}:{$first->username}",
                "{$second->id}:{$second->username}",
            ],
            '--protect' => "{$protected->id}:{$protected->username}",
            '--protect-name' => 'Nama yang tidak cocok',
            '--execute' => true,
            '--confirm' => 'HAPUS-GURU-' . implode('-', $targetIds) . '-SETELAH-BACKUP',
        ];

        $this->artisan('saluang:cleanup-trial-teachers', $options)
            ->expectsOutputToContain('Nama akun yang dilindungi tidak cocok')
            ->assertExitCode(1);
        $this->assertDatabaseHas('users', ['id' => $first->id, 'deleted_at' => null]);

        $options['--protect-name'] = $protected->name;
        $this->artisan('saluang:cleanup-trial-teachers', $options)->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $protected->id, 'deleted_at' => null]);
        $this->assertDatabaseMissing('users', ['id' => $first->id]);
        $this->assertDatabaseMissing('users', ['id' => $second->id]);
    }

    public function test_protected_teacher_cannot_be_in_the_execution_targets(): void
    {
        $protected = $this->createTeacher('Ibu Ayu', 'ayu-target-protected');
        $trial = $this->createTeacher('Guru Percobaan', 'trial-target');
        $targetIds = [$protected->id, $trial->id];
        sort($targetIds);

        $this->artisan('saluang:cleanup-trial-teachers', [
            '--teacher' => [
                "{$protected->id}:{$protected->username}",
                "{$trial->id}:{$trial->username}",
            ],
            '--protect' => "{$protected->id}:{$protected->username}",
            '--protect-name' => $protected->name,
            '--execute' => true,
            '--deactivate' => true,
            '--confirm' => 'NONAKTIFKAN-GURU-' . implode('-', $targetIds) . '-SETELAH-BACKUP',
        ])->expectsOutputToContain('Akun Guru yang dilindungi tidak boleh menjadi target')
            ->assertExitCode(1);

        $this->assertDatabaseHas('users', ['id' => $protected->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('users', ['id' => $trial->id, 'deleted_at' => null]);
    }

    private function createTeacher(string $name, string $username): User
    {
        return User::create([
            'name' => $name,
            'username' => $username,
            'password' => 'secret123',
            'role' => 'guru',
            'email' => null,
            'sekolah' => 'Sekolah Uji',
        ]);
    }
}
