<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CertificateService;
use App\Services\LeaderboardService;
use App\Services\ProgressionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificates,
        private readonly ProgressionService $progression,
        private readonly LeaderboardService $leaderboard,
    ) {}

    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $status = $this->certificates->status($user);

        return view('pages.certificate', [
            'user' => $user,
            'status' => $status,
            'issuedAt' => $user->orientation_completed_at ?? now(),
            'completedQuests' => $status['completed'],
            'rank' => $this->leaderboard->rankOf($user),
            'participants' => $this->leaderboard->totalParticipants(),
        ]);
    }

    /**
     * Halaman verifikasi publik: hanya menampilkan status keabsahan, bukan
     * data pribadi. Dipakai pihak luar untuk memastikan sertifikat asli.
     */
    public function verify(Request $request, ?string $code = null): View
    {
        $code ??= $request->query('code');
        $user = is_string($code) ? $this->certificates->verify($code) : null;

        return view('pages.verify', [
            'code' => is_string($code) ? strtoupper($code) : null,
            'subject' => $user === null ? null : [
                'name' => $user->name,
                'nim' => $user->nim,
                'study_program' => config('ospek.program.study_program'),
                'completed_at' => $user->orientation_completed_at,
                'completed_quests' => $this->progression->completedCount($user),
                'total_quests' => $this->progression->questBoard($user)->count(),
            ],
            'isValid' => $user !== null,
        ]);
    }
}
