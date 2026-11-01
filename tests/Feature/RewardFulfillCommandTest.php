<?php

namespace Tests\Feature;

use App\Models\Reward;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Perintah panitia: menyerahkan hadiah dan membatalkan klaim.
 *
 * Yang dijaga di sini adalah integritas meja penyerahan: satu kode klaim tidak
 * boleh diserahkan dua kali, dan klaim yang sudah diserahkan tidak boleh
 * dibatalkan diam-diam.
 */
class RewardFulfillCommandTest extends TestCase
{
    use RefreshDatabase;

    private function student(int $coins = 1000): User
    {
        return User::create([
            'nim' => '202653066',
            'name' => 'Uji Panitia',
            'email' => 'panitia-test@example.test',
            'password' => Hash::make('password'),
            'role' => 'student',
            'xp' => 100,
            'coins' => $coins,
            'level' => 1,
        ]);
    }

    private function reward(): Reward
    {
        return Reward::create([
            'code' => 'CMD-01',
            'name' => 'Mug Uji',
            'category' => 'merchandise',
            'cost_coins' => 100,
            'stock' => 5,
            'is_active' => true,
        ]);
    }

    public function test_marks_a_pending_claim_as_fulfilled(): void
    {
        $user = $this->student();
        $redemption = app(RewardService::class)->redeemReward($user, $this->reward());

        $this->artisan('reward:fulfill', ['code' => $redemption->claim_code])
            ->assertSuccessful();

        $redemption->refresh();

        $this->assertSame('fulfilled', $redemption->status);
        $this->assertNotNull($redemption->fulfilled_at);
    }

    public function test_code_is_case_insensitive(): void
    {
        $user = $this->student();
        $redemption = app(RewardService::class)->redeemReward($user, $this->reward());

        $this->artisan('reward:fulfill', ['code' => strtolower($redemption->claim_code)])
            ->assertSuccessful();

        $this->assertSame('fulfilled', $redemption->fresh()->status);
    }

    public function test_unknown_code_fails(): void
    {
        $this->artisan('reward:fulfill', ['code' => 'OQ-ZZZZ-ZZZZ'])
            ->assertFailed();
    }

    public function test_cancel_requires_a_reason(): void
    {
        $user = $this->student();
        $redemption = app(RewardService::class)->redeemReward($user, $this->reward());

        $this->artisan('reward:fulfill', [
            'code' => $redemption->claim_code,
            '--cancel' => true,
        ])->assertFailed();

        // Tanpa alasan, klaim harus tetap pending.
        $this->assertSame('pending', $redemption->fresh()->status);
    }

    public function test_cancel_refunds_coins_and_restores_stock(): void
    {
        $user = $this->student(1000);
        $reward = $this->reward();
        $redemption = app(RewardService::class)->redeemReward($user, $reward);

        $this->assertSame(900, $user->fresh()->coins);
        $this->assertSame(4, $reward->fresh()->stock);

        $this->artisan('reward:fulfill', [
            'code' => $redemption->claim_code,
            '--cancel' => true,
            '--reason' => 'Stok rusak',
        ])->assertSuccessful();

        $this->assertSame(1000, $user->fresh()->coins);
        $this->assertSame(5, $reward->fresh()->stock);
        $this->assertSame('cancelled', $redemption->fresh()->status);
    }

    public function test_fulfilled_claim_cannot_be_cancelled(): void
    {
        $user = $this->student();
        $redemption = app(RewardService::class)->redeemReward($user, $this->reward());

        $this->artisan('reward:fulfill', ['code' => $redemption->claim_code])
            ->assertSuccessful();

        $this->artisan('reward:fulfill', [
            'code' => $redemption->claim_code,
            '--cancel' => true,
            '--reason' => 'Terlambat',
        ])->assertFailed();

        $this->assertSame('fulfilled', $redemption->fresh()->status);
    }
}