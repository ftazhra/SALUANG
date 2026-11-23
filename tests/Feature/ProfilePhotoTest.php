<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('material_public');
    }

    public function test_each_role_can_upload_and_replace_its_own_profile_photo(): void
    {
        $roleRoutes = [
            'guru' => '/guru/profile/photo',
            'siswa' => '/siswa/profil/foto',
            'orang_tua' => '/orang-tua/profile/photo',
        ];
        $disk = Storage::disk('material_public');

        foreach ($roleRoutes as $role => $endpoint) {
            $user = $this->user($role, $role . '-photo');
            $other = $this->user($role, $role . '-other-photo');
            $otherPath = 'profile-photos/other-' . $role . '.jpg';
            $disk->put($otherPath, 'existing photo');
            $other->profile_photo_path = $otherPath;
            $other->save();

            $this->actingAs($user)
                ->postJson($endpoint, [
                    'photo' => UploadedFile::fake()->image('first.jpg'),
                ])
                ->assertOk()
                ->assertJsonPath('message', 'Foto profil' . ($role === 'guru' ? ' Guru' : '') . ' berhasil diperbarui.');

            $user->refresh();
            $firstPath = $user->profile_photo_path;
            $this->assertStringStartsWith('profile-photos/', $firstPath);
            $disk->assertExists($firstPath);
            $this->assertStringContainsString('/storage/' . $firstPath, $user->profile_photo_url);

            $this->actingAs($user)
                ->postJson($endpoint, [
                    'photo' => UploadedFile::fake()->image('replacement.png'),
                ])
                ->assertOk();

            $user->refresh();
            $this->assertNotSame($firstPath, $user->profile_photo_path);
            $disk->assertExists($firstPath);
            $disk->assertExists($user->profile_photo_path);
            $this->assertStringContainsString(
                '/storage/' . $user->profile_photo_path,
                $user->profile_photo_url
            );
            $this->assertSame($otherPath, $other->fresh()->profile_photo_path);
            $disk->assertExists($otherPath);
        }
    }

    public function test_invalid_profile_photo_is_rejected_without_changing_the_existing_photo(): void
    {
        $user = $this->user('siswa', 'student-invalid-photo');
        $path = 'profile-photos/existing.jpg';
        Storage::disk('material_public')->put($path, 'existing photo');
        $user->profile_photo_path = $path;
        $user->save();

        $this->actingAs($user)
            ->postJson('/siswa/profil/foto', [
                'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');

        $this->assertSame($path, $user->fresh()->profile_photo_path);
        Storage::disk('material_public')->assertExists($path);

        $this->actingAs($user)
            ->postJson('/siswa/profil/foto', [
                'photo' => UploadedFile::fake()->image('oversized.jpg')->size(2049),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');

        $this->assertSame($path, $user->fresh()->profile_photo_path);
        Storage::disk('material_public')->assertExists($path);
    }

    public function test_deleting_a_photo_reference_keeps_the_old_file_and_missing_photos_use_no_url(): void
    {
        $user = $this->user('siswa', 'student-remove-photo');
        $path = 'profile-photos/retained.jpg';
        Storage::disk('material_public')->put($path, 'existing photo');
        $user->profile_photo_path = $path;
        $user->save();

        $this->actingAs($user)
            ->deleteJson('/siswa/profil/foto')
            ->assertOk();

        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('material_public')->assertExists($path);

        $user->profile_photo_path = 'profile-photos/missing.jpg';
        $this->assertNull($user->profile_photo_url);
    }

    public function test_each_role_deletes_only_its_own_photo_reference_with_json_response(): void
    {
        $roleRoutes = [
            'guru' => '/guru/profile/photo',
            'siswa' => '/siswa/profil/foto',
            'orang_tua' => '/orang-tua/profile/photo',
        ];
        $disk = Storage::disk('material_public');
        $sharedPath = 'profile-photos/shared-owner.jpg';
        $disk->put($sharedPath, 'shared photo');

        foreach ($roleRoutes as $role => $endpoint) {
            $user = $this->user($role, $role . '-delete-photo');
            $other = $this->user($role, $role . '-keep-photo');
            $user->profile_photo_path = $sharedPath;
            $user->save();
            $other->profile_photo_path = $sharedPath;
            $other->save();

            $this->actingAs($user)
                ->deleteJson($endpoint)
                ->assertOk()
                ->assertJsonPath('message', 'Foto profil dihapus dari akun. File lama tetap disimpan.');

            $this->assertNull($user->fresh()->profile_photo_path);
            $this->assertSame($sharedPath, $other->fresh()->profile_photo_path);
            $disk->assertExists($sharedPath);
        }
    }

    public function test_profile_photo_endpoints_reject_users_with_another_role(): void
    {
        $teacher = $this->user('guru', 'teacher-photo-forbidden');

        $this->actingAs($teacher)
            ->postJson('/siswa/profil/foto', [
                'photo' => UploadedFile::fake()->image('forbidden.jpg'),
            ])
            ->assertForbidden();

        $this->actingAs($teacher)
            ->postJson('/orang-tua/profile/photo', [
                'photo' => UploadedFile::fake()->image('forbidden.jpg'),
            ])
            ->assertForbidden();
    }

    private function user(string $role, string $username): User
    {
        return User::create([
            'name' => 'Akun ' . $role,
            'username' => $username,
            'email' => $username . '@example.test',
            'password' => 'secret123',
            'role' => $role,
        ]);
    }
}
