<?php

namespace App\Http\Controllers\OrangTua;

use App\Http\Controllers\Controller;
use App\Services\ProfilePhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    private function ensureParent(Request $request): void
    {
        abort_unless($request->user()?->role === 'orang_tua', 403);
    }

    public function update(Request $request): JsonResponse
    {
        $this->ensureParent($request);
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash:ascii', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'nomor_telepon' => ['nullable', 'string', 'max:20'],
        ]);
        DB::transaction(function () use ($user, $data) {
            $user->fill($data);
            $user->save();
        });

        return response()->json([
            'message' => 'Profil Orang Tua berhasil diperbarui.',
            'data' => $user->only(['name', 'username', 'email', 'nomor_telepon']),
        ]);
    }

    public function updatePhoto(
        Request $request,
        ProfilePhotoService $photos
    ): JsonResponse
    {
        $this->ensureParent($request);
        $request->validate([
            'photo' => ['required', File::image()->max(2048)->types(['jpg', 'jpeg', 'png', 'webp'])],
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

    public function destroyPhoto(
        Request $request,
        ProfilePhotoService $photos
    ): JsonResponse
    {
        $this->ensureParent($request);
        $photos->remove($request->user());

        return response()->json([
            'message' => 'Foto profil dihapus dari akun. File lama tetap disimpan.',
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $this->ensureParent($request);
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $user = $request->user();
        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak sesuai.'],
            ]);
        }
        $user->password = Hash::make($data['password']);
        $user->save();

        return response()->json(['message' => 'Kata sandi berhasil diperbarui.']);
    }
}
