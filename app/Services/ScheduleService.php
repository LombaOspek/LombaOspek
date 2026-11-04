<?php

namespace App\Services;

use App\Models\OrientationSchedule;
use App\Models\Quest;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Menyatukan jadwal acara dengan peta misi.
 *
 * Sebelum ini mahasiswa hanya melihat "modul hari ke-3 terbuka" tanpa tahu
 * acara apa yang mendahuluinya, dan panitia hanya punya angka hari tanpa
 * tempat mencatat agenda. Service ini yang menjawab satu pertanyaan:
 * "setelah acara ini selesai, apa yang harus saya kerjakan?"
 */
class ScheduleService
{
    public function __construct(private readonly ProgressionService $progression) {}

    /**
     * Agenda satu hari, masing-masing membawa status modulnya untuk pengguna.
     *
     * @return Collection<int, array{schedule: OrientationSchedule, quest: Quest|null, quest_status: string}>
     */
    public function forDay(User $user, int $day): Collection
    {
        $schedules = OrientationSchedule::query()
            ->with('quest')
            ->forDay($day)
            ->get();

        $completed = $this->completedQuestIds($user);

        return $schedules->map(fn (OrientationSchedule $schedule): array => [
            'schedule' => $schedule,
            'quest' => $schedule->quest,
            'quest_status' => $this->questStatus($schedule->quest, $completed, $user, $day),
        ]);
    }

    /**
     * Seluruh 8 hari sekaligus, untuk halaman jadwal.
     *
     * @return Collection<int, array{day: int, is_today: bool, is_past: bool, items: Collection}>
     */
    public function allDays(User $user): Collection
    {
        $today = $this->progression->effectiveDay();
        $total = $this->progression->totalOrientationDays();

        $byDay = OrientationSchedule::query()
            ->with('quest')
            ->orderBy('day')
            ->orderBy('starts_at')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('day');

        $completed = $this->completedQuestIds($user);

        return collect(range(1, $total))->map(function (int $day) use ($byDay, $today, $completed, $user): array {
            $items = ($byDay[$day] ?? collect())->map(fn (OrientationSchedule $schedule): array => [
                'schedule' => $schedule,
                'quest' => $schedule->quest,
                'quest_status' => $this->questStatus($schedule->quest, $completed, $user, $day),
            ]);

            return [
                'day' => $day,
                'is_today' => $day === $today,
                'is_past' => $day < $today,
                'items' => $items,
            ];
        });
    }

    /**
     * Agenda hari ini yang modulnya masih terbuka — dipakai dashboard sebagai
     * "langkah berikutnya setelah acara".
     *
     * @return array{schedule: OrientationSchedule, quest: Quest}|null
     */
    public function nextActionable(User $user): ?array
    {
        $day = $this->progression->effectiveDay();
        $completed = $this->completedQuestIds($user);

        $row = OrientationSchedule::query()
            ->with('quest')
            ->forDay($day)
            ->whereNotNull('quest_id')
            ->get()
            ->first(fn (OrientationSchedule $s): bool => $s->quest !== null && ! $completed->contains($s->quest->getKey()));

        return $row === null ? null : ['schedule' => $row, 'quest' => $row->quest];
    }

    /**
     * Jumlah agenda per hari — untuk ringkasan "hari ini 3 agenda".
     *
     * @return Collection<int, int>
     */
    public function countsByDay(): Collection
    {
        return OrientationSchedule::query()
            ->selectRaw('day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');
    }

    /** @return Collection<int, int> */
    private function completedQuestIds(User $user): Collection
    {
        return $user->quests()
            ->wherePivot('status', 'completed')
            ->pluck('quests.id');
    }

    /**
     * Status modul dari sudut pandang mahasiswa.
     *
     * "Terkunci jadwal" dibedakan dari "belum dikerjakan": mahasiswa perlu tahu
     * apakah ia harus menunggu acara, atau memang belum mengerjakan tugasnya.
     */
    private function questStatus(?Quest $quest, Collection $completed, User $user, int $day): string
    {
        if ($quest === null) {
            return 'none';
        }

        if ($completed->contains($quest->getKey())) {
            return 'done';
        }

        if ($quest->unlock_day > $day) {
            return 'locked';
        }

        return 'open';
    }
}