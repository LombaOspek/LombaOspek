<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pemindai QR harus bekerja di browser lapangan, bukan hanya di Chrome desktop.
 *
 * Dua bug ditemukan pada 9 November 2026:
 *
 * 1. TOMBOL KAMERA HILANG DI BANYAK BROWSER
 *    Kode lama memeriksa `'BarcodeDetector' in window`, dan bila tidak ada
 *    langsung menyembunyikan tombol kamera. Di iPhone/Safari API ini tidak
 *    pernah ada, dan di Chrome yang diuji juga tidak tersedia. Artinya pemindai
 *    mati total di perangkat yang paling mungkin dipakai panitia di meja
 *    station. Sekarang ada cadangan `jsQR` yang berjalan di atas <canvas>.
 *
 * 2. INPUT MANUAL IKUT MATI
 *    Pemeriksaan di atas melakukan `return` sebelum form manual didaftarkan,
 *    sehingga satu-satunya jalan yang masih bekerja (mengetik kode) juga tidak
 *    merespons. Sekarang form manual didaftarkan paling awal.
 *
 * Keduanya tidak akan terlihat dari PHP, jadi tes ini mengunci sumber JS-nya.
 */
class ClaimScannerFallbackTest extends TestCase
{
    use RefreshDatabase;

    private function scannerSource(): string
    {
        return file_get_contents(resource_path('js/claim-scanner.js'));
    }

    public function test_a_fallback_decoder_is_bundled(): void
    {
        $src = $this->scannerSource();

        $this->assertStringContainsString(
            "from 'jsqr'",
            $src,
            'Pemindai tidak punya cadangan untuk browser tanpa BarcodeDetector '
            .'(Safari/iPhone, Chrome tertentu). Tambahkan jsQR.',
        );

        $this->assertStringContainsString('jsQR(', $src, 'jsQR diimpor tetapi tidak pernah dipanggil.');
    }

    public function test_the_camera_button_is_not_hidden_just_because_barcode_detector_is_absent(): void
    {
        $src = $this->scannerSource();

        /*
         * Pola lama: sembunyikan tombol lalu `return`. Yang dicari adalah
         * apakah tombol masih disembunyikan di dalam cabang BarcodeDetector.
         */
        $this->assertStringNotContainsString(
            'if (!(\'BarcodeDetector\' in window)) {',
            $src,
            'Kode lama kembali: tombol kamera disembunyikan saat BarcodeDetector tidak ada.',
        );

        $this->assertStringContainsString(
            "'BarcodeDetector' in window",
            $src,
            'Deteksi BarcodeDetector hilang; pemindai native tidak akan dipakai.',
        );
    }

    public function test_manual_input_is_registered_before_capability_checks(): void
    {
        /*
         * Kalau pendaftaran form manual berada SETELAH `return` kapabilitas,
         * input manual mati di browser tanpa pemindai — persis bug yang
         * dilaporkan ("tidak bisa" padahal kode baru digenerate).
         */
        $src = $this->scannerSource();

        $manualPos = strpos($src, 'manualForm.addEventListener');
        $this->assertNotFalse($manualPos, 'Handler form manual tidak ditemukan.');

        $returnPos = strpos($src, "if (!hasCamera) {");
        $this->assertNotFalse($returnPos, 'Pemeriksaan kamera tidak ditemukan.');

        $this->assertLessThan(
            $returnPos,
            $manualPos,
            'Form manual didaftarkan setelah pemeriksaan kamera; ia akan mati '
            .'di browser yang tidak mendukung pemindai.',
        );
    }

    public function test_the_scan_target_can_be_overridden_per_page(): void
    {
        $src = $this->scannerSource();

        $this->assertStringContainsString('data-scanner-target', $src);
        $this->assertStringContainsString('scannerTarget', $src);
    }

    public function test_the_officer_station_offers_a_scanner_and_manual_entry(): void
    {
        $officer = User::query()->firstOrCreate(
            ['nim' => 'LO-SCAN-01'],
            [
                'name' => 'LO Pindai',
                'email' => 'lo.pindai@example.test',
                'password' => bcrypt('rahasia-tes-123'),
                'role' => UserRole::LiaisonOfficer,
                'group_id' => \App\Models\Group::query()->value('id'),
            ],
        );

        $html = $this->actingAs($officer)->get('/lo/station')->assertOk()->getContent();

        $this->assertStringContainsString('data-claim-scanner', $html);
        $this->assertStringContainsString('data-scanner-open', $html);
        $this->assertStringContainsString('data-scanner-code', $html);
        $this->assertStringContainsString('/lo/station?q={code}', $html);
    }

    public function test_the_admin_bursa_still_offers_a_scanner(): void
    {
        /*
         * Seeder tidak membuat akun admin; admin dibuat lewat
         * `php artisan demo:accounts` atau import. Di tes, buat sendiri.
         */
        $admin = User::query()->firstOrCreate(
            ['nim' => 'ADM-SCAN-01'],
            [
                'name' => 'Admin Pindai',
                'email' => 'admin.pindai@example.test',
                'password' => bcrypt('rahasia-tes-123'),
                'role' => UserRole::Admin,
            ],
        );

        $html = $this->actingAs($admin)->get('/admin/bursa')->assertOk()->getContent();

        $this->assertStringContainsString('data-claim-scanner', $html);
        $this->assertStringContainsString('data-scanner-code', $html);
    }
}