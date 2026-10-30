<?php

namespace Database\Seeders;

use App\Enums\QuestStatus;
use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Quest;
use App\Models\User;
use App\Models\UserQuest;
use App\Services\ProgressionService;
use App\Services\XpService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Data mengacu pada KAK & Guidebook INVOFEST 2026:
     * Universitas Muria Kudus, Program Studi Sistem Informasi.
     */
    public function run(): void
    {
        $this->call([
            GroupSeeder::class,
            QuestSeeder::class,
            QuizQuestionSeeder::class,
            AchievementSeeder::class,
            FaqSeeder::class,
        ]);

        $this->seedDemoStudents();
        $this->backfillProgress();
    }

    private function seedDemoStudents(): void
    {
        $garuda = Group::query()->where('code', 'G-04')->first();

        // Akun demo utama: NIM nyata dari KAK (202653033).
        $demo = User::updateOrCreate(
            ['nim' => '202653033'],
            [
                'name' => 'Aldiansyah Fayruz',
                'email' => '202653033@students.umk.ac.id',
                'password' => Hash::make('password'),
                'role' => UserRole::Student,
                'faculty' => 'Fakultas Teknik',
                'major' => 'Sistem Informasi',
                'group_id' => $garuda?->id,
                'group_name' => $garuda?->name ?? 'Kelompok 04 — Garuda',
                'mentor_name' => 'Kak Nissa · Ruang Bimbingan 4B',
                'phone' => '0812-3456-7890',
                'xp' => 0,
                'level' => 1,
                'streak_days' => 0,
                'sound_effects' => true,
                'email_notifications' => true,
                'whatsapp_notifications' => true,
                'dark_mode' => false,
            ],
        );

        // Rekan satu kelompok untuk mengisi papan peringkat. XP-nya berasal dari
        // transaksi sah agar buku besar tetap konsisten.
        $peers = [
            ['nim' => '202653044', 'name' => 'Muhammad Viza Fairuzzeka', 'group' => 'G-04', 'major' => 'Sistem Informasi', 'xp' => 1180],
            ['nim' => '202653067', 'name' => 'Vicko Amelino Syahputra', 'group' => 'G-02', 'major' => 'Sistem Informasi', 'xp' => 960],
            ['nim' => '202653012', 'name' => 'Aisyah Nur Fadhilah', 'group' => 'G-02', 'major' => 'Sistem Informasi', 'xp' => 1420],
            ['nim' => '202653078', 'name' => 'Bagas Dwi Prasetyo', 'group' => 'G-04', 'major' => 'Informatika', 'xp' => 1240],
            ['nim' => '202653101', 'name' => 'Citra Ayu Lestari', 'group' => 'G-01', 'major' => 'Sistem Informasi', 'xp' => 880],
            ['nim' => '202653055', 'name' => 'Dimas Arya Saputra', 'group' => 'G-03', 'major' => 'Informatika', 'xp' => 640],
        ];

        $xpService = app(XpService::class);

        foreach ($peers as $peer) {
            $group = Group::query()->where('code', $peer['group'])->first();

            $user = User::updateOrCreate(
                ['nim' => $peer['nim']],
                [
                    'name' => $peer['name'],
                    'email' => $peer['nim'].'@students.umk.ac.id',
                    'password' => Hash::make('password'),
                    'role' => UserRole::Student,
                    'faculty' => 'Fakultas Teknik',
                    'major' => $peer['major'],
                    'group_id' => $group?->id,
                    'group_name' => $group?->name,
                    'mentor_name' => $group?->mentor_name,
                    'xp' => 0,
                    'level' => 1,
                    'streak_days' => 0,
                ],
            );

            if ($user->xpTransactions()->doesntExist() && $peer['xp'] > 0) {
                $xpService->award(
                    $user,
                    $peer['xp'],
                    'seed',
                    null,
                    'Poin partisipasi orientasi',
                    "seed:{$user->getKey()}",
                );
            }
        }

        unset($demo);
    }

    /**
     * Memberi setiap mahasiswa status modul yang konsisten dengan gerbang
     * waktu, tanpa memberi XP palsu.
     */
    private function backfillProgress(): void
    {
        $progression = app(ProgressionService::class);

        User::query()->where('role', UserRole::Student)->each(function (User $user) use ($progression): void {
            foreach (Quest::query()->published()->ordered()->get() as $quest) {
                if (! $quest->targetsStudent($user)) {
                    continue;
                }

                if (UserQuest::query()->where('user_id', $user->getKey())->where('quest_id', $quest->getKey())->exists()) {
                    continue;
                }

                $status = $progression->statusFor($user, $quest);

                UserQuest::create([
                    'user_id' => $user->getKey(),
                    'quest_id' => $quest->getKey(),
                    'status' => $status === QuestStatus::Locked ? QuestStatus::Locked : $status,
                ]);
            }
        });
    }
}