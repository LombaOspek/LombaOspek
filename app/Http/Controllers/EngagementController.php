<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EngagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Endpoint untuk lima fitur engagement.
 *
 * Semua keputusan hadiah diambil di sini lewat EngagementService: klien hanya
 * memberi tahu "saya mengklik", tidak pernah "beri saya N XP".
 */
class EngagementController extends Controller
{
    public function __construct(private readonly EngagementService $engagement) {}

    /**
     * Klaim Kotak Misteri harian.
     */
    public function claimDailyBox(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $result = $this->engagement->claimDailyBox($user);

        if ($result['already']) {
            return response()->json([
                'ok' => false,
                'reason' => 'already_claimed',
                'message' => 'Kotak hari ini sudah Anda buka. Kembali besok untuk kotak baru.',
                'box' => $this->engagement->dailyBox($user),
            ], 409);
        }

        return response()->json([
            'ok' => true,
            'xp' => $result['xp'],
            'balance_after' => $result['balance_after'],
            'box' => $this->engagement->dailyBox($user),
        ]);
    }

    /**
     * Klaim Misi Rahasia. `clicks` dikirim klien hanya sebagai bukti bahwa
     * urutan kliknya benar-benar terjadi; hadiahnya tetap dari config dan
     * dibatasi satu kali seumur akun.
     */
    public function claimSecretMission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'clicks' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $result = $this->engagement->claimSecretMission($user, (int) $validated['clicks']);

        if ($result['already']) {
            return response()->json([
                'ok' => false,
                'reason' => 'already_claimed',
                'message' => 'Misi rahasia ini sudah pernah Anda temukan.',
            ], 409);
        }

        if ($result['xp'] === 0) {
            return response()->json([
                'ok' => false,
                'reason' => 'not_enough_clicks',
                'message' => 'WIRA belum mau bicara. Coba ketuk lebih cepat.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'xp' => $result['xp'],
            'balance_after' => $result['balance_after'],
        ]);
    }

    /**
     * Data ringkas untuk widget yang perlu diperbarui tanpa memuat ulang
     * halaman (dipakai Gugus Wars dan FOMO feed).
     */
    public function summary(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'gugus_wars' => $this->engagement->gugusWars($user),
            'live_feed' => $this->engagement->liveFeed($user),
            'daily_box' => $this->engagement->dailyBox($user),
            'title' => $this->engagement->titleFor($user),
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
