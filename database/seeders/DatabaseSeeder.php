<?php

namespace Database\Seeders;

use App\Enums\QuestStatus;
use App\Enums\UserRole;
use App\Models\Quest;
use App\Models\User;
use App\Models\UserQuest;
use App\Services\AchievementService;
use App\Services\ProgressionService;
use App\Services\XpService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seluruh data mengacu pada KAK & Guidebook INVOFEST 2026:
     * Universitas Muria Kudus, Program Studi Sistem Informasi.
     */
    public function run(): void
    {
        $this->call([
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
                'group_name' => 'Kelompok 04 — Garuda',
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

        $mentor = User::updateOrCreate(
            ['nim' => 'MENTOR-01'],
            [
                'name' => 'Kak Nissa',
                'email' => 'nissa@mentor.umk.ac.id',
                'password' => Hash::make('password'),
                'role' => UserRole::Mentor,
                'faculty' => 'Fakultas Teknik',
                'major' => 'Sistem Informasi',
                'group_name' => 'Kelompok 04 — Garuda',
                'mentor_name' => null,
                'phone' => '0838-2093-1296',
                'xp' => 0,
                'level' => 1,
                'streak_days' => 0,
            ],
        );

        // Rekan satu kelompok untuk mengisi papan peringkat. XP-nya berasal dari
        // transaksi sah agar buku besar tetap konsisten.
        $peers = [
            ['nim' => '202653044', 'name' => 'Muhammad Viza Fairuzzeka', 'group' => 'Kelompok 04 — Garuda', 'xp' => 1180],
            ['nim' => '202653067', 'name' => 'Vicko Amelino Syahputra', 'group' => 'Kelompok 07 — Rajawali', 'xp' => 960],
            ['nim' => '202653012', 'name' => 'Aisyah Nur Fadhilah', 'group' => 'Kelompok 02 — Elang', 'xp' => 1420],
            ['nim' => '202653078', 'name' => 'Bagas Dwi Prasetyo', 'group' => 'Kelompok 05 — Banteng', 'xp' => 1240],
            ['nim' => '202653101', 'name' => 'Citra Ayu Lestari', 'group' => 'Kelompok 01 — Merpati', 'xp' => 880],
            ['nim' => '202653055', 'name' => 'Dimas Arya Saputra', 'group' => 'Kelompok 03 — Kancil', 'xp' => 640],
        ];

        $xpService = app(XpService::class);

        foreach ($peers as $peer) {
            $user = User::updateOrCreate(
                ['nim' => $peer['nim']],
                [
                    'name' => $peer['name'],
                    'email' => $peer['nim'].'@students.umk.ac.id',
                    'password' => Hash::make('password'),
                    'role' => UserRole::Student,
                    'faculty' => 'Fakultas Teknik',
                    'major' => 'Sistem Informasi',
                    'group_name' => $peer['group'],
                    'mentor_name' => 'Kak Nissa · Ruang Bimbingan 4B',
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

        // Mahasiswa baru sungguhan: modul pertama sudah terbuka, bukan selesai.
        $this->initialiseProgress($demo);
        $this->initialiseProgress($mentor);
    }

    /**
     * Memberi setiap mahasiswa status modul yang konsisten dengan gerbang
     * waktu, tanpa memberi XP palsu.
     */
    private function backfillProgress(): void
    {
        $progression = app(ProgressionService::class);
        $achievements = app(AchievementService::class);

        User::query()->where('role', UserRole::Student)->each(function (User $user) use ($progression): void {
            $progression->questBoard($user);

            foreach (Quest::query()->ordered()->get() as $quest) {
                $status = $progression->statusFor($user, $quest);

                $existing = UserQuest::query()
                    ->where('user_id', $user->getKey())
                    ->where('quest_id', $quest->getKey())
                    ->first();

                if ($existing !== null) {
                    continue;
                }

                UserQuest::create([
                    'user_id' => $user->getKey(),
                    'quest_id' => $quest->getKey(),
                    'status' => $status === QuestStatus::Locked ? QuestStatus::Locked : $status,
                ]);
            }
        });
    }

    private function initialiseProgress(User $user): void
    {
        // Tidak ada baris progres awal: ProgressionService yang menentukan
        // modul mana yang terbuka berdasarkan urutan dan hari orientasi.
    }
}
