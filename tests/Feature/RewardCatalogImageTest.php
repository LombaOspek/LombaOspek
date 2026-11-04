<?php

namespace Tests\Feature;

use App\Models\Reward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Gambar katalog Bursa Hadiah.
 *
 * Sebelum ini 8 dari 9 hadiah tidak punya gambar sama sekali, sehingga kartu
 * hanya menampilkan dua huruf inisial. Katalog merchandise tanpa gambar tidak
 * menjual apa pun — mahasiswa tidak tahu apa yang ditukarnya.
 */
class RewardCatalogImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_every_reward_has_an_image(): void
    {
        $without = Reward::query()->whereNull('image_path')->pluck('code');

        $this->assertTrue(
            $without->isEmpty(),
            'Hadiah tanpa gambar: '.$without->implode(', '),
        );
    }

    public function test_every_image_file_actually_exists(): void
    {
        /*
         * Path yang tercatat di database tetapi filenya tidak ada adalah
         * kegagalan paling mahal di sini: kartu tampil sebagai gambar rusak
         * dan tidak ada yang menyadarinya sampai juri membuka halaman.
         */
        foreach (Reward::query()->get() as $reward) {
            $this->assertTrue(
                Storage::disk('public')->exists($reward->image_path),
                "File gambar {$reward->code} tidak ada: {$reward->image_path}",
            );
        }
    }

    public function test_images_are_real_pictures_not_empty_files(): void
    {
        foreach (Reward::query()->get() as $reward) {
            $path = Storage::disk('public')->path($reward->image_path);

            $this->assertGreaterThan(5000, filesize($path), "Gambar {$reward->code} terlalu kecil / rusak.");

            $info = @getimagesize($path);
            $this->assertNotFalse($info, "Gambar {$reward->code} bukan berkas gambar yang valid.");
            $this->assertGreaterThanOrEqual(400, $info[0], "Gambar {$reward->code} terlalu kecil lebarnya.");
            $this->assertSame($info[0], $info[1], "Gambar {$reward->code} harus persegi agar tidak terpotong.");
        }
    }

    public function test_the_card_uses_object_contain_so_products_are_not_cropped(): void
    {
        /*
         * object-cover pada kotak pendek memotong sisi produk: yang terlihat
         * hanya sepotong bahan, dan mahasiswa tidak bisa menilai barangnya.
         */
        $view = file_get_contents(resource_path('views/pages/rewards.blade.php'));

        // Periksa deklarasi class, bukan seluruh berkas: komentar penjelasan
        // menyebut "object-cover" dan akan menandai dirinya sendiri.
        $this->assertStringContainsString('class="size-full object-contain"', $view);
        $this->assertStringNotContainsString('class="size-full object-cover"', $view);
    }

    public function test_no_test_placeholder_text_remains_in_the_catalog(): void
    {
        foreach (Reward::query()->get() as $reward) {
            $this->assertNotSame('test', strtolower(trim((string) $reward->description)), "Hadiah {$reward->code} masih berisi deskripsi uji.");
            $this->assertNotSame('', trim((string) $reward->name), "Hadiah {$reward->code} tidak punya nama.");
            $this->assertGreaterThan(3, mb_strlen($reward->name), "Nama hadiah {$reward->code} terlalu pendek untuk katalog.");
        }
    }

    public function test_the_catalog_page_renders_all_images(): void
    {
        $user = \App\Models\User::query()->where('role', 'student')->firstOrFail();

        $html = $this->actingAs($user)->get('/bursa')->assertOk()->getContent();

        foreach (Reward::query()->get() as $reward) {
            $this->assertStringContainsString(
                '/storage/'.$reward->image_path,
                $html,
                "Gambar {$reward->code} tidak dirender di halaman Bursa.",
            );
        }
    }
}