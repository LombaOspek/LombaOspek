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
        // mainline() wajib: Side Quest tidak boleh masuk rantai prasyarat.
        // Kalau ikut, satu misi opsional yang belum selesai akan mengunci
        // seluruh modul sesudahnya karena `previousCompleted` jadi false.
        $quests = Quest::query()->published()->mainline()->ordered()->get()
            ->filter(fn (Quest $quest): bool => $quest->targetsStudent($user))
            ->values();

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

    /**
     * Kelompokkan papan misi menjadi babak tematik.
     *
     * Rentang hari tiap babak berasal dari config `ospek.chapters`, sehingga
     * satu palang pembatas menaungi 3-5 node, bukan satu node per hari. Modul
     * yang `unlock_day`-nya tidak tercakup babak mana pun tetap ditampilkan
     * lewat babak cadangan supaya tidak ada node yang hilang dari peta.
     *
     * @return Collection<int, array{key: string, title: string, label: string, tagline: string, narrative: string, icon: string, day_from: int, day_to: int, days: string, rows: Collection, completed: int, total: int, is_current: bool, is_future: bool, is_done: bool}>
     */
    public function chapters(User $user): Collection
    {
        $board = $this->questBoard($user);
        $today = $this->effectiveDay();

        /** @var array<int, array<string, mixed>> $definitions */
        $definitions = config('ospek.chapters', []);

        $chapters = collect($definitions)->values()->map(function (array $definition, int $index) use ($board, $today): array {
            $from = (int) $definition['from'];
            $to = (int) $definition['to'];

            $rows = $board
                ->filter(fn (array $row): bool => (int) $row['quest']->unlock_day >= $from
                    && (int) $row['quest']->unlock_day <= $to)
                ->values();

            return $this->makeChapter($definition, $index, $rows, $today, $from, $to);
        });

        // Jaring pengaman: modul di luar rentang config tetap tampil.
        $orphans = $board->reject(function (array $row) use ($definitions): bool {
            $day = (int) $row['quest']->unlock_day;

            return collect($definitions)->contains(fn (array $d): bool => $day >= (int) $d['from'] && $day <= (int) $d['to']);
        });

        if ($orphans->isNotEmpty()) {
            $first = (int) $orphans->min(fn (array $row): int => (int) $row['quest']->unlock_day);
            $last = (int) $orphans->max(fn (array $row): int => (int) $row['quest']->unlock_day);

            $chapters->push($this->makeChapter([
                'key' => 'lainnya',
                'title' => 'Babak Tambahan',
                'tagline' => 'Modul di luar jadwal utama',
                'narrative' => 'Modul yang ditambahkan panitia di luar rentang babak tematik.',
                'icon' => 'flag',
            ], $chapters->count(), $orphans->values(), $today, $first, $last));
        }

        return $chapters->values();
    }

    /**
     * Rakit satu babak tematik dari definisi config dan baris papan misi.
     *
     * @param  array<string, mixed>  $definition
     * @param  Collection<int, array{quest: Quest, status: QuestStatus, user_quest: ?UserQuest, locked_reason: ?string}>  $rows
     * @return array{key: string, title: string, label: string, tagline: string, narrative: string, icon: string, day_from: int, day_to: int, days: string, rows: Collection, completed: int, total: int, is_current: bool, is_future: bool, is_done: bool}
     */
    private function makeChapter(array $definition, int $index, Collection $rows, int $today, int $from, int $to): array
    {
        $completed = $rows->filter(fn (array $row): bool => $row['status'] === QuestStatus::Completed)->count();
        $total = $rows->count();

        $days = $from === $to ? 'Hari '.$from : 'Hari '.$from.'-'.$to;

        // Babak berjalan = babak yang rentang harinya memuat hari efektif.
        $isCurrent = $today >= $from && $today <= $to;

        return [
            'key' => (string) ($definition['key'] ?? 'babak-'.($index + 1)),
            'title' => (string) $definition['title'],
            'label' => 'BAB '.($index + 1).': '.$definition['title'],
            'tagline' => (string) ($definition['tagline'] ?? ''),
            'narrative' => (string) ($definition['narrative'] ?? ''),
            'icon' => (string) ($definition['icon'] ?? 'flag'),
            'day_from' => $from,
            'day_to' => $to,
            'days' => $days,
            'rows' => $rows,
            'completed' => $completed,
            'total' => $total,
            'is_current' => $isCurrent,
            'is_future' => $from > $today,
            'is_done' => $total > 0 && $completed === $total,
        ];
    }

    /**
     * Modul yang jatuh pada hari orientasi berjalan — bahan widget
     * "Fokus Hari Ini". Bila hari ini tidak punya modul (misal acara sudah
     * lewat), dipakai modul terbuka terdekat agar widget tidak kosong.
     *
     * @return array{rows: Collection, completed: int, total: int, percentage: int, day: int}
     */
    public function todayFocus(User $user): array
    {
        $board = $this->questBoard($user);
        $day = $this->effectiveDay();

        $rows = $board
            ->filter(fn (array $row): bool => (int) $row['quest']->unlock_day === $day)
            ->values();

        if ($rows->isEmpty()) {
            $rows = $board
                ->filter(fn (array $row): bool => $row['status'] !== QuestStatus::Locked)
                ->take(1)
                ->values();
        }

        $total = $rows->count();
        $completed = $rows->filter(fn (array $row): bool => $row['status'] === QuestStatus::Completed)->count();

        return [
            'rows' => $rows,
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            'day' => $day,
        ];
    }

    /**
     * Modul terdekat yang belum selesai (maksimal $limit), untuk widget
     * "Misi Selanjutnya". Urut sesuai langkah, jadi yang teratas adalah
     * yang paling mendesak.
     *
     * @return Collection<int, array{quest: Quest, status: QuestStatus, user_quest: ?UserQuest, locked_reason: ?string}>
     */
    public function nextUp(User $user, int $limit = 3): Collection
    {
        return $this->questBoard($user)
            ->reject(fn (array $row): bool => $row['status'] === QuestStatus::Completed)
            ->take($limit)
            ->values();
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
        /*
         * Side Quest TIDAK ada di `questBoard()` — papan itu sengaja hanya
         * memuat modul utama (`mainline()`), kalau tidak satu misi opsional
         * yang belum selesai akan mengunci seluruh modul sesudahnya.
         *
         * Akibatnya, sebelum perbaikan ini, `statusFor()` selalu mengembalikan
         * `Locked` untuk Side Quest: halaman detailnya menampilkan "Modul masih
         * terkunci" beserta tombol "Lihat modul yang terbuka", sehingga
         * mahasiswa tidak pernah bisa mengunggah bukti foto Side Quest.
         *
         * Statusnya dihitung dari syaratnya sendiri: jumlah modul utama selesai
         * dibandingkan dengan `required_completed_modules`.
         */
        if ($quest->is_side_quest) {
            return $this->sideQuestStatus($user, $quest);
        }

        return $this->questBoard($user)
            ->first(fn (array $row): bool => $row['quest']->is($quest))['status'] ?? QuestStatus::Locked;
    }

    /**
     * Status Side Quest: selesai, sedang dikerjakan, terbuka, atau terkunci
     * karena syarat jumlah modul utama belum terpenuhi.
     */
    private function sideQuestStatus(User $user, Quest $quest): QuestStatus
    {
        /** @var UserQuest|null $userQuest */
        $userQuest = $user->userQuests()->where('quest_id', $quest->getKey())->first();

        if ($userQuest?->status === QuestStatus::Completed) {
            return QuestStatus::Completed;
        }

        $required = (int) ($quest->required_completed_modules ?? 0);
        $completed = $this->completedCount($user);

        if ($completed < $required) {
            return QuestStatus::Locked;
        }

        if ($userQuest?->status === QuestStatus::InProgress) {
            return QuestStatus::InProgress;
        }

        return QuestStatus::Available;
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

    /**
     * Hanya modul utama. Side Quest opsional, jadi menyelesaikannya tidak
     * boleh menggeser persentase penyelesaian maupun memicu sertifikat.
     */
    public function completedCount(User $user): int
    {
        return $user->userQuests()
            ->where('status', QuestStatus::Completed)
            ->whereHas('quest', fn ($query) => $query->mainline())
            ->count();
    }

    public function completionPercentage(User $user, ?int $total = null): int
    {
        $total ??= Quest::query()->mainline()->count();

        if ($total === 0) {
            return 0;
        }

        return (int) min(100, round(($this->completedCount($user) / $total) * 100));
    }

    private function syncCompletionFlag(User $user): void
    {
        // Total memakai mainline() supaya Side Quest yang belum dikerjakan
        // tidak menahan sertifikat selamanya.
        $total = Quest::query()->mainline()->count();

        if ($total > 0 && $this->completedCount($user) >= $total && $user->orientation_completed_at === null) {
            $user->forceFill(['orientation_completed_at' => now()])->save();
        }
    }
}
