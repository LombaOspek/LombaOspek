<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AchievementService;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AchievementController extends Controller
{
    public function __construct(
        private readonly AchievementService $achievements,
        private readonly CertificateService $certificates,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $rows = $this->achievements->board($user);

        return view('pages.achievements', [
            'user' => $user,
            'rows' => $rows,
            'unlockedCount' => $rows->where('is_unlocked', true)->count(),
            'totalAchievements' => $rows->count(),
            'inProgressCount' => $rows->filter(fn (array $row): bool => ! $row['is_unlocked'] && $row['current_steps'] > 0)->count(),
            'lockedCount' => $rows->filter(fn (array $row): bool => ! $row['is_unlocked'] && $row['current_steps'] === 0)->count(),
            'badgeXpTotal' => (int) $rows->where('is_unlocked', true)->sum(fn (array $row): int => $row['achievement']->xp_reward),
            'certificate' => $this->certificates->status($user),
            'metrics' => $this->achievements->metrics($user),
        ]);
    }
}
