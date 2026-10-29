<?php

namespace App\Services;

use App\Enums\QuestStatus;
use App\Models\Achievement;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Penilaian kuis dengan tiga pengaman yang sebelumnya tidak ada:
 * batas percobaan, batas waktu sisi server, dan XP sekali pakai.
 */
class QuizService
{
    public function __construct(
        private readonly XpService $xp,
        private readonly ProgressionService $progression,
        private readonly AchievementService $achievements,
    ) {}

    public function maxAttempts(): int
    {
        return (int) config('ospek.quiz.max_attempts', 3);
    }

    public function timeLimit(): int
    {
        return (int) config('ospek.quiz.time_limit_seconds', 300);
    }

    public function passPercentage(): int
    {
        return (int) config('ospek.quiz.pass_percentage', 80);
    }

    /**
     * @return Collection<int, QuizQuestion>
     */
    public function questions(string $moduleCode): Collection
    {
        return QuizQuestion::query()
            ->where('module_code', $moduleCode)
            ->orderBy('question_number')
            ->get();
    }

    public function attemptCount(User $user, string $moduleCode): int
    {
        return $user->quizAttempts()->where('module_code', $moduleCode)->count();
    }

    public function remainingAttempts(User $user, string $moduleCode): int
    {
        return max(0, $this->maxAttempts() - $this->attemptCount($user, $moduleCode));
    }

    public function bestAttempt(User $user, string $moduleCode): ?QuizAttempt
    {
        return $user->quizAttempts()
            ->where('module_code', $moduleCode)
            ->orderByDesc('score')
            ->orderBy('time_taken_seconds')
            ->first();
    }

    public function latestAttempt(User $user, string $moduleCode): ?QuizAttempt
    {
        return $user->quizAttempts()
            ->where('module_code', $moduleCode)
            ->orderByDesc('id')
            ->first();
    }

    public function hasPassed(User $user, string $moduleCode): bool
    {
        return $user->quizAttempts()
            ->where('module_code', $moduleCode)
            ->where('is_passed', true)
            ->exists();
    }

    /**
     * Stempel kedaluwarsa untuk sesi kuis yang baru dimulai.
     */
    public function expiry(): Carbon
    {
        return now()->addSeconds($this->timeLimit() + (int) config('ospek.quiz.grace_seconds', 20));
    }

    /**
     * @param  array<int|string, string>  $answers
     * @return array{attempt: QuizAttempt, evaluations: array<int, array<string, mixed>>, newly_unlocked: Collection<int, Achievement>}
     */
    public function grade(User $user, string $moduleCode, array $answers, ?Carbon $startedAt = null): array
    {
        $questions = $this->questions($moduleCode);

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'answers' => 'Bank soal untuk modul ini belum tersedia.',
            ]);
        }

        if ($this->remainingAttempts($user, $moduleCode) <= 0) {
            throw ValidationException::withMessages([
                'answers' => 'Batas percobaan kuis ('.$this->maxAttempts().'x) sudah tercapai.',
            ]);
        }

        // Batas waktu divalidasi di server: klaim `time_taken` dari browser tidak
        // dipercaya, karena bisa dimanipulasi lewat devtools.
        $elapsed = $startedAt !== null
            ? max(0, (int) $startedAt->diffInSeconds(now()))
            : $this->timeLimit();

        if ($elapsed > $this->timeLimit() + (int) config('ospek.quiz.grace_seconds', 20)) {
            throw ValidationException::withMessages([
                'answers' => 'Waktu pengerjaan kuis sudah habis. Silakan mulai ulang.',
            ]);
        }

        $evaluations = [];
        $correct = 0;
        $earnedXp = 0;

        foreach ($questions as $question) {
            $answer = $answers[$question->question_number] ?? null;
            $isCorrect = $question->isCorrect(is_string($answer) ? $answer : null);

            if ($isCorrect) {
                $correct++;
                $earnedXp += $question->xp_reward;
            }

            $evaluations[$question->question_number] = [
                'question' => $question->question,
                'user_answer' => $isCorrect || $answer === null ? $answer : strtoupper((string) $answer),
                'correct_answer' => $question->correct_option,
                'is_correct' => $isCorrect,
                'explanation' => $question->explanation,
            ];
        }

        $total = $questions->count();
        $percentage = (int) round(($correct / $total) * 100);
        $isPassed = $percentage >= $this->passPercentage();

        $attempt = QuizAttempt::create([
            'user_id' => $user->getKey(),
            'module_code' => $moduleCode,
            'attempt_number' => $this->attemptCount($user, $moduleCode) + 1,
            'score' => $correct,
            'total_questions' => $total,
            'xp_earned' => $isPassed ? $earnedXp : 0,
            'time_taken_seconds' => $elapsed,
            'expires_at' => $this->expiry(),
            'answers' => $evaluations,
            'is_passed' => $isPassed,
        ]);

        if ($isPassed) {
            // Kunci idempotensi per modul: percobaan ulang tidak menambah XP.
            $this->xp->award(
                $user,
                $earnedXp,
                'quiz',
                $attempt->getKey(),
                "Kuis {$moduleCode} lulus ({$correct}/{$total})",
                "quiz:{$user->getKey()}:{$moduleCode}",
            );

            $quest = $user->quests()->where('quiz_module_code', $moduleCode)->first();

            if ($quest !== null && $this->progression->statusFor($user, $quest) !== QuestStatus::Completed) {
                $this->progression->complete($user, $quest, "Kuis otomatis lulus: {$correct}/{$total}");
                $attempt->setAttribute('xp_earned', $earnedXp);
            }
        }

        $newlyUnlocked = $this->achievements->sync($user);

        return [
            'attempt' => $attempt->refresh(),
            'evaluations' => $evaluations,
            'newly_unlocked' => $newlyUnlocked,
        ];
    }
}
