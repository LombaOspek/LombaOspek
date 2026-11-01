<?php

namespace App\Services;

use App\Exceptions\RewardRedemptionException;
use App\Models\CoinTransaction;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Bursa Hadiah — satu-satunya pintu penukaran koin.
 *
 * ATURAN EKONOMI
 * --------------
 * XP tidak pernah berkurang: ia mengukur progres dan menentukan peringkat.
 * Koin yang dibelanjakan. Keduanya punya ledger sendiri supaya audit tidak
 * tercampur.
 *
 * ATURAN KESELAMATAN (inti kelas ini)
 * -----------------------------------
 * 1. Seluruh mutasi berjalan di dalam DB::transaction().
 * 2. Baris `rewards` dan `users` dikunci dengan lockForUpdate() SEBELUM
 *    dibaca, sehingga dua permintaan serentak tidak bisa sama-sama melihat
 *    stok 1 atau saldo 100.
 * 3. Urutan penguncian SELALU reward lalu user. Urutan yang konsisten
 *    mencegah deadlock: dua transaksi tidak akan pernah menunggu satu sama
 *    lain secara melingkar.
 * 4. Stok, kuota, dan saldo divalidasi SETELAH kunci didapat, bukan sebelum.
 *    Memeriksa sebelum mengunci adalah sumber utama race condition.
 * 5. Ledger memakai idempotency key, jadi retry jaringan tidak menggandakan
 *    mutasi.
 */
class RewardService
{
    /** Huruf tanpa karakter ambigu (0/O, 1/I/L) agar mudah dibacakan di meja panitia. */
    private const CLAIM_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const CLAIM_LENGTH = 8;

    private const CLAIM_PREFIX = 'OQ';

    private const MAX_CLAIM_ATTEMPTS = 5;

    /** Percobaan ulang transaksi saat database melaporkan deadlock. */
    private const TRANSACTION_ATTEMPTS = 3;

