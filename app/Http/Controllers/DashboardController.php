<?php

namespace App\Http\Controllers;

use App\Enums\QuestStatus;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\CertificateService;
use App\Services\LeaderboardService;
use App\Services\ProgressionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ProgressionService $progression,
        private readonly LeaderboardService $leaderboard,
        private readonly AchievementService $achievements,
        private readonly CertificateService $certificates,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $board = $this->progression->questBoard($user);
        $totalQuests = $board->count();
        $completed = $board->filter(fn (array $row): bool => $row['status'] === QuestStatus::Completed)->count();
        $percentage = $totalQuests > 0 ? (int) round(($completed / $totalQuests) * 100) : 0;

        $activeRow = $board->first(fn (array $row): bool => $row['status'] === QuestStatus::InProgress)
            ?? $board->first(fn (array $row): bool => $row['status'] === QuestStatus::Available);

        $achievementRows = $this->achievements->board($user);

        return view('pages.dashboard', [
            'user' => $user,
            'board' => $board,
            'activeRow' => $activeRow,
            'totalQuests' => $totalQuests,
            'completedQuests' => $completed,
            'completionPercentage' => $percentage,
            'leaderboard' => $this->leaderboard->top(5, $user),
            'userRank' => $this->leaderboard->rankOf($user),
            'participantCount' => $this->leaderboard->totalParticipants(),
            'groupStandings' => $this->leaderboard->groupStandings(4),
            'achievementRows' => $achievementRows,
            'unlockedAchievements' => $achievementRows->where('is_unlocked', true)->count(),
            'certificate' => $this->certificates->status($user),
            'orientationDay' => $this->progression->orientationDay(),
            'totalDays' => $this->progression->totalOrientationDays(),
            'orientationEnded' => $this->progression->hasEnded(),
            'recentXp' => $user->xpTransactions()->latest('id')->limit(4)->get(),
        ]);
    }
}
