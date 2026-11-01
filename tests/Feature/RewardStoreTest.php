<?php

namespace Tests\Feature;

use App\Exceptions\RewardRedemptionException;
use App\Models\CoinTransaction;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RewardStoreTest extends TestCase
{
    use RefreshDatabase;

    private function student(int $coins = 500, string $nim = '202653088'): User
    {
        return User::create([
            'nim' => $nim,
            'name' => 'Uji Bursa',
            'email' => $nim.'@example.test',
            'password' => Hash::make('password'),
            'role' => 'student',
            'xp' => 1000,
            'coins' => $coins,
            'level' => 2,
        ]);
    }

    private function reward(array $overrides = []): Reward
    {
        return Reward::create(array_merge([
            'code' => 'MUG-01',
            'name' => 'Mug OspekQuest',
            'category' => 'merchandise',
            'cost_coins' => 200,
            'stock' => 5,
            'quota_per_user' => null,
            'is_active' => true,
        ], $overrides));
    }

    public function test_successful_redeem_deducts_coins_and_stock(): void
    {
        $user = $this->student(500);
        $reward = $this->reward();

        $redemption = app(RewardService::class)->redeemReward($user, $reward);

        $this->assertSame(300, $user->fresh()->coins);
        $this->assertSame(4, $reward->fresh()->stock);

        $this->assertSame(RewardRedemption::STATUS_PENDING, $redemption->status);
        $this->assertSame(200, $redemption->coins_spent);
        $this->assertMatchesRegularExpression('/^OQ-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $redemption->claim_code);
    }

    public function test_ledger_records_negative_mutation_with_balance(): void
    {
        $user = $this->student(500);
        $reward = $this->reward();

        app(RewardService::class)->redeemReward($user, $reward);

        $transaction = CoinTransaction::where('user_id', $user->id)->sole();

        $this->assertSame(-200, $transaction->amount);
        $this->assertSame(300, $transaction->balance_after);
        $this->assertSame('reward_redemption', $transaction->source_type);
    }

    public function test_xp_is_never_touched(): void
    {
        $user = $this->student(500);
        $before = $user->xp;

        app(RewardService::class)->redeemReward($user, $this->reward());

        $this->assertSame($before, $user->fresh()->xp, 'XP tidak boleh berkurang saat belanja.');
    }

    public function test_insufficient_coins_is_rejected_without_side_effects(): void
    {
        $user = $this->student(100);
        $reward = $this->reward(['cost_coins' => 200]);

        try {
            app(RewardService::class)->redeemReward($user, $reward);
            $this->fail('Seharusnya melempar insufficientCoins.');
        } catch (RewardRedemptionException $e) {
            $this->assertSame(RewardRedemptionException::INSUFFICIENT_COINS, $e->reason);
            $this->assertSame(402, $e->httpStatus());
        }

        $this->assertSame(100, $user->fresh()->coins);
        $this->assertSame(5, $reward->fresh()->stock);
        $this->assertSame(0, RewardRedemption::count());
        $this->assertSame(0, CoinTransaction::count());
    }

    public function test_sold_out_reward_is_rejected(): void
    {
        $user = $this->student(500);
        $reward = $this->reward(['stock' => 0]);

        $this->expectException(RewardRedemptionException::class);

        app(RewardService::class)->redeemReward($user, $reward);
    }

    public function test_per_user_quota_is_enforced(): void
    {
        $user = $this->student(2000);
        $reward = $this->reward(['quota_per_user' => 2, 'stock' => 10]);
        $service = app(RewardService::class);

        $service->redeemReward($user, $reward);
        $service->redeemReward($user, $reward);

        try {
            $service->redeemReward($user, $reward);
            $this->fail('Klaim ketiga seharusnya ditolak.');
        } catch (RewardRedemptionException $e) {
            $this->assertSame(RewardRedemptionException::QUOTA_EXCEEDED, $e->reason);
        }

        $this->assertSame(2, RewardRedemption::count());
    }

    public function test_inactive_reward_is_rejected(): void
    {
        $user = $this->student(500);
        $reward = $this->reward(['is_active' => false]);

        $this->expectException(RewardRedemptionException::class);

        app(RewardService::class)->redeemReward($user, $reward);
    }

    public function test_unlimited_stock_is_never_decremented(): void
    {
        $user = $this->student(500);
        $reward = $this->reward(['stock' => null]);

        app(RewardService::class)->redeemReward($user, $reward);

        $this->assertNull($reward->fresh()->stock, 'Stok tak terbatas harus tetap NULL, bukan 0.');
    }

    public function test_claim_codes_are_unique(): void
    {
        $reward = $this->reward(['stock' => null, 'quota_per_user' => null]);
        $service = app(RewardService::class);

        $codes = [];

        for ($i = 0; $i < 12; $i++) {
            $user = $this->student(1000, '2026530'.str_pad((string) (80 + $i), 2, '0', STR_PAD_LEFT));
            $codes[] = $service->redeemReward($user, $reward)->claim_code;
        }

        $this->assertCount(12, array_unique($codes), 'Kode klaim tidak boleh ada yang kembar.');
    }

    /**
     * Inti permintaan: stok TIDAK boleh minus saat banyak permintaan serentak.
     *
     * Tes ini memakai koneksi yang sama (SQLite in-memory), jadi lockForUpdate()
     * tidak benar-benar memblokir. Yang diuji di sini adalah INVARIAN hasil:
     * berapa pun percobaan yang masuk, jumlah klaim tidak pernah melebihi stok
     * dan saldo tidak pernah negatif. Penguncian sesungguhnya diuji terpisah
     * lewat pengujian manual di MySQL (lihat catatan di akhir berkas).
     */
    public function test_stock_never_goes_negative_under_repeated_attempts(): void
    {
        $reward = $this->reward(['stock' => 3, 'cost_coins' => 100]);
        $service = app(RewardService::class);

        $succeeded = 0;
        $rejected = 0;

        for ($i = 0; $i < 10; $i++) {
            $user = $this->student(500, '2026539'.str_pad((string) $i, 2, '0', STR_PAD_LEFT));

            try {
                $service->redeemReward($user, $reward);
                $succeeded++;
            } catch (RewardRedemptionException) {
                $rejected++;
            }
        }

        $this->assertSame(3, $succeeded, 'Hanya 3 klaim boleh berhasil untuk stok 3.');
        $this->assertSame(7, $rejected);
        $this->assertSame(0, $reward->fresh()->stock);
        $this->assertGreaterThanOrEqual(0, $reward->fresh()->stock);
    }

    public function test_balance_never_goes_negative(): void
    {
        $reward = $this->reward(['stock' => null, 'cost_coins' => 300]);
        $user = $this->student(500);
        $service = app(RewardService::class);

        $service->redeemReward($user, $reward);

        try {
            $service->redeemReward($user, $reward);
            $this->fail('Klaim kedua seharusnya ditolak karena saldo kurang.');
        } catch (RewardRedemptionException $e) {
            $this->assertSame(RewardRedemptionException::INSUFFICIENT_COINS, $e->reason);
        }

        $this->assertSame(200, $user->fresh()->coins);
        $this->assertGreaterThanOrEqual(0, $user->fresh()->coins);
    }

    public function test_cancel_refunds_coins_and_restores_stock(): void
    {
        $user = $this->student(500);
        $reward = $this->reward();
        $service = app(RewardService::class);

        $redemption = $service->redeemReward($user, $reward);

        $service->cancelRedemption($redemption, 'Salah pilih ukuran');

        $this->assertSame(500, $user->fresh()->coins);
        $this->assertSame(5, $reward->fresh()->stock);
        $this->assertSame(RewardRedemption::STATUS_CANCELLED, $redemption->fresh()->status);
        $this->assertSame(2, CoinTransaction::where('user_id', $user->id)->count());
    }

    public function test_fulfilled_redemption_cannot_be_cancelled(): void
    {
        $user = $this->student(500);
        $redemption = app(RewardService::class)->redeemReward($user, $this->reward());

        $redemption->forceFill(['status' => RewardRedemption::STATUS_FULFILLED])->save();

        $this->expectException(RewardRedemptionException::class);

        app(RewardService::class)->cancelRedemption($redemption->fresh(), 'Terlambat');
    }
}