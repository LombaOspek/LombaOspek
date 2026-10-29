<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Rentetan aktivitas harian (streak) dihitung dari tanggal, bukan disimpan
 * sebagai angka statis seperti sebelumnya.
 */
class StreakService
{
    /**
     * Catat aktivitas hari ini. Aman dipanggil berkali-kali per hari.
     *
     * @return array{streak_days: int, increased: bool}
     */
    public function touch(User $user): array
    {
        $today = Carbon::today();
        $last = $user->last_active_on?->startOfDay();
        $previousStreak = $user->streak_days;

        if ($last !== null && $last->isSameDay($today)) {
            return ['streak_days' => $previousStreak, 'increased' => false];
        }

        $streak = ($last !== null && $last->isSameDay($today->copy()->subDay()))
            ? $previousStreak + 1
            : 1;

        $user->forceFill([
            'streak_days' => $streak,
            'last_active_on' => $today,
        ])->save();

        return ['streak_days' => $streak, 'increased' => true];
    }
}
