<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LeaderboardService;
use App\Services\ProgressionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $leaderboard,
        private readonly ProgressionService $progression,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('pages.leaderboard', [
            'user' => $user,
            'top' => $this->leaderboard->top(10, $user),
            'myRank' => $this->leaderboard->rankOf($user),
            'participants' => $this->leaderboard->totalParticipants(),
            'groups' => $this->leaderboard->groupStandings(6),
            'completed' => $this->progression->completedCount($user),
        ]);
    }
}
