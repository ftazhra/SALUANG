<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
class ProfileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 01. PEMERIKSAAN AKSES GURU
    |--------------------------------------------------------------------------
    */
    private function ensureGuru(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'guru',
            403,
            'Fitur ini hanya tersedia untuk Guru.'
        );
    }
    /*
    |--------------------------------------------------------------------------
    | 02. MEMPERBARUI INFORMASI PROFIL
    |--------------------------------------------------------------------------
    */
    public function update(Request $request): JsonResponse
    {
        $this->ensureGuru($request);
        $user = $request->user();
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'mata_pelajaran' => [
                'nullable',
                'string',
                'max:255',
            ],
            'sekolah' => [
                'nullable',
                'string',
                'max:255',
            ],
            'nomor_telepon' => [
                'nullable',
                'string',
                'max:30',
            ],
        ]);
        // NIP/NIPK tidak dapat diubah melalui form ini.
        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->email = $data['email'] ?? null;
        $user->mata_pelajaran = $data['mata_pelajaran'] ?? null;
        $user->sekolah = $data['sekolah'] ?? null;
        $user->nomor_telepon = $data['nomor_telepon'] ?? null;
        $user->save();
        return response()->json([
            'message' => 'Profil Guru berhasil diperbarui.',
            'data' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'nip_nipk' => $user->nip_nipk,
                'mata_pelajaran' => $user->mata_pelajaran,
                'sekolah' => $user->sekolah,
                'nomor_telepon' => $user->nomor_telepon,
            ],
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | 03. MENGUBAH FOTO PROFIL
    |--------------------------------------------------------------------------
    */
    public function updatePhoto(Request $request): JsonResponse
    {
        $this->ensureGuru($request);
        $request->validate([
            'photo' => [
                'required',
                File::image()
                    ->max('2mb')
                    ->types(['jpg', 'jpeg', 'png', 'webp']),
            ],
        ]);
        $user = $request->user();
        $oldPath = $user->profile_photo_path;
        $newPath = $request->file('photo')->store(
            'profile-photos',
            'public'
        );
        try {
            $user->profile_photo_path = $newPath;
            $user->save();
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($newPath);
            throw $error;
        }
        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }
        return response()->json([
            'message' => 'Foto profil Guru berhasil diperbarui.',
            'photo_url' => asset('storage/' . $newPath),
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | 04. MENGHAPUS FOTO PROFIL
    |--------------------------------------------------------------------------
    */
    public function destroyPhoto(Request $request): JsonResponse
    {
        $this->ensureGuru($request);
        $user = $request->user();
        $oldPath = $user->profile_photo_path;
        $user->profile_photo_path = null;
        $user->save();
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }
        return response()->json([
            'message' => 'Foto profil Guru berhasil dihapus.',
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | 05. MENGUBAH KATA SANDI
    |--------------------------------------------------------------------------
    */
    public function updatePassword(Request $request): JsonResponse
    {
        $this->ensureGuru($request);
        $data = $request->validate([
            'current_password' => [
                'required',
                'string',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);
        $user = $request->user();
        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [
                    'Password saat ini tidak sesuai.',
                ],
            ]);
        }
        $user->password = Hash::make($data['password']);
        $user->save();
        return response()->json([
            'message' => 'Kata sandi Guru berhasil diperbarui.',
        ]);
    }
}
