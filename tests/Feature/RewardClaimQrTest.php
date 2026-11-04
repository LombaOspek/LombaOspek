<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Support\RewardQr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QR klaim hadiah.
 *
 * Kode klaim berbentuk `OQ-QRRF-Z6UT`. Mengetik itu di depan meja panitia saat
 * ratusan mahasiswa mengantre adalah sumber salah ketik; QR memindahkan beban
 * ke kamera. Kode teks TETAP ada karena ponsel bisa mati dan kamera bisa gagal.
 */
class RewardClaimQrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function student(): User
    {
        return User::query()->where('role', UserRole::Student)->firstOrFail();
    }

    private function pendingRedemption(): RewardRedemption
    {
        $user = $this->student();
        $reward = Reward::query()->firstOrFail();

        return RewardRedemption::query()->create([
            'reward_id' => $reward->getKey(),
            'user_id' => $user->getKey(),
            'claim_code' => 'OQ-TEST-1234',
            'coins_spent' => 150,
            'status' => RewardRedemption::STATUS_PENDING,
        ]);
    }

    public function test_the_qr_is_a_real_svg_not_a_placeholder(): void
    {
        $redemption = $this->pendingRedemption();
        $svg = $redemption->qr_svg;

        $this->assertStringStartsWith('<?xml', $svg);
        $this->assertStringContainsString('<svg', $svg);
        // QR asli bergambar path; SVG kosong tidak akan punya ini.
        $this->assertStringContainsString('<path', $svg);
        $this->assertGreaterThan(500, strlen($svg), 'SVG terlalu pendek untuk berisi QR.');
    }

    public function test_the_qr_encodes_the_verify_url_not_the_bare_code(): void
    {
        /*
         * Isi QR sengaja URL, bukan kode mentah: panitia yang memindai harus
         * langsung melihat status klaim, bukan sekadar teks yang harus dicari
         * manual di panel admin.
         */
        $redemption = $this->pendingRedemption();

        $this->assertSame(
            route('rewards.claim-verify', ['code' => 'OQ-TEST-1234']),
            $redemption->verify_url,
        );
        $this->assertStringContainsString('/klaim/OQ-TEST-1234', $redemption->verify_url);
    }

    public function test_the_data_uri_is_embeddable_in_an_img_tag(): void
    {
        $redemption = $this->pendingRedemption();
        $uri = $redemption->qr_data_uri;

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);

        // Harus benar-benar SVG yang bisa didekode, bukan base64 dari teks lain.
        $decoded = base64_decode(substr($uri, strlen('data:image/svg+xml;base64,')), true);
        $this->assertNotFalse($decoded);
        $this->assertStringContainsString('<svg', $decoded);
    }

    public function test_the_payload_is_stable_for_the_same_code(): void
    {
        /*
         * QR dengan isi berbeda setiap render akan membuat gambar berkedip dan
         * membingungkan saat dicetak. Harus deterministik.
         */
        $a = RewardQr::claimPayload('OQ-AAAA-BBBB');
        $b = RewardQr::claimPayload('OQ-AAAA-BBBB');

        $this->assertSame($a, $b);
        $this->assertNotSame($a, RewardQr::claimPayload('OQ-CCCC-DDDD'));
    }

    public function test_the_student_page_shows_the_qr_for_a_pending_claim(): void
    {
        $user = $this->student();
        $this->pendingRedemption();

        $html = $this->actingAs($user)->get('/bursa')->assertOk()->getContent();

        $this->assertStringContainsString('data:image/svg+xml;base64,', $html);
        $this->assertStringContainsString('OQ-TEST-1234', $html);
        $this->assertStringContainsString('QR klaim OQ-TEST-1234', $html);
    }

    public function test_a_fulfilled_claim_does_not_show_a_qr(): void
    {
        /*
         * Menampilkan QR pada klaim yang sudah diambil mengundang panitia
         * memindainya ulang dan bingung melihat statusnya sendiri.
         */
        $user = $this->student();
        $reward = Reward::query()->firstOrFail();

        RewardRedemption::query()->create([
            'reward_id' => $reward->getKey(),
            'user_id' => $user->getKey(),
            'claim_code' => 'OQ-DONE-9999',
            'coins_spent' => 100,
            'status' => RewardRedemption::STATUS_FULFILLED,
            'fulfilled_at' => now(),
        ]);

        $html = $this->actingAs($user)->get('/bursa')->assertOk()->getContent();

        $this->assertStringContainsString('OQ-DONE-9999', $html);
        $this->assertStringNotContainsString('QR klaim OQ-DONE-9999', $html);
    }

    public function test_the_verify_page_opens_without_login(): void
    {
        /*
         * Panitia di meja penyerahan sering tidak login. Memaksa mereka masuk
         * dulu menghentikan antrean — jadi halaman ini publik, dan hanya
         * menampilkan yang memang tertempel di kartu fisik.
         */
        $this->pendingRedemption();

        $html = $this->get('/klaim/OQ-TEST-1234')->assertOk()->getContent();

        $this->assertStringContainsString('BELUM DISERAHKAN', $html);
        $this->assertStringContainsString($this->student()->name, $html);
        $this->assertStringContainsString($this->student()->nim, $html);
    }

    public function test_the_verify_page_is_case_insensitive(): void
    {
        $this->pendingRedemption();

        $this->get('/klaim/oq-test-1234')->assertOk()
            ->assertSee('BELUM DISERAHKAN');
    }

    public function test_an_unknown_code_shows_a_readable_message_not_an_error(): void
    {
        $this->get('/klaim/OQ-ZZZZ-0000')
            ->assertOk()
            ->assertSee('tidak dikenal');
    }

    public function test_the_verify_page_warns_against_handing_out_twice(): void
    {
        $user = $this->student();
        $reward = Reward::query()->firstOrFail();

        RewardRedemption::query()->create([
            'reward_id' => $reward->getKey(),
            'user_id' => $user->getKey(),
            'claim_code' => 'OQ-DONE-1111',
            'coins_spent' => 100,
            'status' => RewardRedemption::STATUS_FULFILLED,
            'fulfilled_at' => now(),
        ]);

        $this->get('/klaim/OQ-DONE-1111')
            ->assertOk()
            ->assertSee('SUDAH DISERAHKAN')
            ->assertSee('Jangan serahkan barang kedua kali', false);
    }

    public function test_the_admin_page_has_a_scanner_panel_and_qr_in_the_table(): void
    {
        $admin = User::query()->where('role', UserRole::Admin)->firstOrFail();
        $this->pendingRedemption();

        $html = $this->actingAs($admin)->get('/admin/bursa')->assertOk()->getContent();

        $this->assertStringContainsString('data-claim-scanner', $html);
        $this->assertStringContainsString('data-scanner-code', $html);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $html);
    }
}