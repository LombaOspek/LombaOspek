<?php

/**
 * Skrip demo: menyelesaikan seluruh modul untuk SATU akun contoh supaya
 * sertifikat bisa diperlihatkan. Dijalankan lewat jalur aplikasi yang sama
 * dengan mahasiswa (service, bukan update kolom langsung) agar data yang
 * dihasilkan benar-benar sah dan konsisten dengan buku besar XP.
 *
 * Pemakaian: php storage/app/demo-complete.php <nim>
 */

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\QuestActionType;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\ProgressionService;
use App\Services\QuizService;
use App\Services\StreakService;
use App\Services\XpService;

$nim = $argv[1] ?? '202653033';

$user = User::query()->where('nim', $nim)->firstOrFail();
$progression = app(ProgressionService::class);
$xp = app(XpService::class);
$quiz = app(QuizService::class);
$achievements = app(AchievementService::class);
$streaks = app(StreakService::class);

// Demi demo: anggap seluruh jadwal orientasi sudah tiba.
config(['ospek.orientation.start_date' => now()->subDays(30)->toDateString()]);

$reflection = 'Sesi ini membuat saya memahami bahwa menyesuaikan diri di kampus '
    .'membutuhkan inisiatif pribadi: bertanya ketika belum paham, mencatat '
    .'kesepakatan kelompok, dan menyusun jadwal belajar yang realistis. '
    .'Langkah berikutnya adalah menyusun rencana studi bersama dosen pembimbing '
    .'dan aktif mengikuti satu unit kegiatan mahasiswa.';

$streaks->touch($user);

foreach (Quest::query()->ordered()->get() as $quest) {
    $user->refresh();

    if ($progression->statusFor($user, $quest) === QuestStatus::Completed) {
        echo "lewati  {$quest->code}\n";

        continue;
    }

    if ($quest->action_type === QuestActionType::Quiz) {
        $result = $quiz->grade($user, $quest->quiz_module_code, [
            1 => 'B', 2 => 'C', 3 => 'C', 4 => 'B', 5 => 'B',
        ]);

        echo sprintf(
            "kuis    %s -> %d/%d, +%d XP\n",
            $quest->code,
            $result['attempt']->score,
            $result['attempt']->total_questions,
            $result['attempt']->xp_earned,
        );

        continue;
    }

    $progression->complete($user, $quest, $reflection);

    $awarded = $progression->checkpointXp($quest);

    if ($awarded > 0) {
        $xp->award($user, $awarded, 'quest', $quest->getKey(), "Modul selesai: {$quest->title}", "quest:{$user->getKey()}:{$quest->getKey()}");
    }

    $unlocked = $achievements->sync($user);

    echo sprintf(
        "modul   %s -> +%d XP%s\n",
        $quest->code,
        $awarded,
        $unlocked->isEmpty() ? '' : ' | lencana: '.$unlocked->pluck('title')->join(', '),
    );
}

$user->refresh();
$audit = $xp->audit($user);

echo "\n=== RINGKASAN {$user->name} ({$user->nim}) ===\n";
echo 'modul selesai : '.$progression->completedCount($user).'/'.Quest::count()."\n";
echo 'total XP      : '.$user->xp."\n";
echo 'level         : '.$user->level.' · '.$user->level_title."\n";
echo 'lencana       : '.$achievements->board($user)->where('is_unlocked', true)->count()."\n";
echo 'XP ledger ok  : '.($audit['consistent'] ? 'ya (kolom = buku besar)' : 'TIDAK')."\n";
echo 'selesai pada  : '.($user->orientation_completed_at?->translatedFormat('d F Y') ?? 'belum')."\n";
echo 'kode          : '.$user->certificate_code."\n";