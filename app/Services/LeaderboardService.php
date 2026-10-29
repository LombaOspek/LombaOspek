<?php

namespace App\Services;

use App\Enums\QuestStatus;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Peringkat kampus. Memakai satu query agregat sehingga tidak menimbulkan
 * N+1 seperti saat data diambil per baris di view.
 */
class LeaderboardService
{
    /**
     * @return Collection<int, array{rank: int, user: User, is_me: bool}>
     */
    public function top(int $limit = 5, ?User $viewer = null): Collection
    {
        $rows = User::query()
            ->where('xp', '>', 0)
            ->orderByDesc('xp')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $ranked = $rows->values()->map(fn (User $user, int $index): array => [
            'rank' => $index + 1,
            'user' => $user,
            'is_me' => $viewer !== null && $user->is($viewer),
        ]);

        // Penonton selalu melihat posisinya sendiri, meski di luar 5 besar.
        // Tanpa ini "Peringkat 7/7" muncul tanpa baris yang menjelaskan.
        if ($viewer !== null && ! $ranked->contains(fn (array $row): bool => $row['is_me'])) {
            $ranked->push([
                'rank' => $this->rankOf($viewer),
                'user' => $viewer,
                'is_me' => true,
            ]);
        }

        return $ranked;
    }

    public function rankOf(User $user): int
    {
        return User::query()
            ->where('xp', '>', $user->xp)
            ->count() + 1;
    }

    public function totalParticipants(): int
    {
        return User::query()->where('role', 'student')->count();
    }

    /**
     * Peringkat per kelompok orientasi, untuk widget "kumpulan saya".
     *
     * @return Collection<int, array{group: string, total_xp: int, members: int, rank: int}>
     */
    public function groupStandings(int $limit = 5): Collection
    {
        return User::query()
            ->where('role', 'student')
            ->whereNotNull('group_name')
            ->selectRaw('group_name as group_name, sum(xp) as total_xp, count(*) as members')
            ->groupBy('group_name')
            ->orderByDesc('total_xp')
            ->limit($limit)
            ->get()
            ->values()
            ->map(fn ($row, int $index): array => [
                'group' => (string) $row->group_name,
                'total_xp' => (int) $row->total_xp,
                'members' => (int) $row->members,
                'rank' => $index + 1,
            ]);
    }

    /**
     * Statistik ringkas untuk header dashboard.
     *
     * @return array{completed: int, total: int, percentage: int, rank: int, participants: int}
     */
    public function summary(User $user, int $completed, int $total): array
    {
        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            'rank' => $this->rankOf($user),
            'participants' => $this->totalParticipants(),
        ];
    }

    /**
     * Daftar modul yang belum selesai, dipakai untuk kartu "sasaran berikutnya".
     */
    public function nextMilestone(User $user): ?string
    {
        $board = app(ProgressionService::class)->questBoard($user);

        $row = $board->first(fn (array $item): bool => $item['status'] === QuestStatus::InProgress)
            ?? $board->first(fn (array $item): bool => $item['status'] === QuestStatus::Available);

        return $row['quest']->title ?? null;
    }
}
