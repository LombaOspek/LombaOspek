<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AchievementController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user() ?? User::where('email', 'karan.mehra@maba.ac.id')->first() ?? User::first();

        $achievements = Achievement::all()->map(function (Achievement $ach) use ($user) {
            $userAch = $user->userAchievements()->where('achievement_id', $ach->id)->first();
            $ach->current_steps = $userAch ? $userAch->current_steps : 0;
            $ach->is_unlocked = $userAch ? $userAch->is_unlocked : false;
            $ach->unlocked_at = $userAch ? $userAch->unlocked_at : null;
            $ach->progress_percentage = $ach->total_steps > 0 ? min(100, (int) round(($ach->current_steps / $ach->total_steps) * 100)) : 0;

            return $ach;
        });

        $totalAchievements = $achievements->count();
        $unlockedCount = $achievements->where('is_unlocked', true)->count();
        $inProgressCount = $achievements->where('is_unlocked', false)->where('current_steps', '>', 0)->count();
        $lockedCount = $achievements->where('is_unlocked', false)->where('current_steps', 0)->count();

        // Badges collection
        $badges = [
            [
                'name' => 'Lencana Mahasiswa Sejati',
                'description' => 'Tamat semua modul orientasi kampus 2025.',
                'icon' => '🎖️',
                'color' => 'bg-[#F5A25D]',
                'is_unlocked' => ($unlockedCount >= 5),
                'rarity' => 'Emas • Tahap 1',
            ],
            [
                'name' => 'Lencana Kutu Buku Kampus',
                'description' => 'Akses penuh perpustakaan digital EZProxy.',
                'icon' => '📚',
                'color' => 'bg-[#A5C9CA]',
                'is_unlocked' => true,
                'rarity' => 'Perak',
            ],
            [
                'name' => 'Lencana Pemburu Misi',
                'description' => 'Selesaikan 5 misi berturut-turut.',
                'icon' => '🎯',
                'color' => 'bg-[#F9D976]',
                'is_unlocked' => true,
                'rarity' => 'Gangsa',
            ],
            [
                'name' => 'Lencana Sahabat Kampus',
                'description' => 'Sosialisasi aktif bersama rakan kumpulan.',
                'icon' => '🤝',
                'color' => 'bg-[#FA7970]',
                'is_unlocked' => true,
                'rarity' => 'Perak',
            ],
            [
                'name' => 'Lencana Penjelajah Kampus',
                'description' => 'Daftar masuk di 6 mercu tanda penting universiti.',
                'icon' => '🏛️',
                'color' => 'bg-[#FFE6AF]',
                'is_unlocked' => true,
                'rarity' => 'Perak',
            ],
            [
                'name' => 'Lencana Cendekiawan SKS',
                'description' => 'Skor 100% dalam kuiz interaktif modul orientasi.',
                'icon' => '⚡',
                'color' => 'bg-[#E78B24]',
                'is_unlocked' => ($user->quizAttempts()->where('score', 5)->exists()),
                'rarity' => 'Emas',
            ],
        ];

        $unlockedBadgesCount = collect($badges)->where('is_unlocked', true)->count();

        return view('pages.achievements', compact(
            'user',
            'achievements',
            'totalAchievements',
            'unlockedCount',
            'inProgressCount',
            'lockedCount',
            'badges',
            'unlockedBadgesCount'
        ));
    }

    public function certificate(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user() ?? User::where('email', 'karan.mehra@maba.ac.id')->first() ?? User::first();

        return view('pages.certificate', compact('user'));
    }
}
