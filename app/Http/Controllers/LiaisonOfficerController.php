<?php

namespace App\Http\Controllers;

use App\Enums\QuestStatus;
use App\Enums\SubmissionStatus;
use App\Enums\UserRole;
use App\Models\Quest;
use App\Models\QuestSubmission;
use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Portal Liaison Officer (kakak tingkat): meninjau bukti misi mahasiswa
 * gugusnya sendiri dan memantau statistik verifikasi.
 */
class LiaisonOfficerController extends Controller
{
    /**
     * @return array{group: ?\App\Models\Group, pending: int, approved: int, approved_today: int, rejected: int, today_xp: int, efficiency: float, members: int}
     */
    private function stats(User $officer): array
    {
        $groupMemberIds = $officer->group_id !== null
            ? User::query()->where('group_id', $officer->group_id)->pluck('id')
            : collect();

        $base = QuestSubmission::query()->whereIn('user_id', $groupMemberIds);

        $approved = (clone $base)->where('status', SubmissionStatus::Approved)->count();
        $rejected = (clone $base)->where('status', SubmissionStatus::Rejected)->count();
        $decided = $approved + $rejected;

        return [
            'group' => $officer->group,
            'pending' => (clone $base)->where('status', SubmissionStatus::Pending)->count(),
            'approved' => $approved,
            'approved_today' => (clone $base)
                ->where('status', SubmissionStatus::Approved)
                ->whereDate('reviewed_at', Carbon::today())
                ->count(),
            'rejected' => $rejected,
            'today_xp' => (int) (clone $base)
                ->where('status', SubmissionStatus::Approved)
                ->whereDate('reviewed_at', Carbon::today())
                ->with('quest:id,xp_reward')
                ->get()
                ->sum(fn (QuestSubmission $s): int => $s->quest?->xp_reward ?? 0),
            // Rasio kelulusan: berapa persen bukti yang layak tanpa perbaikan.
            'efficiency' => $decided > 0 ? round(($approved / $decided) * 100, 1) : 0.0,
            'members' => $groupMemberIds->count(),
        ];
    }

    public function index(Request $request): View
    {
        /** @var User $officer */
        $officer = $request->user();

        abort_unless($officer->role->canVerifySubmissions(), 403);

        $status = $request->query('status', 'pending');
        $status = in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : 'pending';

        $memberIds = $officer->group_id !== null
            ? User::query()->where('group_id', $officer->group_id)->pluck('id')
            : collect();

        $submissions = QuestSubmission::query()
            ->with(['user:id,name,nim,major,group_id', 'quest:id,title,xp_reward,category,verification_method'])
            ->whereIn('user_id', $memberIds)
            ->when($status !== 'pending', fn ($q) => $q->where('status', $status))
            ->when($status === 'pending', fn ($q) => $q->where('status', SubmissionStatus::Pending))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = '%'.trim((string) $request->query('q')).'%';

                $q->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('nim', 'like', $term));
            })
            ->when($request->filled('quest'), fn ($q) => $q->where('quest_id', $request->integer('quest')))
            ->oldest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('pages.lo.dashboard', [
            'officer' => $officer,
            'stats' => $this->stats($officer),
            'submissions' => $submissions,
            'status' => $status,
            'quests' => Quest::query()->whereIn('id', QuestSubmission::query()->whereIn('user_id', $memberIds)->select('quest_id'))->get(['id', 'title']),
        ]);
    }

    /**
     * Tampilkan bukti foto. Hanya LO gugus terkait (atau admin) yang boleh
     * membuka — file disimpan di disk privat, bukan public.
     */
    public function proof(Request $request, QuestSubmission $submission)
    {
        /** @var User $officer */
        $officer = $request->user();

        abort_unless($officer->canReviewSubmissionOf($submission->user), 403);

        abort_if($submission->proof_path === null, 404, 'Bukti tidak tersedia.');

        $disk = Storage::disk('local');

        abort_unless($disk->exists($submission->proof_path), 404, 'File bukti tidak ditemukan.');

        return $disk->response($submission->proof_path);
    }

    public function approve(Request $request, QuestSubmission $submission): RedirectResponse
    {
        /** @var User $officer */
        $officer = $request->user();

        $result = app(VerificationService::class)
            ->review($officer, $submission, true);

        return back()->with('toast', [
            'title' => 'Bukti disetujui',
            'message' => $result['message'],
            'icon' => 'check',
            'tone' => 'success',
        ]);
    }

    public function reject(Request $request, QuestSubmission $submission): RedirectResponse
    {
        /** @var User $officer */
        $officer = $request->user();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:300'],
        ], [
            'reason.required' => 'Tuliskan alasan agar mahasiswa tahu apa yang harus diperbaiki.',
            'reason.min' => 'Alasan minimal 10 karakter.',
        ]);

        app(VerificationService::class)
            ->review($officer, $submission, false, $validated['reason']);

        return back()->with('toast', [
            'title' => 'Bukti ditolak',
            'message' => 'Mahasiswa dapat mengunggah perbaikan.',
            'icon' => 'x',
            'tone' => 'warning',
        ]);
    }
}