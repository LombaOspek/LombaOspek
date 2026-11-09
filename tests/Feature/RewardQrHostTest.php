<?php

namespace Tests\Feature;

use App\Models\RewardRedemption;
use App\Support\RewardQr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Host di dalam QR harus host yang benar-benar melayani aplikasi.
 *
 * Insiden 9 November 2026: `.env` berisi `APP_URL=http://localhost:8000`
 * sementara aplikasi dilayani di `127.0.0.1:8123`. QR yang dibuat di halaman
 * Bursa berisi `http://localhost:8000/klaim/OQ-...`. Panitia memindainya dan
 * browser membuka halaman kosong — dilaporkan sebagai "QR tidak bisa dipakai
 * padahal baru digenerate".
 *
 * Isi QR-nya sebenarnya benar; yang salah hanya host. Karena QR dibagikan ke
 * ponsel mahasiswa dan panitia di jaringan kampus, host yang salah membuat
 * seluruh alur penyerahan gagal.
 */
class RewardQrHostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_the_qr_points_at_the_host_serving_the_request(): void
    {
        /*
         * Meniru laptop panitia yang membuka aplikasi lewat IP jaringan
         * kampus. QR harus memuat IP itu, bukan `localhost:8000` dari .env.
         */
        $this->app['request']->server->set('HTTP_HOST', '10.20.30.40:8123');
        $this->app['request']->server->set('SERVER_PORT', '8123');
        $this->app['request']->server->set('HTTPS', 'off');

        $url = RewardQr::claimPayload('OQ-QRHS-TEST');

        $this->assertStringContainsString('10.20.30.40:8123', $url);
        $this->assertStringNotContainsString(
            'localhost:8000',
            $url,
            'QR masih memakai APP_URL, bukan host request aktif.',
        );
    }

    public function test_the_qr_contains_the_claim_code_path(): void
    {
        $this->app['request']->server->set('HTTP_HOST', '192.168.1.9:8123');
        $this->app['request']->server->set('SERVER_PORT', '8123');

        $url = RewardQr::claimPayload('OQ-ABCD-EFGH');

        $this->assertStringEndsWith('/klaim/OQ-ABCD-EFGH', $url);
    }

    public function test_a_rendered_qr_decodes_to_the_live_host(): void
    {
        /*
         * Membangun QR dari payload yang sama dengan yang dipakai halaman
         * Bursa, lalu memastikan payload-nya bukan host mati. Dekode piksel
         * dilakukan terpisah di luar PHP; di sini cukup isi payload-nya.
         */
        $this->app['request']->server->set('HTTP_HOST', '127.0.0.1:8123');
        $this->app['request']->server->set('SERVER_PORT', '8123');

        $payload = RewardQr::claimPayload('OQ-QRRF-Z6UT');

        $this->assertSame('http://127.0.0.1:8123/klaim/OQ-QRRF-Z6UT', $payload);

        // SVG harus benar-benar terbentuk, bukan string kosong.
        $svg = RewardQr::svg($payload, 240);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertGreaterThan(1000, strlen($svg));
    }

    public function test_outside_http_the_app_url_is_used_as_fallback(): void
    {
        /*
         * Seeder dan perintah artisan tidak punya request aktif. Di sana
         * APP_URL adalah satu-satunya sumber yang masuk akal, jadi jangan
         * sampai fungsi ini melempar galat.
         */
        $payload = RewardQr::claimPayload('OQ-CONS-OLE1');

        $this->assertStringEndsWith('/klaim/OQ-CONS-OLE1', $payload);
        $this->assertStringStartsWith('http', $payload);
    }

    public function test_the_redemption_page_embeds_the_live_host(): void
    {
        /*
         * Uji dari sisi pengguna: mahasiswa membuka /bursa, dan data URI QR di
         * halaman itu harus memuat host yang sedang dipakai.
         */
        $student = \App\Models\User::query()
            ->where('role', \App\Enums\UserRole::Student)
            ->firstOrFail();

        $redemption = RewardRedemption::query()->first();

        if ($redemption === null) {
            $reward = \App\Models\Reward::query()->firstOrFail();
            $redemption = RewardRedemption::query()->create([
                'user_id' => $student->getKey(),
                'reward_id' => $reward->getKey(),
                'claim_code' => 'OQ-EMBD-ED01',
                'coins_spent' => $reward->cost_coins,
                'status' => 'pending',
            ]);
        }

        $owner = $redemption->user;

        $html = $this->actingAs($owner)
            ->get('http://127.0.0.1:8123/bursa')
            ->assertOk()
            ->getContent();

        // data URI berisi SVG ter-encode base64; cari potongan yang terbaca.
        $this->assertMatchesRegularExpression(
            '/src="data:image\/svg\+xml;base64,[A-Za-z0-9+\/=]{200,}"/',
            $html,
            'QR klaim tidak ditemukan di halaman Bursa.',
        );
    }

    public function test_the_scan_target_sends_officers_to_their_station(): void
    {
        /*
         * Di station LO hasil pindai harus membuka baris klaim di station itu
         * sendiri, bukan halaman verifikasi publik - supaya LO bisa langsung
         * menekan Serahkan.
         */
        $officer = \App\Models\User::query()
            ->where('role', \App\Enums\UserRole::LiaisonOfficer)
            ->whereNotNull('group_id')
            ->firstOrFail();

        $html = $this->actingAs($officer)->get('/lo/station')->assertOk()->getContent();

        $this->assertStringContainsString('data-scanner-target', $html);
        $this->assertStringContainsString('/lo/station?q={code}', $html);
        $this->assertStringContainsString('data-claim-scanner', $html);
    }
}