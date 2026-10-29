<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user() ?? User::where('email', 'karan.mehra@maba.ac.id')->first() ?? User::first();

        // Ensure user has quest records
        $quests = Quest::orderBy('step_number')->get()->map(function (Quest $quest) use ($user) {
            $userQuest = $user->userQuests()->where('quest_id', $quest->id)->first();
            $quest->user_status = $userQuest ? $userQuest->status : ($quest->step_number <= 3 ? 'completed' : ($quest->step_number === 4 ? 'in_progress' : 'locked'));

            return $quest;
        });

        $totalQuestsCount = $quests->count();
        $completedQuestsCount = $quests->where('user_status', 'completed')->count();
        $completionPercentage = $totalQuestsCount > 0 ? (int) round(($completedQuestsCount / $totalQuestsCount) * 100) : 0;

        $activeQuest = $quests->firstWhere('user_status', 'in_progress') ?? $quests->first();

        // Top students for campus leaderboard
        $leaderboard = User::orderByDesc('xp')->take(5)->get();

        // User rank position
        $userRank = User::where('xp', '>', $user->xp)->count() + 1;

        // Achievements stats
        $totalAchievements = Achievement::count();
        $unlockedAchievementsCount = $user->userAchievements()->where('is_unlocked', true)->count();

        // Upcoming orientation events schedule
        $events = [
            [
                'time' => '08:30 - 10:00',
                'title' => 'Sesi Bersama Dekan & Pensyarah Teras',
                'location' => 'Auditorium Fakulti Sains Komputer',
                'badge' => 'Wajib',
                'badge_color' => 'bg-rose-100 text-rose-800',
                'is_today' => true,
            ],
            [
                'time' => '10:30 - 12:00',
                'title' => 'Bengkel Pendaftaran Kursus & Penasihat SKS',
                'location' => 'Makmal Al-Khawarizmi Aras 2',
                'badge' => 'Akademik',
                'badge_color' => 'bg-amber-100 text-amber-900',
                'is_today' => true,
            ],
            [
                'time' => '14:00 - 16:30',
                'title' => 'Larian Ria Siswa & Pesta Sukan Kampus',
                'location' => 'Padang Ragbi & Trek Utama',
                'badge' => 'Kokurikulum',
                'badge_color' => 'bg-emerald-100 text-emerald-900',
                'is_today' => false,
            ],
            [
                'time' => '20:00 - 22:30',
                'title' => 'Malam Kemuncak Ikrar & Penyerahan Sijil',
                'location' => 'Dewan Canselor Utama',
                'badge' => 'Rasmi',
                'badge_color' => 'bg-sky-100 text-sky-900',
                'is_today' => false,
            ],
        ];

        return view('pages.dashboard', compact(
            'user',
            'quests',
            'totalQuestsCount',
            'completedQuestsCount',
            'completionPercentage',
            'activeQuest',
            'leaderboard',
            'userRank',
            'totalAchievements',
            'unlockedAchievementsCount',
            'events'
        ));
    }
}
