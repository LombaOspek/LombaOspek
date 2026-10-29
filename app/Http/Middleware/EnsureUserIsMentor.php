<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsMentor
{
    /**
     * Gerbang peran untuk endpoint mentor (balas tiket bantuan).
     * Tanpa ini, mahasiswa bisa membalas tiketnya sendiri.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isMentor()) {
            abort(403, 'Halaman ini hanya untuk mentor orientasi.');
        }

        return $next($request);
    }
}
