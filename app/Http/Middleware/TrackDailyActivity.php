<?php

namespace App\Http\Middleware;

use App\Services\StreakService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencatat aktivitas harian setiap kali mahasiswa membuka portal sehingga
 * angka "rentetan" berasal dari perilaku nyata, bukan nilai seed statis.
 */
class TrackDailyActivity
{
    public function __construct(private readonly StreakService $streaks) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $request->isMethod('GET') && $user->last_active_on?->isToday() !== true) {
            $this->streaks->touch($user);
        }

        return $next($request);
    }
}
