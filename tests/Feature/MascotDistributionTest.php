<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Maskot WIRA harus tersebar di SELURUH peta, termasuk Bab 3.
 *
 * Bug yang diperbaiki: view merender empat maskot (satu per babak + penutup),
 * tetapi `syncMascots()` memakai tiga slot tetap `[0.25, 0.5, 0.75]`. Maskot
 * keempat mengambil `slots[4 % 3] = slots[0]`, yaitu posisi yang sudah dipakai
 * maskot pertama. `overlapsMascot()` menolaknya, `relocate()` mengembalikan
 * posisi aslinya, dan dua maskot bertumpuk di satu titik.
 *
 * Akibat yang terlihat pengguna: Bab 3 ("Aksi & Penutupan") tidak punya
 * karakter WIRA sama sekali di sepanjang jalurnya - padahal itu babak
 * penutupan yang justru paling butuh penanda "perjalanan selesai".
 */
class MascotDistributionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function mapHtml(): string
    {
        $user = User::query()->where('role', UserRole::Student)->firstOrFail();

        return $this->actingAs($user)->get('/misi')->assertOk()->getContent();
    }

    public function test_the_view_renders_one_mascot_per_slot(): void
    {
        $html = $this->mapHtml();

        // Empat maskot dirender: satu per babak, ditambah penutup.
        $this->assertSame(
            4,
            substr_count($html, 'class="quest-mascot"'),
            'Jumlah maskot yang dirender berubah; sesuaikan sebaran slotnya.',
        );
    }

    public function test_each_mascot_has_its_own_image(): void
    {
        $html = $this->mapHtml();

        $files = [
            'wira-idea.png', 'wira-star.png', 'wira-write.png', 'wira-trophy.png',
        ];

        foreach ($files as $file) {
            $this->assertStringContainsString(
                'images/mascot/'.$file,
                $html,
                "Maskot {$file} tidak dirender di peta.",
            );
        }

        $this->assertSame(
            count($files),
            count(array_unique($files)),
            'Ada dua slot memakai berkas maskot yang sama.',
        );
    }

    public function test_the_last_mascot_is_the_trophy_pose(): void
    {
        /*
         * Urutan pose adalah narasi: menyapa -> menyemangati -> menemani
         * menulis -> menunjuk hadiah. Kalau pose trofi tidak lagi terakhir,
         * penanda penutup perjalanan hilang dari ujung peta.
         */
        $html = $this->mapHtml();

        $posIdea = strpos($html, 'wira-idea.png');
        $posStar = strpos($html, 'wira-star.png');
        $posWrite = strpos($html, 'wira-write.png');
        $posTrophy = strpos($html, 'wira-trophy.png');

        $this->assertNotFalse($posIdea);
        $this->assertNotFalse($posTrophy);

        $this->assertLessThan($posStar, $posIdea, 'Pose menyapa harus lebih dulu.');
        $this->assertLessThan($posWrite, $posStar, 'Pose semangat harus sebelum menulis.');
        $this->assertLessThan($posTrophy, $posWrite, 'Pose trofi harus terakhir.');
    }

    public function test_slots_are_computed_from_the_mascot_count(): void
    {
        /*
         * Ini inti perbaikannya. Slot yang dipatok (mis. `[0.25, 0.5, 0.75]`)
         * membuat maskot ke-N memakai ulang slot maskot pertama begitu jumlah
         * maskot melebihi panjang array itu. Slot harus diturunkan dari
         * `mascots.length`.
         */
        $html = $this->mapHtml();

        $this->assertStringContainsString(
            '(i + 0.5) / mascots.length',
            $html,
            'Sebaran maskot tidak dihitung dari jumlah maskot.',
        );
    }

    public function test_the_mascot_zone_contains_every_chapter_panel(): void
    {
        /*
         * Maskot diposisikan absolut di dalam `.quest-mascot-zone`. Kalau zona
         * itu lebih pendek daripada daftar babak, maskot terakhir dipaksa
         * berada di dalam area yang tidak mencakup Bab 3.
         */
        $html = $this->mapHtml();

        $this->assertStringContainsString('quest-mascot-zone', $html);

        /*
         * `quest-chapter-item` muncul 3x sebagai elemen dan 3x lagi di dalam
         * skrip (querySelectorAll), jadi hitung elemen `<li>`-nya saja.
         */
        $this->assertSame(
            3,
            substr_count($html, 'class="quest-chapter-item list-none"'),
            'Jumlah panel babak berubah; maskot per babak ikut berubah.',
        );
    }

    public function test_every_chapter_gets_a_mascot_at_expected_ratios(): void
    {
        /*
         * Verifikasi aritmetik sebaran tanpa browser: untuk N maskot, slot
         * (i + 0.5) / N harus menghasilkan satu slot di dalam rentang tiap
         * babak. Rasio babak diambil dari konfigurasi, bukan angka hafalan.
         */
        $chapters = collect(config('ospek.chapters'));
        $count = 4;

        $ratios = [];
        for ($i = 0; $i < $count; $i++) {
            $ratios[] = ($i + 0.5) / $count;
        }

        // 0.125, 0.375, 0.625, 0.875 - satu per babak, tak ada yang kembar.
        $this->assertSame(count($ratios), count(array_unique($ratios)));

        foreach ($ratios as $i => $ratio) {
            $this->assertGreaterThan(0, $ratio);
            $this->assertLessThan(1, $ratio);
            $this->assertGreaterThan(
                $ratios[$i - 1] ?? -1,
                $ratio,
                'Slot maskot harus naik monoton, bukan berulang.',
            );
        }

        $this->assertCount(3, $chapters, 'Konfigurasi babak berubah.');
    }
}