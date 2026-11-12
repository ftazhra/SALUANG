<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSiswa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless(
            $user?->role === 'siswa' && !$user->trashed(),
            403,
            'Akses hanya untuk akun Siswa yang aktif.'
        );

        return $next($request);
    }
}
