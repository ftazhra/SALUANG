<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class ProfileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 01. MENGUBAH FOTO PROFIL SISWA
    |--------------------------------------------------------------------------
    */

    public function updatePhoto(Request $request): JsonResponse
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
                    ->max('2mb')
                    ->types(['jpg', 'jpeg', 'png', 'webp']),
            ],
        ]);

        $user = $request->user();
        $oldPath = $user->profile_photo_path;

        // Simpan foto baru ke public disk.
        $newPath = $request->file('photo')->store(
            'profile-photos',
            'public'
        );

        try {
            // Hanya akun siswa yang login yang diperbarui.
            $user->profile_photo_path = $newPath;
            $user->save();
        } catch (\Throwable $error) {
            // Jangan tinggalkan file baru jika database gagal.
            Storage::disk('public')->delete($newPath);
            throw $error;
        }

        // Foto lama dihapus setelah data baru tersimpan.
        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'message' => 'Foto profil berhasil diperbarui.',
            'photo_url' => asset('storage/' . $newPath),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | 02. MENGHAPUS FOTO PROFIL SISWA
    |--------------------------------------------------------------------------
    */

    public function destroyPhoto(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()
                && $request->user()->role === 'siswa',
            403,
            'Hanya siswa yang dapat menghapus foto profil.'
        );

        $user = $request->user();
        $oldPath = $user->profile_photo_path;

        // Hapus referensi foto pada akun siswa.
        $user->profile_photo_path = null;
        $user->save();

        // Hapus file lama setelah database berhasil diperbarui.
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'message' => 'Foto profil berhasil dihapus.',
        ]);
    }
}
