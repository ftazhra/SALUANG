<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ParentLinkController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const CODE_LIFETIME = 10;

    /**
     * Siswa membuat kode penghubung.
     */
    public function generate(Request $request): JsonResponse
    {
        $student = $request->user();

        abort_unless(
            $student && $student->role === 'siswa',
            403
        );

        $code = (string) random_int(100000, 999999);

        DB::table('parent_link_codes')->updateOrInsert(
            ['student_id' => $student->id],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(
                    self::CODE_LIFETIME
                ),
                'used_at' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // Kode baru memiliki jatah percobaan baru.
        RateLimiter::clear(
            $this->attemptKey((int) $student->id)
        );

        return response()->json([
            'message' => 'Kode penghubung berhasil dibuat.',
            'code' => $code,
            'expires_in' => 600,
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Orang Tua memasukkan kode dari akun Siswa.
     */
    public function verify(Request $request): RedirectResponse
    {
        $parent = $request->user();

        abort_unless(
            $parent && $parent->role === 'orang_tua',
            403
        );

        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        DB::transaction(function () use ($parent, $data) {
            $relation = DB::table('student_guardians')
                ->join(
                    'users as siswa',
                    'siswa.id',
                    '=',
                    'student_guardians.student_id'
                )
                ->where(
                    'student_guardians.guardian_id',
                    $parent->id
                )
                ->where(
                    'student_guardians.status',
                    'pending'
                )
                ->where('siswa.role', 'siswa')
                ->select('student_guardians.*')
                ->lockForUpdate()
                ->first();

            if (!$relation) {
                throw ValidationException::withMessages([
                    'code' => 'Tidak ada hubungan anak yang menunggu verifikasi.',
                ]);
            }

            $studentId = (int) $relation->student_id;
            $attemptKey = $this->attemptKey($studentId);

            if (RateLimiter::tooManyAttempts(
                $attemptKey,
                self::MAX_ATTEMPTS
            )) {
                throw ValidationException::withMessages([
                    'code' => 'Batas percobaan tercapai. Minta anak membuat kode baru.',
                ]);
            }

            $record = DB::table('parent_link_codes')
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->first();

            $valid = $record
                && $record->used_at === null
                && Carbon::parse(
                    $record->expires_at
                )->isFuture()
                && Hash::check(
                    $data['code'],
                    $record->code_hash
                );

            if (!$valid) {
                RateLimiter::hit($attemptKey, 600);

                throw ValidationException::withMessages([
                    'code' => 'Kode salah, sudah digunakan, atau kedaluwarsa.',
                ]);
            }

            // Tandai kode telah digunakan.
            DB::table('parent_link_codes')
                ->where('id', $record->id)
                ->whereNull('used_at')
                ->update([
                    'used_at' => now(),
                    'updated_at' => now(),
                ]);

            // Aktifkan hubungan Orang Tua dengan Siswa.
            DB::table('student_guardians')
                ->where('id', $relation->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('dashboard')
            ->with(
                'status',
                'Akun Orang Tua berhasil terhubung dengan anak.'
            );
    }

    /**
     * Kunci pembatas percobaan untuk setiap Siswa.
     */
    private function attemptKey(int $studentId): string
    {
        return 'parent-link-attempts:' . $studentId;
    }
}
