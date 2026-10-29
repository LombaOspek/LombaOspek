<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Models\User;
use App\Models\UserQuest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuestController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user() ?? User::where('email', 'karan.mehra@maba.ac.id')->first() ?? User::first();

        $quests = Quest::orderBy('step_number')->get()->map(function (Quest $quest) use ($user) {
            $userQuest = $user->userQuests()->where('quest_id', $quest->id)->first();
            $quest->user_status = $userQuest ? $userQuest->status : ($quest->step_number <= 3 ? 'completed' : ($quest->step_number === 4 ? 'in_progress' : 'locked'));

            return $quest;
        });

        $totalQuestsCount = $quests->count();
        $completedQuestsCount = $quests->where('user_status', 'completed')->count();
        $completionPercentage = $totalQuestsCount > 0 ? (int) round(($completedQuestsCount / $totalQuestsCount) * 100) : 0;
        $activeQuest = $quests->firstWhere('user_status', 'in_progress');

        return view('pages.missions', compact(
            'user',
            'quests',
            'totalQuestsCount',
            'completedQuestsCount',
            'completionPercentage',
            'activeQuest'
        ));
    }

    public function complete(Request $request, Quest $quest): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::where('email', 'karan.mehra@maba.ac.id')->first() ?? User::first();

        $userQuest = UserQuest::firstOrCreate(
            ['user_id' => $user->id, 'quest_id' => $quest->id],
            ['status' => 'in_progress']
        );

        if ($userQuest->status !== 'completed') {
            $userQuest->status = 'completed';
            $userQuest->completed_at = now();
            $userQuest->save();

            // Reward XP
            $user->addXp($quest->xp_reward);

            // Unlock next quest in sequence
            $nextQuest = Quest::where('step_number', $quest->step_number + 1)->first();
            if ($nextQuest) {
                UserQuest::updateOrCreate(
                    ['user_id' => $user->id, 'quest_id' => $nextQuest->id],
                    ['status' => 'in_progress']
                );
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Misi '.$quest->title.' berjaya diselesaikan! +'.$quest->xp_reward.' XP',
                'user_xp' => $user->xp,
                'user_level' => $user->level,
                'rank_title' => $user->rank_title,
            ]);
        }

        return back()->with('toast', [
            'title' => 'Misi Selesai!',
            'message' => $quest->title.' (+'.$quest->xp_reward.' XP)',
            'icon' => '🎉',
        ]);
    }
}
