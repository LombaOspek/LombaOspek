<?php

namespace App\Http\Controllers;

use App\Exceptions\RewardRedemptionException;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Bursa Hadiah untuk mahasiswa.
 *
 * Controller ini sengaja tipis: seluruh aturan ekonomi ada di RewardService.
 * Yang dikerjakan di sini hanya membaca data untuk tampilan dan menerjemahkan
 * RewardRedemptionException menjadi respons yang bisa dipahami pengguna.
 */
class RewardController extends Controller
{
    public function __construct(private readonly RewardService $rewards) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $rewards = Reward::query()
            ->available()
            ->orderBy('sort_order')
            ->orderBy('cost_coins')
            ->get();

        // Kuota & klaim dihitung sekali untuk semua hadiah, bukan per baris
        // di dalam view.
        $claimsByReward = RewardRedemption::query()
            ->where('user_id', $user->getKey())
            ->where('status', '!=', RewardRedemption::STATUS_CANCELLED)
            ->selectRaw('reward_id, count(*) as total')
            ->groupBy('reward_id')
            ->pluck('total', 'reward_id');

        $rows = $rewards->map(fn (Reward $reward): array => [
            'reward' => $reward,
            'claimed' => (int) ($claimsByReward[$reward->getKey()] ?? 0),
            'affordable' => $user->coins >= $reward->cost_coins,
            'sold_out' => $reward->isSoldOut(),
        ]);

        return view('pages.rewards', [
            'user' => $user,
            'rows' => $rows,
            'redemptions' => RewardRedemption::query()
                ->with('reward')
                ->where('user_id', $user->getKey())
                ->latest('id')
                ->limit(20)
                ->get(),
            'coinHistory' => $user->coinTransactions()
                ->latest('id')
                ->limit(15)
                ->get(),
        ]);
    }

    /**
     * Tukar hadiah.
     *
     * Mendukung dua bentuk permintaan: form biasa (redirect + flash toast) dan
     * fetch JSON dari modal. Klien tidak pernah mengirim harga — hanya id
     * hadiah, dan server yang menentukan biayanya.
     */
    /**
     * Halaman yang terbuka saat panitia memindai QR klaim.
     *
     * Sengaja PUBLIK (di luar `auth`): panitia di meja penyerahan sering tidak
     * login, dan memaksa mereka masuk dulu membuat antrean berhenti. Yang
     * ditampilkan hanya yang memang tertempel di kartu fisik: nama pemilik,
     * hadiah, dan status. Kode klaim lengkap tidak dicetak ulang di sini supaya
     * URL yang bocor tidak bisa dipakai menebak kode lain.
     */
    public function verifyClaim(string $code): View
    {
        $redemption = RewardRedemption::query()
            ->with(['reward', 'user'])
            ->where('claim_code', strtoupper(trim($code)))
            ->first();

        return view('pages.claim-verify', [
            'code' => strtoupper(trim($code)),
            'redemption' => $redemption,
            'reward' => $redemption?->reward,
            'owner' => $redemption?->user,
            'isValid' => $redemption !== null,
        ]);
    }

    public function redeem(Request $request, Reward $reward): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $wantsJson = $request->expectsJson();

        try {
            $redemption = $this->rewards->redeemReward($user, $reward);
        } catch (RewardRedemptionException $exception) {
            if ($wantsJson) {
                return response()->json([
                    'ok' => false,
                    'reason' => $exception->reason,
                    'message' => $exception->getMessage(),
                    'coins' => $user->fresh()->coins,
                ], $exception->httpStatus());
            }

            return back()->with('toast', [
                'title' => 'Penukaran gagal',
                'message' => $exception->getMessage(),
                'tone' => 'warning',
            ]);
        }

        // Berhasil: muat ulang moot agar saldo di seluruh halaman ikut segar.
        $user->refresh();

        if ($wantsJson) {
            return response()->json([
                'ok' => true,
                'claim_code' => $redemption->claim_code,
                'reward' => [
                    'name' => $redemption->reward->name,
                    'cost_coins' => $redemption->coins_spent,
                ],
                'coins' => $user->coins,
            ]);
        }

        return redirect()
            ->route('rewards.index')
            ->with('toast', [
                'title' => 'Berhasil ditukar: '.$redemption->reward->name,
                'message' => 'Kode klaim Anda: '.$redemption->claim_code.'. Tunjukkan ke panitia.',
                'tone' => 'success',
            ]);
    }
}