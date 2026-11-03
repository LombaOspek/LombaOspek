<?php

namespace Tests\Feature;

use App\Models\CoinTransaction;
use App\Models\Quest;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\CoinService;
use App\Services\ProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Koin harus punya SUMBER.
 *
 * Sebelum ini, XpService hanya menulis users.xp dan koin tidak pernah bertambah
 * dari aktivitas apa pun — sehingga Bursa Hadiah menampilkan "Koin Kurang"
 * untuk seluruh mahasiswa. Tes ini mengunci sambungan itu.
 */
class CoinEconomyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\QuestSeeder::class);
        $this->seed(\Database\Seeders\SideQuestSeeder::class);
    }

    private function student(): User
    {
        return User::create([
            'nim' => '202699001',
            'name' => 'Uji Koin',
            'email' => 'koin@example.test',
            'password' => Hash::make('password'),
            'role' => 'student',
            'xp' => 0,
            'coins' => 0,
            'level' => 1,
        ]);
    }

    public function test_rates_are_configured(): void
    {
        $this->assertSame(0.10, config('ospek.coins.rates.quest'));
        $this->assertSame(1.00, config('ospek.coins.rates.side_quest'));
        $this->assertSame(50, config('ospek.coins.flat.achievement'));
    }

    public function test_side_quest_pays_more_than_a_main_module(): void
    {
        $service = app(CoinService::class);

        // 150 XP: modul 10% = 15 koin, side quest 100% = 150 koin.
        $this->assertSame(15, $service->coinsFor(150, 'quest'));
        $this->assertSame(150, $service->coinsFor(150, 'side_quest'));
    }

    public function test_flat_rewards_ignore_xp_amount(): void
    {
        $service = app(CoinService::class);

        $this->assertSame(50, $service->coinsFor(500, 'achievement'));
        $this->assertSame(50, $service->coinsFor(50, 'achievement'));
        $this->assertSame(20, $service->coinsFor(120, 'daily_box'));
    }

    public function test_small_xp_yields_no_coins(): void
    {
        $service = app(CoinService::class);

        // Di bawah min_xp: tidak ada koin, supaya tidak ada baris ledger nol.
        $this->assertSame(0, $service->coinsFor(7, 'quest'));
    }

    /**
     * Modul refleksi tanpa verifikasi — jalur termudah untuk menguji award
     * tanpa perlu mengunggah bukti foto atau menebak token.
     */
    private function reflectionQuest(User $user): Quest
    {
        $quest = Quest::query()
            ->mainline()
            ->where('verification_method', 'none')
            ->where('action_type', 'reflection')
            ->ordered()
            ->firstOrFail();

        // Buka rantai prasyarat: selesaikan modul sebelumnya lewat service.
        // Tanpa ini statusnya masih terkunci dan endpoint menolak.
        $progression = app(ProgressionService::class);

        foreach (Quest::query()->mainline()->ordered()->get() as $earlier) {
            if ($earlier->step_number >= $quest->step_number) {
                break;
            }

            $progression->complete($user, $earlier);
        }

        return $quest;
    }

    public function test_completing_a_module_grants_coins_alongside_xp(): void
    {
        $user = $this->student();
        $quest = $this->reflectionQuest($user);
        $before = $user->coins;

        $this->actingAs($user)->post("/misi/{$quest->getKey()}/selesai", [
            'confirm' => '1',
            'reflection' => str_repeat('Refleksi yang cukup panjang untuk lolos validasi. ', 2),
        ]);

        $fresh = $user->fresh();

        $this->assertGreaterThan(0, $fresh->xp, 'XP harus bertambah.');
        $this->assertGreaterThan($before, $fresh->coins, 'Koin harus bertambah bersamaan dengan XP.');
        $this->assertSame((int) floor($quest->xp_reward * 0.10), $fresh->coins);
    }

    public function test_coin_ledger_records_the_mutation(): void
    {
        $user = $this->student();
        $quest = $this->reflectionQuest($user);

        $this->actingAs($user)->post("/misi/{$quest->getKey()}/selesai", [
            'confirm' => '1',
            'reflection' => str_repeat('Refleksi yang cukup panjang untuk lolos validasi. ', 2),
        ]);

        $row = CoinTransaction::where('user_id', $user->id)->sole();

        $this->assertSame((int) floor($quest->xp_reward * 0.10), $row->amount);
        $this->assertSame($user->fresh()->coins, $row->balance_after);
    }

    public function test_xp_and_coins_stay_reconciled(): void
    {
        $user = $this->student();
        $quest = $this->reflectionQuest($user);

        $this->actingAs($user)->post("/misi/{$quest->getKey()}/selesai", [
            'confirm' => '1',
            'reflection' => str_repeat('Refleksi yang cukup panjang untuk lolos validasi. ', 2),
        ]);

        $audit = app(CoinService::class)->audit($user->fresh());

        $this->assertTrue($audit['consistent'], 'users.coins harus sama dengan SUM(coin_transactions).');
        $this->assertSame(0, $audit['difference']);
    }

    public function test_award_is_idempotent(): void
    {
        $user = $this->student();
        $service = app(CoinService::class);

        $first = $service->award($user, 100, 'quest', 1, 'Uji', 'key:uji:1');
        $second = $service->award($user, 100, 'quest', 1, 'Uji ulang', 'key:uji:1');

        $this->assertSame(100, $first);
        $this->assertNull($second, 'Kunci yang sama tidak boleh menggandakan koin.');
        $this->assertSame(1, CoinTransaction::where('user_id', $user->id)->count());
    }

    public function test_repeat_quiz_attempt_does_not_duplicate_coins(): void
    {
        $user = $this->student();
        $service = app(CoinService::class);

        $service->awardForXp($user, 140, 'quiz', 1, 'Kuis', 'quiz:'.$user->id.':MOD-04');
        $service->awardForXp($user, 140, 'quiz', 1, 'Kuis', 'quiz:'.$user->id.':MOD-04');

        $this->assertSame(1, CoinTransaction::where('user_id', $user->id)->count());
    }

    public function test_coins_never_go_negative(): void
    {
        $user = $this->student();
        $service = app(CoinService::class);

        $service->award($user, -50, 'quest', 1, 'Potongan', 'key:negatif');

        $this->assertSame(0, $user->fresh()->coins);
        $this->assertGreaterThanOrEqual(0, $user->fresh()->coins);
    }
}
