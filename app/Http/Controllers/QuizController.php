<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Models\User;
use App\Services\ProgressionService;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizService $quiz,
        private readonly ProgressionService $progression,
    ) {}

    public function index(Request $request, ?string $module = null): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $moduleCode = $module ?: $this->defaultModuleCode($user);

        if ($moduleCode === null) {
            return redirect()->route('quests.index')->with('toast', [
                'title' => 'Belum ada kuis',
                'message' => 'Belum ada modul kuis yang terbuka untuk Anda.',
                'icon' => 'lock',
                'tone' => 'warning',
            ]);
        }

        $quest = Quest::query()->where('quiz_module_code', $moduleCode)->first();

        if ($quest === null) {
            abort(404, 'Modul kuis tidak ditemukan.');
        }

        $status = $this->progression->statusFor($user, $quest);

        if (! $status->isOpen()) {
            return redirect()->route('quests.index')->with('toast', [
                'title' => 'Kuis masih terkunci',
                'message' => 'Selesaikan modul sebelumnya terlebih dahulu.',
                'icon' => 'lock',
                'tone' => 'warning',
            ]);
        }

        $questions = $this->quiz->questions($moduleCode);
        $best = $this->quiz->bestAttempt($user, $moduleCode);

        return view('pages.quiz', [
            'user' => $user,
            'quest' => $quest,
            'questions' => $questions,
            'moduleCode' => $moduleCode,
            'bestAttempt' => $best,
            'attemptCount' => $this->quiz->attemptCount($user, $moduleCode),
            'maxAttempts' => $this->quiz->maxAttempts(),
            'remainingAttempts' => $this->quiz->remainingAttempts($user, $moduleCode),
            'timeLimit' => $this->quiz->timeLimit(),
            'passPercentage' => $this->quiz->passPercentage(),
            'hasPassed' => $this->quiz->hasPassed($user, $moduleCode),
        ]);
    }

    /**
     * Membuka sesi kuis: server menyimpan stempel waktu mulai agar durasi
     * pengerjaan tidak bisa dikirim sembarangan oleh klien.
     */
    public function start(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'module_code' => ['required', 'string', 'max:20'],
        ]);

        $moduleCode = $data['module_code'];

        if ($this->quiz->remainingAttempts($user, $moduleCode) <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Batas percobaan kuis sudah tercapai.',
            ], 422);
        }

        $startedAt = now();

        $request->session()->put("quiz.started_at.{$moduleCode}", $startedAt->toIso8601String());
        $request->session()->put("quiz.expires_at.{$moduleCode}", $this->quiz->expiry()->toIso8601String());

        return response()->json([
            'success' => true,
            'module_code' => $moduleCode,
            'started_at' => $startedAt->toIso8601String(),
            'expires_at' => $this->quiz->expiry()->toIso8601String(),
            'time_limit' => $this->quiz->timeLimit(),
            'attempt_number' => $this->quiz->attemptCount($user, $moduleCode) + 1,
            'remaining_attempts' => $this->quiz->remainingAttempts($user, $moduleCode),
        ]);
    }

    public function submit(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'module_code' => ['required', 'string', 'max:20'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['nullable', 'string', 'in:A,B,C,D,a,b,c,d'],
            'started_at' => ['nullable', 'date'],
        ]);

        $moduleCode = $data['module_code'];

        // Waktu mulai diambil dari sesi (otoritatif), bukan dari body request.
        $sessionStart = $request->session()->pull("quiz.started_at.{$moduleCode}");
        $startedAt = $sessionStart !== null ? Carbon::parse($sessionStart) : null;

        if ($startedAt === null && ! empty($data['started_at'])) {
            $startedAt = Carbon::parse($data['started_at']);
        }

        try {
            $result = $this->quiz->grade($user, $moduleCode, $data['answers'], $startedAt);
        } catch (ValidationException $exception) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => collect($exception->errors())->flatten()->first(),
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        $attempt = $result['attempt'];
        $newlyUnlocked = $result['newly_unlocked'];

        $payload = [
            'success' => true,
            'score' => $attempt->score,
            'total' => $attempt->total_questions,
            'percentage' => $attempt->percentage,
            'xp_earned' => $attempt->xp_earned,
            'is_passed' => $attempt->is_passed,
            'attempt_number' => $attempt->attempt_number,
            'remaining_attempts' => $this->quiz->remainingAttempts($user, $moduleCode),
            'evaluations' => $result['evaluations'],
            'user_xp' => $user->refresh()->xp,
            'user_level' => $user->level,
            'level_title' => $user->level_title,
            'newly_unlocked' => $newlyUnlocked->map(fn ($achievement): array => [
                'title' => $achievement->title,
                'icon' => $achievement->icon,
                'xp' => $achievement->xp_reward,
            ])->values(),
        ];

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return redirect()->route('quiz', ['module' => $moduleCode])->with('quiz_result', $payload);
    }

    /**
     * Pilih modul kuis pertama yang sedang terbuka bagi mahasiswa.
     */
    private function defaultModuleCode(User $user): ?string
    {
        $board = $this->progression->questBoard($user);

        $open = $board->first(fn (array $row): bool => $row['status']->isOpen()
            && $row['quest']->quiz_module_code !== null);

        return $open['quest']->quiz_module_code ?? null;
    }
}
