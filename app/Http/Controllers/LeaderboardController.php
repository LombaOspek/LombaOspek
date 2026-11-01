<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EngagementService;
use App\Services\LeaderboardService;
use App\Services\ProgressionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly ProgressionService $progression,
        private readonly EngagementService $engagement,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $top = $this->leaderboard->top(10, $user);

        // Gelar "Digital Flexing" dihitung sekali di sini, bukan di dalam loop
        // view: peringkat sudah diketahui dari koleksi yang sama, jadi tidak
        // ada query tambahan per baris.
        $titles = $top->mapWithKeys(fn (array $entry): array => [
            $entry['user']->getKey() => $this->engagement->titleFor($entry['user'], $entry['rank']),
        ])->all();

        return view('pages.leaderboard', [
            'user' => $user,
            'top' => $top,
            'myRank' => $this->leaderboard->rankOf($user),
            'participants' => $this->leaderboard->totalParticipants(),
            'groups' => $this->leaderboard->groupStandings(6),
            'completed' => $this->progression->completedCount($user),
            'titles' => $titles,
        ]);
    }
}
