<?php

namespace App\Services;

use App\Models\CoinTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya pintu penambahan koin.
 *
 * Kembaran XpService, tetapi untuk mata uang belanja. Dipisah karena aturannya
 * berbeda: XP tidak pernah berkurang, koin bisa dibelanjakan di Bursa Hadiah.
 *
 * Pola yang dipertahankan sama seperti XpService:
 *  - DB::transaction + lockForUpdate() agar dua permintaan serentak tidak
 *    menghasilkan saldo yang salah
 *  - idempotency_key unik agar klaim ulang / retry jaringan tidak menggandakan
 *    saldo
 *  - balance_after disimpan pada tiap mutasi sehingga riwayat bisa diaudit
 *    tanpa menjumlah ulang seluruh tabel
 */
class CoinService
{
    /**
     * Beri koin sebesar konversi dari sebuah perolehan XP.
     *
     * Dipanggil berdampingan dengan XpService::award() di titik yang sama,
     * memakai idempotency key yang sama pula — sehingga koin dan XP selalu
     * bertambah bersama atau tidak sama sekali.
     *
     * @param  string  $sourceType  quest|quiz|side_quest|achievement|daily_box|secret_mission
     * @return int|null Saldo baru, atau null bila tidak ada koin yang diberikan.
     */
    public function awardForXp(
        User $user,
        int $xpAmount,
        string $sourceType,
        ?int $sourceId,
        string $description,
        ?string $idempotencyKey = null,
    ): ?int {
        $coins = $this->coinsFor($xpAmount, $sourceType);

        if ($coins <= 0) {
            return null;
        }

        return $this->award($user, $coins, $sourceType, $sourceId, $description, $idempotencyKey);
    }

    /**
     * Beri koin bernilai tetap (bukan turunan XP).
     *
     * @return int|null Saldo baru, atau null bila hadiah nol / duplikat.
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

        return DB::transaction(function () use ($user, $amount, $sourceType, $sourceId, $description, $idempotencyKey): ?int {
            /** @var User $locked */
            $locked = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($idempotencyKey !== null
                && CoinTransaction::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                Log::info('Koin ditolak karena idempotency key sudah dipakai.', [
                    'user_id' => $locked->getKey(),
                    'key' => $idempotencyKey,
                ]);

                return null;
            }

            $newBalance = max(0, $locked->coins + $amount);
            $locked->coins = $newBalance;
            $locked->save();

            CoinTransaction::create([
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

    /**
     * Hitung koin dari sebuah perolehan XP.
     *
     * `side_quest` sengaja 100%: Side Quest opsional, jadi insentifnya harus
     * jauh lebih besar daripada modul wajib agar mahasiswa benar-benar
     * bersosialisasi — inilah tujuan yang disebut proposal.
     */
    public function coinsFor(int $xpAmount, string $sourceType): int
    {
        $config = config('ospek.coins');

        // Hadiah tetap lebih dulu: lencana dan kotak misteri tidak dihitung
        // sebagai persentase XP-nya.
        $flat = $config['flat'][$sourceType] ?? null;

        if ($flat !== null) {
            return (int) $flat;
        }

        $rate = $config['rates'][$sourceType] ?? null;

        if ($rate === null || $xpAmount < (int) $config['min_xp']) {
            return 0;
        }

        return (int) floor($xpAmount * (float) $rate);
    }

    /**
     * Rekonsiliasi: selisih antara kolom users.coins dan jumlah mutasi.
     *
     * @return array{column: int, ledger: int, difference: int, consistent: bool}
     */
    public function audit(User $user): array
    {
        $ledgerTotal = (int) $user->coinTransactions()->sum('amount');

        return [
            'column' => $user->coins,
            'ledger' => $ledgerTotal,
            'difference' => $user->coins - $ledgerTotal,
            'consistent' => $user->coins === $ledgerTotal,
        ];
    }
}