    /**
     * Tukarkan koin dengan hadiah.
     *
     * @throws RewardRedemptionException bila stok habis, kuota terlampaui,
     *                                    hadiah nonaktif, atau koin kurang.
     */
    public function redeemReward(User $user, Reward $reward): RewardRedemption
    {
        return DB::transaction(function () use ($user, $reward): RewardRedemption {
            /*
             * Kunci hadiah lebih dulu: inilah baris yang paling sering
             * diperebutkan (stok terbatas), jadi memegangnya lebih awal
             * membuat antrean terbentuk di satu titik.
             */
            $lockedReward = Reward::query()
                ->lockForUpdate()
                ->find($reward->getKey());

            if ($lockedReward === null) {
                throw RewardRedemptionException::inactive((string) $reward->name);
            }

            if (! $lockedReward->is_active) {
                throw RewardRedemptionException::inactive($lockedReward->name);
            }

            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->getKey());

            $this->assertQuotaAvailable($lockedUser, $lockedReward);
            $this->assertStockAvailable($lockedReward);
            $this->assertBalanceSufficient($lockedUser, $lockedReward);

            $this->reduceStock($lockedReward);
            $this->withdrawCoins($lockedUser, $lockedReward);

            $redemption = $this->recordRedemption($lockedUser, $lockedReward);

            $this->recordCoinMutation($lockedUser, $lockedReward, $redemption);

            // Model milik pemanggil ikut diperbarui agar kode setelah ini
            // membaca saldo/stok yang benar tanpa query ulang.
            $user->setRawAttributes($lockedUser->getAttributes(), true);
            $reward->setRawAttributes($lockedReward->getAttributes(), true);

            return $redemption;
        }, self::TRANSACTION_ATTEMPTS);
    }

    /**
     * Batalkan klaim dan kembalikan koin + stok.
     *
     * Hanya klaim berstatus pending yang bisa dibatalkan: klaim yang sudah
     * diserahkan ke mahasiswa tidak boleh dibatalkan diam-diam.
     *
     * @throws RewardRedemptionException
     */
    public function cancelRedemption(RewardRedemption $redemption, string $reason): RewardRedemption
    {
        return DB::transaction(function () use ($redemption, $reason): RewardRedemption {
            $lockedReward = Reward::query()
                ->lockForUpdate()
                ->find($redemption->reward_id);

            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($redemption->user_id);

            $lockedRedemption = RewardRedemption::query()
                ->lockForUpdate()
                ->findOrFail($redemption->getKey());

            if (! $lockedRedemption->isPending()) {
                throw RewardRedemptionException::cannotCancel($lockedRedemption->status);
            }

            $lockedRedemption->forceFill([
                'status' => RewardRedemption::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_reason' => Str::limit($reason, 295),
            ])->save();

            if ($lockedReward !== null && ! $lockedReward->hasUnlimitedStock()) {
                $lockedReward->increment('stock');
            }

            $refund = $lockedRedemption->coins_spent;

            $lockedUser->coins += $refund;
            $lockedUser->save();

            CoinTransaction::create([
                'user_id' => $lockedUser->getKey(),
                'amount' => $refund,
                'balance_after' => $lockedUser->coins,
                'source_type' => 'reward_refund',
                'source_id' => $lockedRedemption->getKey(),
                'description' => 'Pembatalan klaim '.$lockedRedemption->claim_code,
                'idempotency_key' => 'reward_refund:'.$lockedRedemption->getKey(),
            ]);

            $redemption->setRawAttributes($lockedRedemption->getAttributes(), true);

            return $lockedRedemption;
        }, self::TRANSACTION_ATTEMPTS);
    }

    /* ------------------------------------------------------------------
     * Validasi — semuanya dipanggil SETELAH baris dikunci.
     * ---------------------------------------------------------------- */

    private function assertQuotaAvailable(User $user, Reward $reward): void
    {
        if ($reward->hasUnlimitedQuota()) {
            return;
        }

        // Klaim yang dibatalkan tidak dihitung: mahasiswa berhak mencoba lagi.
        $claimed = RewardRedemption::query()
            ->where('user_id', $user->getKey())
            ->where('reward_id', $reward->getKey())
            ->where('status', '!=', RewardRedemption::STATUS_CANCELLED)
            ->count();

        if ($claimed >= $reward->quota_per_user) {
            throw RewardRedemptionException::quotaExceeded($reward->name, $reward->quota_per_user);
        }
    }

    private function assertStockAvailable(Reward $reward): void
    {
        if ($reward->isSoldOut()) {
            throw RewardRedemptionException::outOfStock($reward->name);
        }
    }

    private function assertBalanceSufficient(User $user, Reward $reward): void
    {
        if ($user->coins < $reward->cost_coins) {
            throw RewardRedemptionException::insufficientCoins($reward->cost_coins, $user->coins);
        }
    }

    /* ------------------------------------------------------------------
     * Mutasi
     * ---------------------------------------------------------------- */

    /**
     * Stok NULL berarti tak terbatas, jadi tidak disentuh sama sekali —
     * mengurangi NULL akan menghasilkan NULL dan menghapus maknanya.
     */
    private function reduceStock(Reward $reward): void
    {
        if ($reward->hasUnlimitedStock()) {
            return;
        }

        $reward->decrement('stock');
    }

    private function withdrawCoins(User $user, Reward $reward): void
    {
        $user->coins -= $reward->cost_coins;
        $user->save();
    }

    private function recordRedemption(User $user, Reward $reward): RewardRedemption
    {
        return RewardRedemption::create([
            'reward_id' => $reward->getKey(),
            'user_id' => $user->getKey(),
            'claim_code' => $this->generateUniqueClaimCode(),
            'coins_spent' => $reward->cost_coins,
            'status' => RewardRedemption::STATUS_PENDING,
            'meta' => [
                'reward_name' => $reward->name,
                'cost_at_redeem' => $reward->cost_coins,
            ],
        ])->load('reward');
    }

    private function recordCoinMutation(User $user, Reward $reward, RewardRedemption $redemption): void
    {
        CoinTransaction::create([
            'user_id' => $user->getKey(),
            'amount' => -$reward->cost_coins,
            'balance_after' => $user->coins,
            'source_type' => 'reward_redemption',
            'source_id' => $redemption->getKey(),
            'description' => 'Tukar hadiah: '.$reward->name,
            'idempotency_key' => 'reward_redeem:'.$redemption->claim_code,
        ]);
    }

    /**
     * Kode klaim unik. Diperiksa di dalam transaksi karena unique index adalah
     * kebenaran akhir — pemeriksaan di sini hanya menghindari percobaan yang
     * jelas gagal.
     */
    private function generateUniqueClaimCode(): string
    {
        for ($attempt = 0; $attempt < self::MAX_CLAIM_ATTEMPTS; $attempt++) {
            $code = $this->randomClaimCode();

            $exists = RewardRedemption::query()
                ->where('claim_code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        // Praktis tidak tercapai (32^8 ≈ 1,1 triliun kombinasi), tetapi kalau
        // terjadi, gagal keras lebih baik daripada menulis kode duplikat.
        throw RewardRedemptionException::generic(
            'claim_code_exhausted',
            'Gagal membuat kode klaim unik. Coba lagi.',
        );
    }

    private function randomClaimCode(): string
    {
        $body = '';

        for ($i = 0; $i < self::CLAIM_LENGTH; $i++) {
            $body .= self::CLAIM_ALPHABET[random_int(0, strlen(self::CLAIM_ALPHABET) - 1)];
        }

        // Grup 4-4 supaya mudah dibacakan lewat telepon atau ditulis tangan.
        return self::CLAIM_PREFIX.'-'.substr($body, 0, 4).'-'.substr($body, 4);
    }
}