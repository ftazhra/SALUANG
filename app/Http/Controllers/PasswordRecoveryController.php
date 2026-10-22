<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PasswordRecoveryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | TAMPILKAN HALAMAN LUPA KATA SANDI
    |--------------------------------------------------------------------------
    */

    public function show(): View
    {
        return view('auth.forgot-password');
    }

    /*
    |--------------------------------------------------------------------------
    | SIMPAN PERMINTAAN PEMULIHAN MELALUI EMAIL
    |--------------------------------------------------------------------------
    |
    | Mendukung akun Siswa, Guru, dan Orang Tua.
    | Ini adalah pengajuan untuk diverifikasi sekolah,
    | bukan proses reset password secara otomatis.
    |
    */

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ]);

        $email = trim($data['email']);

        /*
        | Cari akun SALUANG berdasarkan Email.
        | Tidak dibatasi untuk satu peran tertentu.
        */
        $user = User::query()
            ->whereIn('role', [
                'siswa',
                'guru',
                'orang_tua',
            ])
            ->where('email', $email)
            ->first();

        /*
        | Jika akun ditemukan, simpan permintaan.
        | Hindari membuat permintaan pending berulang.
        */
        if ($user) {
            DB::transaction(function () use ($user) {

                $existing = DB::table('password_recovery_requests')
                    ->where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if (!$existing) {
                    DB::table('password_recovery_requests')->insert([
                        'user_id' => $user->id,
                        'status' => 'pending',
                        'resolved_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
        }

        /*
        | Pesan dibuat sama, baik akun ditemukan
        | maupun tidak, untuk melindungi informasi akun.
        */
        return back()->with(
            'status',
            'Jika alamat email terdaftar di SALUANG, ' .
            'permintaan pemulihan akan dicatat. ' .
            'Silakan hubungi pihak sekolah untuk verifikasi.'
        );
    }
}
