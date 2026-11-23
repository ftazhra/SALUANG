<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Services\ProfilePhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        if (
            array_key_exists('sekolah', $data)
            && $data['sekolah'] !== $user->sekolah
        ) {
            throw ValidationException::withMessages([
                'sekolah' => 'Perubahan sekolah harus diverifikasi melalui pengelola SALUANG.',
            ]);
        }

        // NIP/NIPK tidak dapat diubah melalui form ini.
        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->email = $data['email'] ?? null;
        $user->mata_pelajaran = $data['mata_pelajaran'] ?? null;
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
    public function updatePhoto(
        Request $request,
        ProfilePhotoService $photos
    ): JsonResponse
    {
        $this->ensureGuru($request);
        $request->validate([
            'photo' => [
                'required',
                File::image()
                    ->max(2048)
                    ->types(['jpg', 'jpeg', 'png', 'webp']),
            ],
        ], [
            'photo.required' => 'Pilih foto profil terlebih dahulu.',
            'photo.image' => 'File yang dipilih harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'photo.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
            'photo.extensions' => 'Format foto harus JPG, PNG, atau WebP.',
        ]);
        $photoUrl = $photos->replace($request->user(), $request->file('photo'));

        return response()->json([
            'message' => 'Foto profil Guru berhasil diperbarui.',
            'photo_url' => $photoUrl,
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | 04. MENGHAPUS FOTO PROFIL
    |--------------------------------------------------------------------------
    */
    public function destroyPhoto(
        Request $request,
        ProfilePhotoService $photos
    ): JsonResponse
    {
        $this->ensureGuru($request);
        $photos->remove($request->user());

        return response()->json([
            'message' => 'Foto profil dihapus dari akun. File lama tetap disimpan.',
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
