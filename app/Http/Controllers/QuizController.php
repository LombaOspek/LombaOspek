<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Quest;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserQuest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user() ?? User::where('email', 'karan.mehra@maba.ac.id')->first() ?? User::first();

        $questions = QuizQuestion::where('module_code', 'MOD-04')
            ->orderBy('question_number')
            ->get();

        $latestAttempt = $user->quizAttempts()
            ->where('module_code', 'MOD-04')
            ->latest()
            ->first();

        return view('pages.quiz', compact('user', 'questions', 'latestAttempt'));
    }

    public function submit(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::where('email', 'karan.mehra@maba.ac.id')->first() ?? User::first();

        $submittedAnswers = $request->input('answers', []);
        $timeTaken = (int) $request->input('time_taken', 65);

        $questions = QuizQuestion::where('module_code', 'MOD-04')
            ->orderBy('question_number')
            ->get();

        $correctCount = 0;
        $totalQuestions = $questions->count();
        $xpEarned = 0;
        $evaluations = [];

        foreach ($questions as $q) {
            $userAns = strtoupper((string) ($submittedAnswers[$q->question_number] ?? ''));
            $isCorrect = ($userAns === strtoupper($q->correct_option));

            if ($isCorrect) {
                $correctCount++;
                $xpEarned += $q->xp_reward;
            }

            $evaluations[$q->question_number] = [
                'user_answer' => $userAns,
                'correct_answer' => $q->correct_option,
                'is_correct' => $isCorrect,
                'explanation' => $q->explanation,
            ];
        }

        $isPassed = ($correctCount >= 3);

        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'module_code' => 'MOD-04',
            'score' => $correctCount,
            'total_questions' => $totalQuestions,
            'xp_earned' => $xpEarned,
            'time_taken_seconds' => $timeTaken,
            'answers' => $evaluations,
            'is_passed' => $isPassed,
        ]);

        // Award XP to student
        if ($xpEarned > 0) {
            $user->addXp($xpEarned);
        }

        // If passed, mark MOD-04 as completed in UserQuest
        if ($isPassed) {
            $quest = Quest::where('code', 'MOD-04')->first();
            if ($quest) {
                UserQuest::updateOrCreate(
                    ['user_id' => $user->id, 'quest_id' => $quest->id],
                    ['status' => 'completed', 'completed_at' => now()]
                );

                // Unlock next quest (MOD-05)
                $nextQuest = Quest::where('step_number', $quest->step_number + 1)->first();
                if ($nextQuest) {
                    UserQuest::updateOrCreate(
                        ['user_id' => $user->id, 'quest_id' => $nextQuest->id],
                        ['status' => 'in_progress']
                    );
                }
            }
        }

        // If perfect score, unlock 'ach-03' (Juara Kuiz Fakulti)
        if ($correctCount === $totalQuestions) {
            $quizAchievement = Achievement::where('code', 'ach-03')->first();
            if ($quizAchievement) {
                UserAchievement::updateOrCreate(
                    ['user_id' => $user->id, 'achievement_id' => $quizAchievement->id],
                    [
                        'current_steps' => 1,
                        'is_unlocked' => true,
                        'unlocked_at' => now(),
                    ]
                );
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'score' => $correctCount,
                'total' => $totalQuestions,
                'percentage' => round(($correctCount / $totalQuestions) * 100),
                'xp_earned' => $xpEarned,
                'is_passed' => $isPassed,
                'evaluations' => $evaluations,
                'user_xp' => $user->xp,
                'user_level' => $user->level,
            ]);
        }

        return redirect()->route('quiz')->with('quiz_result', [
            'score' => $correctCount,
            'total' => $totalQuestions,
            'xp_earned' => $xpEarned,
            'is_passed' => $isPassed,
        ]);
    }
}
