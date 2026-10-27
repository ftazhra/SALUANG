<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsGuru
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role === 'guru', 403, 'Akses hanya untuk Guru.');

        return $next($request);
    }
}