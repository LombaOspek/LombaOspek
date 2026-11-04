<?php

namespace App\Http\Controllers;

use App\Enums\QuestStatus;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\CertificateService;
use App\Services\EngagementService;
use App\Services\SideQuestService;
use App\Services\LeaderboardService;
use App\Services\ProgressionService;
use App\Services\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ProgressionService $progression,
        private readonly ScheduleService $schedule,
        private readonly LeaderboardService $leaderboard,
        private readonly AchievementService $achievements,
        private readonly CertificateService $certificates,
        private readonly EngagementService $engagement,
        private readonly SideQuestService $sideQuests,
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

        // ------------------------------------------------------------------
        // Fokus Harian: mencegah cognitive overload dengan memecah papan
        // menjadi tiga hal saja - yang dikerjakan hari ini, yang berikutnya,
        // dan tantangan harian yang terukur dari data nyata.
        // ------------------------------------------------------------------
        $focus = $this->progression->todayFocus($user);
        $nextUp = $this->progression->nextUp($user, 3);
        $chapters = $this->progression->chapters($user);

        // Tantangan harian dihitung dari kejadian hari ini, bukan angka karangan.
        $completedToday = $user->userQuests()
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', Carbon::today())
            ->count();

        $xpToday = (int) $user->xpTransactions()
            ->whereDate('created_at', Carbon::today())
            ->where('amount', '>', 0)
            ->sum('amount');

        $activeToday = $user->last_active_on !== null
            && $user->last_active_on->isSameDay(Carbon::today());

        $dailyChallenges = [
            [
                'key' => 'module',
                'title' => 'Tuntaskan 1 modul hari ini',
                'hint' => 'Satu langkah kecil, satu bab maju.',
                'current' => min($completedToday, 1),
                'target' => 1,
                'done' => $completedToday >= 1,
                'icon' => 'check-circle',
                'tone' => 'brand',
            ],
            [
                'key' => 'xp',
                'title' => 'Kumpulkan 100 XP hari ini',
                'hint' => 'XP dari modul dan kuis sah dihitung otomatis.',
                'current' => min($xpToday, 100),
                'target' => 100,
                'done' => $xpToday >= 100,
                'icon' => 'bolt',
                'tone' => 'gold',
            ],
            [
                'key' => 'streak',
                'title' => 'Jaga rentetan aktif',
                'hint' => 'Buka aplikasi hari ini agar rentetan tidak putus.',
                'current' => $activeToday ? 1 : 0,
                'target' => 1,
                'done' => $activeToday,
                'icon' => 'flame',
                'tone' => 'vermilion',
            ],
        ];

        $challengeDone = collect($dailyChallenges)->where('done', true)->count();

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
            'focus' => $focus,
            // Agenda hari ini dari tabel jadwal, supaya dashboard menyebut
            // acara yang mendahului modul - bukan hanya nomor hari.
            'todayAgenda' => $this->schedule->forDay($user, $this->progression->effectiveDay()),
            'nextUp' => $nextUp,
            'chapters' => $chapters,
            'dailyChallenges' => $dailyChallenges,
            'challengeDone' => $challengeDone,
            'xpToday' => $xpToday,
            'sideQuest' => $this->sideQuests->nextAvailable($user),
            'sideQuestSummary' => $this->sideQuests->summary($user),
            'engagement' => [
                'gugusWars' => $this->engagement->gugusWars($user),
                'dailyBox' => $this->engagement->dailyBox($user),
                'liveFeed' => $this->engagement->liveFeed($user),
                'secret' => [
                    'clicks' => (int) config('ospek.engagement.secret_mission.clicks'),
                    'windowMs' => (int) config('ospek.engagement.secret_mission.window_ms'),
                    'xp' => (int) config('ospek.engagement.secret_mission.xp'),
                ],
            ],
        ]);
    }
}
