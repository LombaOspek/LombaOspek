<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Overlay level-up tidak boleh bocor ke halaman lain.
 *
 * Bug yang diperbaiki: `quest-detail` menjadwalkan `levelUp()` 900ms setelah
 * modul selesai. Kalau mahasiswa menekan "Peta Misi" sebelum timer itu habis,
 * overlay ikut terpasang di halaman TUJUAN — latar jadi gelap 70% dengan kartu
 * kuning yang tidak diminta. Diukur: kecerahan rata-rata halaman turun 21%
 * (median 234 → 170) dan tidak ada satu pun piksel putih murni (max 193).
 *
 * Ini bukan kosmetik: mahasiswa mengira aplikasinya rusak, dan karena overlay
 * memakai `z-[10000]` seluruh peta misi tidak bisa diklik.
 */
class LevelUpOverlayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_the_level_up_timer_is_cancelled_when_leaving_the_page(): void
    {
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();
        $quest = \App\Models\Quest::query()->where('code', 'MOD-01')->firstOrFail();

        $html = $this->actingAs($student)->get("/misi/{$quest->getKey()}")->assertOk()->getContent();

        /*
         * Tanpa clearTimeout pada pagehide/beforeunload, timer 900ms menyala
         * setelah dokumen lama dibuang dan overlay-nya menempel di halaman baru.
         */
        $this->assertStringContainsString('levelUpTimer', $html);
        $this->assertStringContainsString('clearTimeout(levelUpTimer)', $html);
        $this->assertStringContainsString("addEventListener('pagehide'", $html);
    }

    public function test_the_backdrop_is_not_dark_before_the_card_is_visible(): void
    {
        /*
         * Kartu memakai animasi `pop-in` yang mulai dari opacity 0. Kalau latar
         * sudah gelap sejak overlay dipasang, ada ~100ms layar gelap kosong —
         * persis gejala "halaman redup tanpa alasan".
         */
        $js = file_get_contents(resource_path('js/app.js'));

        // Overlay dibuat TANPA bg-ink di className...
        $this->assertMatchesRegularExpression(
            '/overlay\.className\s*=\s*[\'"][^\'"]*fixed inset-0 z-\[10000\][^\'"]*[\'"]/s',
            $js,
        );

        $this->assertDoesNotMatchRegularExpression(
            '/overlay\.className\s*=\s*[\'"][^\'"]*bg-ink\/70[^\'"]*[\'"]/s',
            $js,
            'Overlay tidak boleh langsung diberi latar gelap di className.',
        );

        // ...lalu digelapkan setelah kartu terpasang.
        $this->assertStringContainsString("overlay.style.backgroundColor = 'transparent'", $js);
        $this->assertStringContainsString('requestAnimationFrame', $js);
    }

    public function test_the_overlay_still_unlocks_page_scrolling_when_closed(): void
    {
        /*
         * Overlay mengunci `body.style.overflow` saat terbuka. Kalau kunci tidak
         * dilepas — mis. karena halaman ditinggal — seluruh halaman tujuan
         * tidak bisa digulir, dan itu terlihat seperti aplikasi membeku.
         */
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("document.body.style.overflow = 'hidden'", $js);
        $this->assertStringContainsString("document.body.style.overflow = ''", $js);
    }

    public function test_the_mission_map_has_no_overlay_drawn_over_it(): void
    {
        /*
         * Peta misi tidak boleh memuat modal apa pun. Kalau suatu saat ada yang
         * meng-include komponen modal di sini, halaman akan tampil gelap sejak
         * render pertama dan tes ini yang menangkapnya lebih dulu.
         */
        $student = User::query()->where('role', UserRole::Student)->firstOrFail();

        $html = $this->actingAs($student)->get('/misi')->assertOk()->getContent();

        $this->assertStringNotContainsString('levelup-overlay', $html);
        $this->assertStringNotContainsString('bg-ink/70', $html);
    }
}