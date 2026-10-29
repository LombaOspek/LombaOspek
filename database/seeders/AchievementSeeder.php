<?php

namespace Database\Seeders;

use App\Enums\AchievementMetric;
use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Setiap lencana terikat pada satu metrik nyata sehingga progresnya
     * dihitung otomatis oleh AchievementService — tidak lagi hardcode.
     */
    public function run(): void
    {
        $achievements = [
            [
                'code' => 'ach-01',
                'title' => 'Langkah Pertama',
                'description' => 'Selesaikan modul orientasi pertama Anda.',
                'icon' => 'flag',
                'category' => 'Perjalanan',
                'metric' => AchievementMetric::QuestsCompleted,
                'xp_reward' => 50,
                'total_steps' => 1,
                'badge_name' => 'Lencana Langkah Pertama',
                'badge_color' => 'teal',
            ],
            [
                'code' => 'ach-02',
                'title' => 'Setengah Jalan',
                'description' => 'Selesaikan empat dari delapan modul orientasi.',
                'icon' => 'map',
                'category' => 'Perjalanan',
                'metric' => AchievementMetric::QuestsCompleted,
                'xp_reward' => 150,
                'total_steps' => 4,
                'badge_name' => 'Lencana Penjelajah Kampus',
                'badge_color' => 'sky',
            ],
            [
                'code' => 'ach-03',
                'title' => 'Cendekiawan Akademik',
                'description' => 'Lulus Kuis Sistem Kredit Semester dengan skor sempurna.',
                'icon' => 'quiz',
                'category' => 'Akademik',
                'metric' => AchievementMetric::PerfectQuizzes,
                'xp_reward' => 200,
                'total_steps' => 1,
                'badge_name' => 'Lencana Cendekiawan',
                'badge_color' => 'amber',
            ],
            [
                'code' => 'ach-04',
                'title' => 'Rentetan Konsisten',
                'description' => 'Buka portal orientasi lima hari berturut-turut.',
                'icon' => 'flame',
                'category' => 'Kedisiplinan',
                'metric' => AchievementMetric::StreakDays,
                'xp_reward' => 180,
                'total_steps' => 5,
                'badge_name' => 'Lencana Rentetan',
                'badge_color' => 'vermilion',
            ],
            [
                'code' => 'ach-05',
                'title' => 'Pemburu Poin',
                'description' => 'Kumpulkan 1.500 XP dari modul dan kuis.',
                'icon' => 'bolt',
                'category' => 'Gamifikasi',
                'metric' => AchievementMetric::TotalXp,
                'xp_reward' => 160,
                'total_steps' => 1500,
                'badge_name' => 'Lencana Pemburu Poin',
                'badge_color' => 'amber',
            ],
            [
                'code' => 'ach-06',
                'title' => 'Sahabat Mentor',
                'description' => 'Dapatkan jawaban atas tiket bantuan yang Anda kirim.',
                'icon' => 'ticket',
                'category' => 'Sosial',
                'metric' => AchievementMetric::HelpTicketsAnswered,
                'xp_reward' => 120,
                'total_steps' => 1,
                'badge_name' => 'Lencana Sahabat Mentor',
                'badge_color' => 'sky',
            ],
            [
                'code' => 'ach-07',
                'title' => 'Mahasiswa Sejati',
                'description' => 'Tuntaskan seluruh delapan modul orientasi.',
                'icon' => 'trophy',
                'category' => 'Perjalanan',
                'metric' => AchievementMetric::QuestsCompleted,
                'xp_reward' => 500,
                'total_steps' => 8,
                'badge_name' => 'Lencana Mahasiswa Sejati',
                'badge_color' => 'teal',
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(
                ['code' => $achievement['code']],
                $achievement + ['is_automatic' => true],
            );
        }
    }
}
