<?php

namespace App\Services;

use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Side Quest — misi sosial opsional.
 *
 * Berbeda dari modul utama dalam tiga hal, dan ketiganya disengaja:
 *
 * 1. TIDAK ADA RANTAI PRASYARAT. Menyelesaikan Side Quest tidak membuka modul
 *    berikutnya, dan melewatkannya tidak mengunci apa pun. Kalau dipaksa masuk
 *    rantai, satu misi opsional bisa menggagalkan seluruh perjalanan.
 * 2. TIDAK DIHITUNG GERBANG BABAK. Peta misi tetap 13 node utama; Side Quest
 *    punya daftarnya sendiri agar "0/4 modul" pada babak tetap bermakna.
 * 3. GERBANGNYA ADALAH PROGRES, bukan hari. Side Quest terbuka setelah
 *    sejumlah modul utama selesai, supaya mahasiswa tidak disuruh menyapa
 *    orang lain sebelum tahu apa itu gugus.
 */
class SideQuestService
{
    /**
     * Daftar Side Quest beserta status untuk satu mahasiswa.
     *
     * @return Collection<int, array{quest: Quest, status: QuestStatus, locked_reason: string|null, user_quest: mixed}>
     */
    public function board(User $user): Collection
    {
        $completedModules = $this->completedModuleCount($user);

        $progress = $user->userQuests()
            ->whereIn('quest_id', Quest::query()->sideQuests()->pluck('id'))
            ->get()
            ->keyBy('quest_id');

        return Quest::query()
            ->published()
            ->sideQuests()
            ->orderBy('required_completed_modules')
            ->orderBy('step_number')
            ->get()
            ->filter(fn (Quest $quest): bool => $quest->targetsStudent($user))
            ->values()
            ->map(function (Quest $quest) use ($progress, $completedModules): array {
                $userQuest = $progress->get($quest->getKey());

                $status = match (true) {
                    $userQuest?->status === QuestStatus::Completed => QuestStatus::Completed,
                    $userQuest?->status === QuestStatus::InProgress => QuestStatus::InProgress,
                    $completedModules < $quest->required_completed_modules => QuestStatus::Locked,
                    default => QuestStatus::Available,
                };

                return [
                    'quest' => $quest,
                    'status' => $status,
                    'user_quest' => $userQuest,
                    'locked_reason' => $status === QuestStatus::Locked
                        ? sprintf(
                            'Selesaikan %d modul utama dulu (%d/%d).',
                            $quest->required_completed_modules,
                            min($completedModules, $quest->required_completed_modules),
                            $quest->required_completed_modules,
                        )
                        : null,
                ];
            });
    }

    /**
     * Side Quest yang sedang bisa dikerjakan. Dipakai dashboard untuk satu
     * ajakan saja — daftar panjang di beranda justru mengembalikan cognitive
     * overload yang sudah dibereskan lewat konsep "Fokus Harian".
     */
    public function nextAvailable(User $user): ?array
    {
        return $this->board($user)
            ->first(fn (array $row): bool => in_array($row['status'], [QuestStatus::InProgress, QuestStatus::Available], true));
    }

    /**
     * @return array{total: int, completed: int, available: int, locked: int, bonus_xp: int}
     */
    public function summary(User $user): array
    {
        $board = $this->board($user);

        return [
            'total' => $board->count(),
            'completed' => $board->where('status', QuestStatus::Completed)->count(),
            'available' => $board->where('status', QuestStatus::Available)->count(),
            'locked' => $board->where('status', QuestStatus::Locked)->count(),
            'bonus_xp' => (int) $board
                ->where('status', QuestStatus::Completed)
                ->sum(fn (array $row): int => $row['quest']->xp_reward),
        ];
    }

    /**
     * Hanya modul utama yang dihitung. Kalau Side Quest ikut dihitung, syarat
     * "setelah 5 modul" bisa terpenuhi oleh misi sampingan dan gerbangnya
     * kehilangan arti.
     */
    public function completedModuleCount(User $user): int
    {
        return $user->userQuests()
            ->where('status', QuestStatus::Completed)
            ->whereHas('quest', fn ($query) => $query->mainline())
            ->count();
    }
}