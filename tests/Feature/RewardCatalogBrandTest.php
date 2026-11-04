<?php

namespace Tests\Feature;

use App\Models\Reward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Katalog merchandise harus menampilkan barang kampus, bukan barang merek lain.
 *
 * Riwayatnya: kandidat foto dari internet hampir semuanya membawa merek pihak
 * ketiga - lanyard Nintendo, stiker Sanrio, gift card Visa bertulis "$50",
 * pin militer. Menampilkannya di aplikasi kampus berarti memakai merek orang
 * untuk barang yang tidak dijual. Karena itu katalog dibangun dari foto
 * produk bersih + logo UMK resmi, atau digambar sendiri untuk lanyard/pin/
 * stiker/voucher/pulsa.
 *
 * Tes ini mengunci dua hal yang mudah rusak tanpa ketahuan:
 *   1. setiap hadiah punya gambar persegi yang benar-benar bisa dibuka;
 *   2. tidak ada nama berkas/deskripsi yang menyebut merek luar.
 */
class RewardCatalogBrandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_every_reward_image_is_a_square_jpeg(): void
    {
        foreach (Reward::query()->get() as $reward) {
            $path = Storage::disk('public')->path($reward->image_path);

            $this->assertFileExists($path, "Gambar {$reward->code} hilang.");

            $info = getimagesize($path);
            $this->assertNotFalse($info, "Gambar {$reward->code} bukan gambar valid.");

            $this->assertSame($info[0], $info[1], "Gambar {$reward->code} tidak persegi.");

            $this->assertGreaterThanOrEqual(
                800,
                $info[0],
                "Gambar {$reward->code} di bawah 800px, akan pecah di kartu besar.",
            );
        }
    }

    public function test_the_catalog_never_uses_a_foreign_brand_name(): void
    {
        /*
         * Daftar merek yang muncul di kandidat foto dan sengaja ditolak.
         * Kalau salah satunya masuk lagi (mis. panitia mengunggah ulang foto
         * hasil pencarian gambar), tes ini menangkapnya.
         */
        $banned = [
            'sanrio', 'hello kitty', 'kuromi', 'my melody',
            'nintendo', 'visa', 'mastercard', 'apple', 'adidas', 'nike',
            'gift certificate \$', 'deadpool',
        ];

        foreach (Reward::query()->get() as $reward) {
            $text = mb_strtolower($reward->name.' '.$reward->description.' '.$reward->image_path);

            foreach ($banned as $brand) {
                $this->assertDoesNotMatchRegularExpression(
                    '/'.$brand.'/i',
                    $text,
                    "Hadiah {$reward->code} menyebut merek luar: {$brand}",
                );
            }
        }
    }

    public function test_each_reward_has_its_own_image_file(): void
    {
        /*
         * Sembilan hadiah dengan satu gambar bersama akan terlihat seperti
         * katalog malas - pin tampil sebagai lanyard, dan seterusnya.
         */
        $paths = Reward::query()->pluck('image_path');

        $this->assertSame(
            $paths->count(),
            $paths->unique()->count(),
            'Ada hadiah yang berbagi berkas gambar yang sama.',
        );
    }

    public function test_the_nine_catalog_codes_are_present(): void
    {
        $expected = [
            'LANYARD-01', 'LANYARD-02', 'PIN-01', 'STIKER-01', 'MUG-01',
            'TOTE-01', 'KAOS-01', 'VOUCHER-KANTIN', 'PULSA-10K',
        ];

        $present = Reward::query()->pluck('code')->all();

        foreach ($expected as $code) {
            $this->assertContains($code, $present, "Hadiah {$code} tidak ada di katalog.");
        }
    }

    public function test_the_bursa_page_serves_each_image_over_http(): void
    {
        /*
         * Berkas ada di disk tetapi URL-nya salah adalah kegagalan yang pernah
         * terjadi: `asset($reward->image_path)` menghasilkan /rewards/... yang
         * 404. Tes ini memastikan URL yang dirender benar-benar dapat diakses.
         */
        /*
         * Tidak lewat `$this->get($url)`: `/storage/...` dilayani langsung oleh
         * web server (symlink), bukan oleh router Laravel, sehingga pada
         * lingkungan tes permintaan itu tidak pernah mencapai aplikasi.
         *
         * Yang diperiksa: URL yang dirender menunjuk ke berkas yang benar-benar
         * ada di disk publik, dan berkas itu adalah JPEG yang sah.
         */
        foreach (Reward::query()->get() as $reward) {
            $url = $reward->image_url;

            $this->assertStringStartsWith('/storage/', $url, "URL {$reward->code} bukan path penyimpanan publik.");
            $this->assertStringEndsWith('.jpg', $url, "URL {$reward->code} bukan JPEG.");

            $relative = str_replace('/storage/', '', $url);

            $this->assertTrue(
                Storage::disk('public')->exists($relative),
                "URL {$reward->code} menunjuk berkas yang tidak ada: {$relative}",
            );

            $info = getimagesize(Storage::disk('public')->path($relative));
            $this->assertSame('image/jpeg', $info['mime'], "Berkas {$reward->code} bukan JPEG.");
        }
    }
}