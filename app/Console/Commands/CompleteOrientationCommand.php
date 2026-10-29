<?php

namespace App\Console\Commands;

use App\Enums\QuestActionType;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\ProgressionService;
use App\Services\QuizService;
use App\Services\StreakService;
use App\Services\XpService;
use Illuminate\Console\Command;

/**
 * Menyelesaikan seluruh modul untuk satu akun — dipakai untuk menyiapkan
 * demonstrasi sertifikat.
 *
 * Sengaja memakai service yang sama dengan alur mahasiswa (bukan update kolom
 * langsung) supaya data hasilnya sah: XP tercatat di buku besar, lencana
 * terbuka lewat evaluasi metrik, dan sertifikat benar-benar memenuhi syarat.
 */
class CompleteOrientationCommand extends Command
{
    protected $signature = 'ospek:complete
                            {nim : NIM mahasiswa yang akan diselesaikan modulnya}
                            {--days-ago=30 : Geser tanggal mulai orientasi ke masa lalu}';

    protected $description = 'Menyelesaikan seluruh modul orientasi untuk satu mahasiswa (untuk demo sertifikat).';

    public function handle(
        ProgressionService $progression,
        XpService $xp,
        QuizService $quiz,
        AchievementService $achievements,
        StreakService $streaks,
    ): int {
        $user = User::query()->where('nim', $this->argument('nim'))->first();

        if ($user === null) {
            $this->error('Mahasiswa dengan NIM '.$this->argument('nim').' tidak ditemukan.');

            return self::FAILURE;
        }

        // Anggap seluruh jadwal orientasi sudah tiba agar gerbang waktu terbuka.
        config(['ospek.orientation.start_date' => now()->subDays((int) $this->option('days-ago'))->toDateString()]);

        $reflection = 'Sesi ini membuat saya memahami bahwa menyesuaikan diri di kampus membutuhkan '
            .'inisiatif pribadi: bertanya ketika belum paham, mencatat kesepakatan kelompok, dan '
            .'menyusun jadwal belajar yang realistis. Langkah berikutnya adalah menyusun rencana '
            .'studi bersama dosen pembimbing dan aktif mengikuti satu unit kegiatan mahasiswa.';

        $streaks->touch($user);

        foreach (Quest::query()->ordered()->get() as $quest) {
            $user->refresh();

            if ($progression->statusFor($user, $quest) === QuestStatus::Completed) {
                $this->line("  lewati  {$quest->code}");

                continue;
            }

            if ($quest->action_type === QuestActionType::Quiz) {
                $result = $quiz->grade($user, $quest->quiz_module_code, [
                    1 => 'B', 2 => 'C', 3 => 'C', 4 => 'B', 5 => 'B',
                ]);

                $this->line(sprintf(
                    '  kuis    %s → %d/%d, +%d XP',
                    $quest->code,
                    $result['attempt']->score,
                    $result['attempt']->total_questions,
                    $result['attempt']->xp_earned,
                ));

                continue;
            }

            $progression->complete($user, $quest, $reflection);
            $awarded = $progression->checkpointXp($quest);

            if ($awarded > 0) {
                $xp->award($user, $awarded, 'quest', $quest->getKey(), "Modul selesai: {$quest->title}", "quest:{$user->getKey()}:{$quest->getKey()}");
            }

            $unlocked = $achievements->sync($user);

            $this->line(sprintf(
                '  modul   %s → +%d XP%s',
                $quest->code,
                $awarded,
                $unlocked->isEmpty() ? '' : ' | lencana: '.$unlocked->pluck('title')->join(', '),
            ));
        }

        $user->refresh();
        $audit = $xp->audit($user);

        $this->newLine();
        $this->info("{$user->name} ({$user->nim})");
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Modul selesai', $progression->completedCount($user).'/'.Quest::count()],
                ['Total XP', number_format($user->xp, 0, ',', '.')],
                ['Level', $user->level.' · '.$user->level_title],
                ['Lencana terbuka', (string) $achievements->board($user)->where('is_unlocked', true)->count()],
                ['Buku besar XP', $audit['consistent'] ? 'konsisten' : 'TIDAK KONSISTEN'],
                ['Selesai pada', $user->orientation_completed_at?->translatedFormat('d F Y') ?? 'belum'],
                ['Kode sertifikat', $user->certificate_code],
            ],
        );

        if (! $audit['consistent']) {
            $this->warn('Selisih kolom vs buku besar: '.$audit['difference'].' XP');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}