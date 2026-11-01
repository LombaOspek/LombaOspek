<?php

namespace Tests\Feature;

use App\Models\EngagementClaim;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\EngagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Lima fitur engagement harus aman terhadap klien yang nakal.
 *
 * Yang diuji di sini bukan tampilan, melainkan janji yang mahal kalau bocor:
 * hadiah tidak bisa di-farm, dan tidak ada XP yang dibayarkan dua kali.
 */
class EngagementTest extends TestCase
{
    use RefreshDatabase;

    private function student(string $nim = '202653099'): User
    {
        return User::create([
            'nim' => $nim,
            'name' => 'Uji Engagement',
            'email' => $nim.'@example.test',
            'password' => Hash::make('password'),
            'role' => 'student',
            'xp' => 0,
            'level' => 1,
            'group_name' => 'Kelompok 01 — Merpati',
        ]);
    }

    public function test_daily_box_pays_once_per_day(): void
    {
        $user = $this->student();
        $service = app(EngagementService::class);

        $first = $service->claimDailyBox($user);

        $this->assertFalse($first['already']);
        $this->assertGreaterThan(0, $first['xp']);

        // Klaim kedua di hari yang sama harus ditolak tanpa membayar lagi.
        $second = $service->claimDailyBox($user);

        $this->assertTrue($second['already']);
        $this->assertSame(0, $second['xp']);

        $this->assertSame(1, XpTransaction::where('user_id', $user->id)->where('source_type', 'daily_box')->count());
        $this->assertSame(1, EngagementClaim::where('user_id', $user->id)->where('kind', 'daily_box')->count());
    }

    public function test_daily_box_pays_again_on_a_new_day(): void
    {
        $user = $this->student();
        $service = app(EngagementService::class);

        Carbon::setTestNow('2026-11-01 09:00:00');
        $service->claimDailyBox($user);

        Carbon::setTestNow('2026-11-02 09:00:00');
        $second = $service->claimDailyBox($user);

        $this->assertFalse($second['already'], 'Kotak harus tersedia lagi di hari berikutnya.');
        $this->assertSame(2, EngagementClaim::where('user_id', $user->id)->where('kind', 'daily_box')->count());

        Carbon::setTestNow();
    }

    public function test_daily_box_reward_is_deterministic_for_the_same_day(): void
    {
        $user = $this->student();
        $service = app(EngagementService::class);

        $before = $service->dailyBox($user);
        $this->assertFalse($before['claimed']);

        $claimed = $service->claimDailyBox($user);
        $after = $service->dailyBox($user);

        // Hadiah yang dibayarkan harus sama dengan yang dijanjikan sebelum klaim.
        $this->assertSame($before['value'], $claimed['xp']);
        $this->assertSame($before['value'], $after['value']);
        $this->assertTrue($after['claimed']);
    }

    public function test_secret_mission_rejects_fewer_clicks(): void
    {
        $user = $this->student();

        $result = app(EngagementService::class)->claimSecretMission($user, 3);

        $this->assertSame(0, $result['xp']);
        $this->assertFalse($result['already']);
        $this->assertSame(0, XpTransaction::where('user_id', $user->id)->where('source_type', 'secret_mission')->count());
    }

    public function test_secret_mission_can_only_be_found_once(): void
    {
        $user = $this->student();
        $service = app(EngagementService::class);

        $first = $service->claimSecretMission($user, 7);
        $second = $service->claimSecretMission($user, 7);

        $this->assertFalse($first['already']);
        $this->assertGreaterThan(0, $first['xp']);

        $this->assertTrue($second['already']);
        $this->assertSame(0, $second['xp']);

        $this->assertSame(1, XpTransaction::where('user_id', $user->id)->where('source_type', 'secret_mission')->count());
    }

    public function test_daily_box_modal_is_scoped_to_the_logged_in_user(): void
    {
        // Regresi: penanda klien dulu hanya berisi tanggal, sehingga mahasiswa
        // berikutnya yang login di perangkat yang sama (lab kampus) tidak
        // pernah melihat kotaknya sendiri.
        $user = $this->student();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-user="'.$user->id.'"', false);
    }

