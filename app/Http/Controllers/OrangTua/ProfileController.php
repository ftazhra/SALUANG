<?php

namespace App\Http\Controllers\OrangTua;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public function updatePhoto(Request $request): JsonResponse
    {
        $this->ensureParent($request);
        $request->validate([
            'photo' => ['required', File::image()->max('2mb')->types(['jpg', 'jpeg', 'png', 'webp'])],
        ]);

        $user = $request->user();
        $oldPath = $user->profile_photo_path;
        $newPath = $request->file('photo')->store('profile-photos', 'public');
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
            'message' => 'Foto profil berhasil diperbarui.',
            'photo_url' => asset('storage/' . $newPath),
        ]);
    }

    public function destroyPhoto(Request $request): JsonResponse
    {
        $this->ensureParent($request);
        $user = $request->user();
        $oldPath = $user->profile_photo_path;
        $user->profile_photo_path = null;
        $user->save();
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json(['message' => 'Foto profil berhasil dihapus.']);
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
