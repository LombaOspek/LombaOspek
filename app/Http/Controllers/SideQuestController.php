<?php

namespace App\Http\Controllers;

use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use App\Services\ProgressionService;
use App\Services\SideQuestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Side Quest — misi sosial opsional.
 *
 * Sengaja memakai ulang jalur penyelesaian modul yang sudah ada supaya XP,
 * idempotensi, dan verifikasi tidak punya dua implementasi berbeda.
 */
class SideQuestController extends Controller
{
    public function __construct(
        private readonly SideQuestService $sideQuests,
        private readonly ProgressionService $progression,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $board = $this->sideQuests->board($user);

        return view('pages.side-quests', [
            'user' => $user,
            'rows' => $board,
            'summary' => $this->sideQuests->summary($user),
            'completedModules' => $this->sideQuests->completedModuleCount($user),
        ]);
    }

    /**
     * Mulai Side Quest. Memakai service progresi yang sama, jadi tidak ada
     * jalur kedua yang bisa melewati aturan.
     */
    public function start(Request $request, Quest $quest): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($quest->is_side_quest, 404);

        $status = $this->statusFor($user, $quest);

        if (! $status->isOpen()) {
            return back()->with('toast', [
                'title' => 'Belum bisa dimulai',
                'message' => 'Selesaikan lebih banyak modul utama untuk membuka misi ini.',
                'tone' => 'warning',
            ]);
        }

        $this->progression->markInProgress($user, $quest);

        return redirect()
            ->route('quests.show', $quest)
            ->with('toast', [
                'title' => 'Side Quest dimulai',
                'message' => $quest->title,
                'tone' => 'neutral',
            ]);
    }

    private function statusFor(User $user, Quest $quest): QuestStatus
    {
        return $this->sideQuests->board($user)
            ->first(fn (array $row): bool => $row['quest']->is($quest))['status'] ?? QuestStatus::Locked;
    }
}