    public function test_endpoint_refuses_duplicate_claim_with_409(): void
    {
        $user = $this->student();

        $this->actingAs($user)->postJson(route('engagement.daily-box'))->assertOk();
        $this->actingAs($user)->postJson(route('engagement.daily-box'))
            ->assertStatus(409)
            ->assertJsonPath('reason', 'already_claimed');
    }

    public function test_endpoint_refuses_secret_mission_with_too_few_clicks(): void
    {
        $user = $this->student();

        $this->actingAs($user)
            ->postJson(route('engagement.secret-mission'), ['clicks' => 2])
            ->assertStatus(422)
            ->assertJsonPath('reason', 'not_enough_clicks');
    }

    public function test_titles_come_from_xp_bands(): void
    {
        $service = app(EngagementService::class);

        $low = $this->student('202653090');
        $low->xp = 10;
        $this->assertSame('Maba Baru', $service->titleFor($low)['label']);

        $high = $this->student('202653091');
        $high->xp = 2300;
        $this->assertSame('Sang Ambis', $service->titleFor($high)['label']);
        $this->assertSame('ember', $service->titleFor($high)['frame']);
    }

    public function test_gugus_wars_needs_two_groups(): void
    {
        $user = $this->student();

        $wars = app(EngagementService::class)->gugusWars($user);

        $this->assertFalse($wars['available']);
        $this->assertNull($wars['left']);
    }

    public function test_gugus_wars_ratio_reflects_real_xp(): void
    {
        $me = $this->student('202653092');
        $me->update(['xp' => 300, 'group_name' => 'Gugus A']);

        $other = $this->student('202653093');
        $other->update(['xp' => 100, 'group_name' => 'Gugus B']);

        $wars = app(EngagementService::class)->gugusWars($me);

        $this->assertTrue($wars['available']);
        $this->assertSame('left', $wars['my_side']);
        // 300 vs 100 -> 75%.
        $this->assertEqualsWithDelta(0.75, $wars['ratio'], 0.01);
    }

    public function test_live_feed_excludes_the_viewer_and_never_leaks_secret(): void
    {
        $me = $this->student('202653094');
        $friend = $this->student('202653095');

        // Aktivitas sahabat, termasuk satu misi rahasia yang tidak boleh tampil.
        XpTransaction::create([
            'user_id' => $friend->id, 'amount' => 120, 'balance_after' => 120,
            'source_type' => 'quest', 'source_id' => 1, 'description' => 'Modul',
            'idempotency_key' => 'feed:friend:1',
        ]);
        XpTransaction::create([
            'user_id' => $friend->id, 'amount' => 150, 'balance_after' => 270,
            'source_type' => 'secret_mission', 'source_id' => null, 'description' => 'Rahasia',
            'idempotency_key' => 'feed:friend:2',
        ]);
        XpTransaction::create([
            'user_id' => $me->id, 'amount' => 99, 'balance_after' => 99,
            'source_type' => 'quest', 'source_id' => 1, 'description' => 'Milik sendiri',
            'idempotency_key' => 'feed:me:1',
        ]);

        $feed = app(EngagementService::class)->liveFeed($me);

        $this->assertCount(1, $feed, 'Hanya aktivitas orang lain yang layak tampil.');
        $this->assertSame('menyelesaikan modul', $feed->first()['action']);

        // Nama disamarkan jadi "Depan I." — bukan nama lengkap, dan misi
        // rahasia milik orang lain tidak pernah muncul di feed.
        $this->assertSame('Uji E.', $feed->first()['name']);
    }

    public function test_summary_endpoint_returns_all_widgets(): void
    {
        $user = $this->student();

        $this->actingAs($user)
            ->getJson(route('engagement.summary'))
            ->assertOk()
            ->assertJsonStructure([
                'gugus_wars' => ['available', 'ratio'],
                'live_feed',
                'daily_box' => ['key', 'claimed', 'value'],
                'title' => ['label', 'frame'],
                'server_time',
            ]);
    }

    public function test_engagement_endpoints_require_authentication(): void
    {
        $this->postJson(route('engagement.daily-box'))->assertUnauthorized();
        $this->postJson(route('engagement.secret-mission'), ['clicks' => 7])->assertUnauthorized();
    }
}
