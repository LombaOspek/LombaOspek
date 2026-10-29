<?php

namespace App\Services;

use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya pintu penambahan XP.
 *
 * Sebelumnya `$user->addXp()` dipanggil langsung dari controller sehingga kuis
 * yang diulang terus menambah poin tanpa batas. Semua award sekarang harus
 * membawa `$idempotencyKey`: bila key sudah pernah dipakai, transaksi
 * diabaikan dan saldo tidak berubah.
 */
class XpService
{
    /**
     * @return int|null Saldo baru, atau null bila award dilewati (duplikat / nol).
     */
    public function award(
        User $user,
        int $amount,
        string $sourceType,
        ?int $sourceId,
        string $description,
        ?string $idempotencyKey = null,
    ): ?int {
        if ($amount === 0) {
            return null;
        }

        return DB::transaction(function () use ($user, $amount, $sourceType, $sourceId, $description, $idempotencyKey) {
            /** @var User $locked */
            $locked = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($idempotencyKey !== null
                && XpTransaction::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                Log::info('XP award ditolak karena idempotency key sudah dipakai.', [
                    'user_id' => $locked->getKey(),
                    'key' => $idempotencyKey,
                ]);

                return null;
            }

            $newBalance = max(0, $locked->xp + $amount);
            $locked->xp = $newBalance;
            $locked->level = $this->levelForXp($newBalance);
            $locked->save();

            XpTransaction::create([
                'user_id' => $locked->getKey(),
                'amount' => $amount,
                'balance_after' => $newBalance,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
            ]);

            $user->setRawAttributes($locked->getAttributes(), true);

            return $newBalance;
        });
    }

    public function levelForXp(int $xp): int
    {
        /** @var array<int, array{title: string, min_xp: int}> $levels */
        $levels = config('ospek.levels', []);

        $level = 1;

        foreach ($levels as $candidate => $band) {
            if ($xp >= $band['min_xp']) {
                $level = (int) $candidate;
            }
        }

        return $level;
    }

    /**
     * Rekonsiliasi: selisih antara kolom users.xp dan jumlah mutasi.
     */
    public function audit(User $user): array
    {
        $ledgerTotal = (int) $user->xpTransactions()->sum('amount');

        return [
            'column' => $user->xp,
            'ledger' => $ledgerTotal,
            'difference' => $user->xp - $ledgerTotal,
            'consistent' => $user->xp === $ledgerTotal,
        ];
    }
}
