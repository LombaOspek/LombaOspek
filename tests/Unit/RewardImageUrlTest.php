<?php

namespace Tests\Unit;

use App\Models\Reward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * UNIT — perilaku accessor `Reward::image_url`.
 *
 * Inkrep: satu-satunya tanggung jawab accessor ini adalah mengubah path
 * relatif disk menjadi URL yang bisa dipakai <img src>. Kalau ia salah,
 * SEMUA halaman yang menampilkan hadiah ikut rusak.
 */
class RewardImageUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_when_there_is_no_image(): void
    {
        $reward = new Reward(['image_path' => null]);

        $this->assertNull($reward->image_url);
    }

    public function test_maps_the_public_disk_path_to_the_storage_url(): void
    {
        $reward = new Reward(['image_path' => 'rewards/lanyard-02-d7855479.jpeg']);

        $this->assertSame('/storage/rewards/lanyard-02-d7855479.jpeg', $reward->image_url);
    }

    public function test_the_url_is_relative_so_it_survives_any_host_or_port(): void
    {
        /*
         * Regresi penting. Sebelumnya accessor memakai Storage::url() yang
         * mengembalikan URL ABSOLUT dari APP_URL. Di proyek ini
         * APP_URL=http://localhost:8000 padahal server dev berjalan di port
         * lain, sehingga src menunjuk port yang salah. URL absolut juga patah
         * begitu aplikasi dipasang di subfolder atau domain lain.
         */
        config(['app.url' => 'https://ospek.umk.ac.id']);

        $reward = new Reward(['image_path' => 'rewards/mug.webp']);

        $this->assertSame('/storage/rewards/mug.webp', $reward->image_url);
        $this->assertStringNotContainsString('ospek.umk.ac.id', $reward->image_url);
        $this->assertStringStartsWith('/', $reward->image_url, 'URL harus relatif terhadap dokumen.');
    }

    public function test_a_leading_slash_in_the_stored_path_does_not_double_up(): void
    {
        $reward = new Reward(['image_path' => '/rewards/pin.png']);

        $this->assertSame('/storage/rewards/pin.png', $reward->image_url);
    }

    public function test_the_image_url_never_points_at_the_bare_path(): void
    {
        /*
         * Inilah bug aslinya: view memakai asset('rewards/x.jpg') yang
         * menghasilkan "/rewards/x.jpg" dan 404, padahal berkas ada di
         * "/storage/rewards/x.jpg". Asersi ini mengunci bentuk URL.
         */
        $reward = new Reward(['image_path' => 'rewards/stiker.webp']);

        $this->assertStringStartsWith('/storage/', $reward->image_url);
        $this->assertStringNotContainsString('//storage', $reward->image_url);
    }
}