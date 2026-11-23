<?php
namespace App\Http\Controllers\Siswa;
use App\Http\Controllers\Controller;
use App\Services\ProfilePhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
class ProfileController extends Controller
{
    public function updateInformation(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->role === 'siswa',
            403,
            'Hanya Siswa yang dapat mengubah informasi profil.'
        );

        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'alpha_dash:ascii',
                'max:50',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'kelas' => ['nullable', 'string', 'max:20'],
            'sekolah' => ['nullable', 'string', 'max:255'],
            'nomor_telepon' => ['nullable', 'string', 'max:20'],
        ]);

        foreach (['kelas', 'sekolah'] as $field) {
            if (
                array_key_exists($field, $data)
                && $data[$field] !== $user->{$field}
            ) {
                throw ValidationException::withMessages([
                    $field => 'Perubahan kelas atau sekolah harus diverifikasi melalui pengelola SALUANG.',
                ]);
            }
        }

        unset($data['kelas'], $data['sekolah']);
        $user->fill($data)->save();

        return response()->json([
            'message' => 'Informasi profil berhasil diperbarui.',
            'student' => $user->only([
                'id',
                'name',
                'username',
                'nisn',
                'email',
                'kelas',
                'sekolah',
                'nomor_telepon',
            ]),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 01. MENGUBAH FOTO PROFIL SISWA
    |--------------------------------------------------------------------------
    */
    public function updatePhoto(
        Request $request,
        ProfilePhotoService $photos
    ): JsonResponse
    {
        abort_unless(
            $request->user()
                && $request->user()->role === 'siswa',
            403,
            'Hanya siswa yang dapat mengubah foto profil.'
        );
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
            'message' => 'Foto profil berhasil diperbarui.',
            'photo_url' => $photoUrl,
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | 02. MENGHAPUS FOTO PROFIL SISWA
    |--------------------------------------------------------------------------
    */
    public function destroyPhoto(
        Request $request,
        ProfilePhotoService $photos
    ): JsonResponse
    {
        abort_unless(
            $request->user()
                && $request->user()->role === 'siswa',
            403,
            'Hanya siswa yang dapat menghapus foto profil.'
        );
        $photos->remove($request->user());

        return response()->json([
            'message' => 'Foto profil dihapus dari akun. File lama tetap disimpan.',
        ]);
    }
}
