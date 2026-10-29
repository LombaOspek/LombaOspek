<?php

namespace App\Http\Controllers;

use App\Enums\QuestActionType;
use App\Enums\QuestStatus;
use App\Http\Requests\CompleteQuestRequest;
use App\Models\Quest;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\ProgressionService;
use App\Services\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestController extends Controller
{
    public function __construct(
        private readonly ProgressionService $progression,
        private readonly XpService $xp,
        private readonly AchievementService $achievements,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $board = $this->progression->questBoard($user);

        $activeRow = $board->first(fn (array $row): bool => $row['status'] === QuestStatus::InProgress)
            ?? $board->first(fn (array $row): bool => $row['status'] === QuestStatus::Available);

        return view('pages.missions', [
            'user' => $user,
            'board' => $board,
            'activeRow' => $activeRow,
            'counts' => [
                'completed' => $board->filter(fn (array $row): bool => $row['status'] === QuestStatus::Completed)->count(),
                'in_progress' => $board->filter(fn (array $row): bool => $row['status'] === QuestStatus::InProgress)->count(),
                'available' => $board->filter(fn (array $row): bool => $row['status'] === QuestStatus::Available)->count(),
                'locked' => $board->filter(fn (array $row): bool => $row['status'] === QuestStatus::Locked)->count(),
                'total' => $board->count(),
            ],
            'orientationDay' => $this->progression->orientationDay(),
        ]);
    }

    public function show(Request $request, Quest $quest): View
    {
        /** @var User $user */
        $user = $request->user();

        $status = $this->progression->statusFor($user, $quest);
        $row = collect($this->progression->questBoard($user))
            ->first(fn (array $item): bool => $item['quest']->is($quest));

        return view('pages.quest-detail', [
            'user' => $user,
            'quest' => $quest,
            'status' => $status,
            'lockedReason' => $row['locked_reason'] ?? null,
            'userQuest' => $row['user_quest'] ?? null,
        ]);
    }

    public function start(Request $request, Quest $quest): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $this->progression->canStart($user, $quest)) {
            return back()->with('toast', [
                'title' => 'Modul masih terkunci',
                'message' => 'Selesaikan modul sebelumnya lebih dulu.',
                'icon' => 'lock',
                'tone' => 'warning',
            ]);
        }

        // Modul berbasis kuis tidak punya tombol "selesai"; langsung arahkan.
        if ($quest->action_type === QuestActionType::Quiz) {
            return redirect()->route('quiz', ['module' => $quest->quiz_module_code]);
        }

        $this->progression->markInProgress($user, $quest);

        return redirect()->route('quests.show', $quest)->with('toast', [
            'title' => 'Modul dimulai',
            'message' => 'Ikuti instruksi modul lalu tandai selesai.',
            'icon' => 'play',
            'tone' => 'info',
        ]);
    }

    public function complete(CompleteQuestRequest $request, Quest $quest): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $status = $this->progression->statusFor($user, $quest);

        if (! $status->isOpen()) {
            $message = $status === QuestStatus::Completed
                ? 'Modul ini sudah selesai sebelumnya.'
                : 'Modul belum terbuka untuk Anda.';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('toast', [
                'title' => 'Tidak bisa diselesaikan',
                'message' => $message,
                'icon' => 'lock',
                'tone' => 'warning',
            ]);
        }

        if ($quest->action_type === QuestActionType::Quiz) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Modul kuis diselesaikan lewat halaman kuis, bukan tombol absensi.',
                ], 422);
            }

            return redirect()->route('quiz', ['module' => $quest->quiz_module_code]);
        }

        if ($quest->action_type === QuestActionType::Reflection && blank($request->validated('reflection'))) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Modul refleksi membutuhkan catatan minimal 80 karakter.',
                ], 422);
            }

            return back()
                ->withErrors(['reflection' => 'Refleksi minimal 80 karakter.'])
                ->withInput();
        }

        $this->progression->complete($user, $quest, $request->validated('reflection'));

        $awarded = $this->progression->checkpointXp($quest);

        if ($awarded > 0) {
            $this->xp->award(
                $user,
                $awarded,
                'quest',
                $quest->getKey(),
                "Modul selesai: {$quest->title}",
                "quest:{$user->getKey()}:{$quest->getKey()}",
            );
        }

        $newlyUnlocked = $this->achievements->sync($user);

        $payload = [
            'success' => true,
            'message' => "Modul \"{$quest->title}\" selesai.".$this->xpSuffix($awarded),
            'xp_awarded' => $awarded,
            'user_xp' => $user->refresh()->xp,
            'user_level' => $user->level,
            'level_title' => $user->level_title,
            'newly_unlocked' => $newlyUnlocked->map(fn ($achievement): array => [
                'title' => $achievement->title,
                'icon' => $achievement->icon,
                'xp' => $achievement->xp_reward,
            ])->values(),
            'redirect' => route('quests.index'),
        ];

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return redirect()->route('quests.index')->with('toast', [
            'title' => 'Modul selesai',
            'message' => $payload['message'],
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    private function xpSuffix(int $awarded): string
    {
        return $awarded > 0 ? " +{$awarded} XP" : '';
    }
}
