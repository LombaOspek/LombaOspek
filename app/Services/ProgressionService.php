<?php

namespace App\Services;

use App\Enums\QuestActionType;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use App\Models\UserQuest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resolusi status modul: menggabungkan tiga sumber kebenaran —
 * progres tersimpan (user_quests), urutan langkah (step_number), dan
 * gerbang waktu (unlock_day).
 *
 * Ini menutup celah lama di mana endpoint `POST /misi/{quest}/complete`
 * menerima modul mana pun, termasuk yang masih terkunci.
 */
class ProgressionService
{
    /**
     * Hari orientasi berjalan, dibatasi 0..totalHari.
     *
     * 0 = belum mulai, 1..N = sedang berjalan, N = hari terakhir (atau setelah
     * acara selesai). Tanpa pembatasan, acara yang sudah lewat akan menampilkan
     * "hari ke-74 dari 8" dan seluruh modul terbuka tanpa alasan yang jelas.
     */
    public function orientationDay(): int
    {
        $start = Carbon::parse(config('ospek.orientation.start_date'))->startOfDay();
        $today = Carbon::today();

        if ($today->lt($start)) {
            return 0;
        }

        $elapsed = (int) $start->diffInDays($today) + 1;

        return min($elapsed, $this->totalOrientationDays());
    }

    /**
     * True bila tanggal orientasi sudah melewati hari terakhir. Dipakai untuk
     * memberi keterangan "orientasi telah berakhir" alih-alih status hari.
     */
    public function hasEnded(): bool
    {
        $start = Carbon::parse(config('ospek.orientation.start_date'))->startOfDay();

        return Carbon::today()->startOfDay()->diffInDays($start, false) < -($this->totalOrientationDays() - 1);
    }

    public function totalOrientationDays(): int
    {
        return (int) config('ospek.orientation.days', 8);
    }

    /**
     * Hari orientasi yang dipakai untuk gating. Saat development (sebelum
     * tanggal mulai) seluruh modul dianggap terbuka agar demo tetap bisa jalan.
     */
    public function effectiveDay(): int
    {
        $current = $this->orientationDay();

        return $current > 0 ? $current : $this->totalOrientationDays();
    }

    /**
     * @return Collection<int, array{quest: Quest, status: QuestStatus, user_quest: ?UserQuest, locked_reason: ?string}>
     */
    public function questBoard(User $user): Collection
    {
        $quests = Quest::query()->ordered()->get();
        $progress = $user->userQuests()->get()->keyBy('quest_id');
        $day = $this->effectiveDay();

        $previousCompleted = true;

        return $quests->map(function (Quest $quest) use ($progress, $day, &$previousCompleted): array {
            /** @var UserQuest|null $userQuest */
            $userQuest = $progress->get($quest->getKey());

            $status = match (true) {
                $userQuest?->status === QuestStatus::Completed => QuestStatus::Completed,
                ! $quest->isUnlockedOnDay($day) => QuestStatus::Locked,
                ! $previousCompleted => QuestStatus::Locked,
                $userQuest?->status === QuestStatus::InProgress => QuestStatus::InProgress,
                default => QuestStatus::Available,
            };

            $lockedReason = match (true) {
                $status !== QuestStatus::Locked => null,
                ! $quest->isUnlockedOnDay($day) => 'Terbuka pada hari ke-'.$quest->unlock_day.' orientasi.',
                default => 'Selesaikan modul sebelumnya terlebih dahulu.',
            };

            // Modul terkunci tidak memutus rantai prasyarat.
            $previousCompleted = $status === QuestStatus::Completed;

            return [
                'quest' => $quest,
                'status' => $status,
                'user_quest' => $userQuest,
                'locked_reason' => $lockedReason,
            ];
        });
    }

    public function activeQuest(User $user): ?Quest
    {
        $board = $this->questBoard($user);

        $candidate = $board->first(fn (array $row): bool => $row['status'] === QuestStatus::InProgress)
            ?? $board->first(fn (array $row): bool => $row['status'] === QuestStatus::Available);

        return $candidate['quest'] ?? null;
    }

    public function canStart(User $user, Quest $quest): bool
    {
        return $this->statusFor($user, $quest)->isOpen();
    }

    public function statusFor(User $user, Quest $quest): QuestStatus
    {
        return $this->questBoard($user)
            ->first(fn (array $row): bool => $row['quest']->is($quest))['status'] ?? QuestStatus::Locked;
    }

    public function markInProgress(User $user, Quest $quest): UserQuest
    {
        return UserQuest::updateOrCreate(
            ['user_id' => $user->getKey(), 'quest_id' => $quest->getKey()],
            ['status' => QuestStatus::InProgress],
        );
    }

    /**
     * Menandai modul selesai + membuka modul berikutnya.
     *
     * XP tidak diberikan di sini — pemanggil yang menentukan nilainya karena
     * modul kuis mendapat XP dari skor, bukan dari kolom quests.xp_reward.
     * `$xpAwarded` hanya dicatat sebagai jejak pada baris progres sehingga
     * riwayat "modul ini dulu memberi berapa" tidak hilang.
     */
    public function complete(User $user, Quest $quest, ?string $note = null, int $xpAwarded = 0): UserQuest
    {
        return DB::transaction(function () use ($user, $quest, $note, $xpAwarded): UserQuest {
            $userQuest = UserQuest::updateOrCreate(
                ['user_id' => $user->getKey(), 'quest_id' => $quest->getKey()],
                [
                    'status' => QuestStatus::Completed,
                    'completed_at' => now(),
                    'note' => $note,
                    'xp_awarded' => $xpAwarded,
                ],
            );

            $next = Quest::query()->where('step_number', '>', $quest->step_number)->ordered()->first();

            if ($next !== null) {
                UserQuest::firstOrCreate(
                    ['user_id' => $user->getKey(), 'quest_id' => $next->getKey()],
                    ['status' => QuestStatus::InProgress],
                );
            }

            $this->syncCompletionFlag($user);

            return $userQuest;
        });
    }

    /**
     * XP untuk modul absensi/refleksi. Modul kuis melewati jalur ini.
     */
    public function checkpointXp(Quest $quest): int
    {
        return $quest->action_type === QuestActionType::Quiz ? 0 : $quest->xp_reward;
    }

    public function completedCount(User $user): int
    {
        return $user->userQuests()->where('status', QuestStatus::Completed)->count();
    }

    public function completionPercentage(User $user, ?int $total = null): int
    {
        $total ??= Quest::query()->count();

        if ($total === 0) {
            return 0;
        }

        return (int) min(100, round(($this->completedCount($user) / $total) * 100));
    }

    private function syncCompletionFlag(User $user): void
    {
        $total = Quest::query()->count();

        if ($total > 0 && $this->completedCount($user) >= $total && $user->orientation_completed_at === null) {
            $user->forceFill(['orientation_completed_at' => now()])->save();
        }
    }
}
