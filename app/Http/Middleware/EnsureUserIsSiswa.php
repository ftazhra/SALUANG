<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSiswa
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role === 'siswa', 403, 'Akses hanya untuk Siswa.');

        return $next($request);
    }
}
