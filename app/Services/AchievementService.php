<?php

namespace App\Services;

use App\Enums\AchievementMetric;
use App\Enums\QuestStatus;
use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Collection;

/**
 * Evaluasi pencapaian secara otomatis dari metrik nyata.
 *
 * Sebelumnya progress hanya diisi oleh seeder dan badge di-hardcode di
 * controller, sehingga mahasiswa yang benar-benar menyelesaikan modul tidak
 * pernah mendapat lencana. Sekarang setiap metrik dihitung ulang dari data.
 */
class AchievementService
{
    public function __construct(
        private readonly XpService $xp,
        private readonly ProgressionService $progression,
    ) {}

    /**
     * Hitung ulang semua pencapaian user dan kembalikan yang baru terbuka.
     *
     * @return Collection<int, Achievement>
     */
    public function sync(User $user): Collection
    {
        $achievements = Achievement::query()->get();
        $existing = $user->userAchievements()->get()->keyBy('achievement_id');
        $metrics = $this->metrics($user);
        $newlyUnlocked = collect();

        foreach ($achievements as $achievement) {
            $value = $metrics[$achievement->metric->value] ?? 0;
            $steps = min($achievement->total_steps, $value);
            $wasUnlocked = (bool) $existing->get($achievement->getKey())?->is_unlocked;
            $isUnlocked = $achievement->total_steps > 0 && $steps >= $achievement->total_steps;

            $record = UserAchievement::updateOrCreate(
                ['user_id' => $user->getKey(), 'achievement_id' => $achievement->getKey()],
                [
                    'current_steps' => $steps,
                    'is_unlocked' => $isUnlocked,
                    'unlocked_at' => $isUnlocked
                        ? ($existing->get($achievement->getKey())?->unlocked_at ?? now())
                        : null,
                ],
            );

            if ($isUnlocked && ! $wasUnlocked) {
                $newlyUnlocked->push($achievement);

                $this->xp->award(
                    $user,
                    $achievement->xp_reward,
                    'achievement',
                    $achievement->getKey(),
                    'Lencana terbuka: '.$achievement->title,
                    "achievement:{$user->getKey()}:{$achievement->getKey()}",
                );
            }

            unset($record);
        }

        return $newlyUnlocked;
    }

    /**
     * Daftar pencapaian lengkap dengan progres user, siap dipakai view.
     *
     * @return Collection<int, array{achievement: Achievement, current_steps: int, total_steps: int, percentage: int, is_unlocked: bool, unlocked_at: mixed}>
     */
    public function board(User $user): Collection
    {
        $this->sync($user);

        $records = $user->userAchievements()->get()->keyBy('achievement_id');

        return Achievement::query()->orderBy('id')->get()->map(function (Achievement $achievement) use ($records): array {
            $record = $records->get($achievement->getKey());
            $current = (int) ($record?->current_steps ?? 0);
            $total = max(1, $achievement->total_steps);

            return [
                'achievement' => $achievement,
                'current_steps' => $current,
                'total_steps' => $achievement->total_steps,
                'percentage' => (int) min(100, round(($current / $total) * 100)),
                'is_unlocked' => (bool) ($record?->is_unlocked ?? false),
                'unlocked_at' => $record?->unlocked_at,
            ];
        });
    }

    /**
     * @return array<string, int>
     */
    public function metrics(User $user): array
    {
        $perfect = $user->quizAttempts()->where('is_passed', true)
            ->whereColumn('score', 'total_questions')
            ->count();

        return [
            AchievementMetric::QuestsCompleted->value => $user->userQuests()
                ->where('status', QuestStatus::Completed)->count(),
            AchievementMetric::QuizzesPassed->value => $user->quizAttempts()
                ->where('is_passed', true)->count(),
            AchievementMetric::PerfectQuizzes->value => $perfect,
            AchievementMetric::StreakDays->value => $user->streak_days,
            AchievementMetric::TotalXp->value => $user->xp,
            AchievementMetric::HelpTicketsAnswered->value => $user->helpTickets()
                ->whereNotNull('answered_at')->count(),
        ];
    }
}
