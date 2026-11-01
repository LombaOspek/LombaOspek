<?php

namespace Tests\Feature;

use App\Models\CoinTransaction;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Lapisan HTTP Bursa Hadiah: halaman, penukaran, dan kegagalannya.
 *
 * Aturan ekonomi sendiri sudah diuji di RewardStoreTest; di sini yang diuji
 * adalah kontrak HTTP: status kode, bentuk JSON, dan flash message.
 */
class RewardStoreHttpTest extends TestCase
{
    use RefreshDatabase;

    private function student(int $coins = 500, string $nim = '202653077'): User
    {
        return User::create([
            'nim' => $nim,
            'name' => 'Uji Bursa HTTP',
            'email' => $nim.'@example.test',
            'password' => Hash::make('password'),
            'role' => 'student',
            'xp' => 800,
            'coins' => $coins,
            'level' => 2,
        ]);
    }

    private function reward(array $overrides = []): Reward
    {
        return Reward::create(array_merge([
            'code' => 'HTTP-01',
            'name' => 'Tote Bag Uji',
            'category' => 'merchandise',
            'cost_coins' => 200,
            'stock' => 5,
            'is_active' => true,
        ], $overrides));
    }

    public function test_bursa_page_lists_only_active_rewards(): void
    {
        $user = $this->student();

        $this->reward(['code' => 'AKTIF-1', 'name' => 'Hadiah Aktif']);
        $this->reward(['code' => 'MATI-1', 'name' => 'Hadiah Nonaktif', 'is_active' => false]);

        $this->actingAs($user)
            ->get(route('rewards.index'))
            ->assertOk()
            ->assertSee('Hadiah Aktif')
            ->assertDontSee('Hadiah Nonaktif');
    }

    public function test_redeem_returns_claim_code_as_json(): void
    {
        $user = $this->student(500);
        $reward = $this->reward();

        $response = $this->actingAs($user)
            ->postJson(route('rewards.redeem', $reward))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('coins', 300)
            ->assertJsonPath('reward.name', 'Tote Bag Uji');

        $code = $response->json('claim_code');

        $this->assertMatchesRegularExpression('/^OQ-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code);
        $this->assertDatabaseHas('reward_redemptions', [
            'claim_code' => $code,
            'user_id' => $user->id,
            'coins_spent' => 200,
        ]);
    }

    public function test_redeem_redirects_with_toast_for_form_posts(): void
    {
        $user = $this->student(500);
        $reward = $this->reward();

        $this->actingAs($user)
            ->post(route('rewards.redeem', $reward))
            ->assertRedirect(route('rewards.index'))
            ->assertSessionHas('toast');

        $this->assertSame(300, $user->fresh()->coins);
    }

    public function test_redeem_fails_with_402_when_coins_are_insufficient(): void
    {
        $user = $this->student(50);
        $reward = $this->reward(['cost_coins' => 200]);

        $this->actingAs($user)
            ->postJson(route('rewards.redeem', $reward))
            ->assertStatus(402)
            ->assertJsonPath('reason', 'insufficient_coins');

        $this->assertSame(50, $user->fresh()->coins);
        $this->assertSame(0, RewardRedemption::count());
    }

    public function test_redeem_fails_with_409_when_out_of_stock(): void
    {
        $user = $this->student(500);
        $reward = $this->reward(['stock' => 0]);

        $this->actingAs($user)
            ->postJson(route('rewards.redeem', $reward))
            ->assertStatus(409)
            ->assertJsonPath('reason', 'out_of_stock');
    }

    public function test_redeem_fails_when_quota_is_reached(): void
    {
        $user = $this->student(2000);
        $reward = $this->reward(['quota_per_user' => 1, 'stock' => 10]);

        $this->actingAs($user)->postJson(route('rewards.redeem', $reward))->assertOk();

        $this->actingAs($user)
            ->postJson(route('rewards.redeem', $reward))
            ->assertStatus(409)
            ->assertJsonPath('reason', 'quota_exceeded');
    }

    public function test_xp_survives_a_redemption(): void
    {
        $user = $this->student(500);
        $reward = $this->reward();

        $this->actingAs($user)->postJson(route('rewards.redeem', $reward))->assertOk();

        $this->assertSame(800, $user->fresh()->xp, 'XP tidak boleh berubah oleh penukaran.');
        $this->assertSame(1, CoinTransaction::where('user_id', $user->id)->where('amount', '<', 0)->count());
    }

    public function test_bursa_requires_authentication(): void
    {
        $this->get(route('rewards.index'))->assertRedirect(route('login'));
        $this->postJson(route('rewards.redeem', $this->reward()))->assertUnauthorized();
    }
}