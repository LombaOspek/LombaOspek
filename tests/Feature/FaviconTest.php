<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ikon tab (favicon).
 *
 * Sebelum ini `public/favicon.ico` berukuran **0 byte** dan tidak ada satu pun
 * tag <link rel="icon"> di layout. Browser lalu menampilkan ikon dokumen abu-abu
 * di tab, dan operator yang membuka banyak tab tidak bisa membedakan aplikasi
 * ini dari tab lain.
 */
class FaviconTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_icon_files_exist_and_are_not_empty(): void
    {
        /*
         * Berkas 0 byte tetap lolos `assertFileExists`. Itulah persis bug yang
         * terjadi sebelumnya: scaffold Laravel menyisakan favicon.ico kosong,
         * dan browser diam-diam mengabaikannya tanpa error apa pun.
         */
        $files = [
            'favicon.ico',
            'favicon-16.png',
            'favicon-32.png',
            'apple-touch-icon.png',
            'icon-192.png',
            'icon-512.png',
            'site.webmanifest',
        ];

        foreach ($files as $file) {
            $path = public_path($file);

            $this->assertFileExists($path, "Berkas {$file} tidak ada.");
            $this->assertGreaterThan(100, filesize($path), "Berkas {$file} kosong atau rusak.");
        }
    }

    public function test_the_ico_is_a_real_multi_size_icon(): void
    {
        /*
         * ICO menyimpan beberapa ukuran sekaligus; browser memilih yang paling
         * dekat. Kalau hanya satu ukuran tersimpan, tab di layar HiDPI memakai
         * gambar yang diperbesar dan terlihat kabur.
         */
        $raw = file_get_contents(public_path('favicon.ico'));

        $header = unpack('vreserved/vtype/vcount', substr($raw, 0, 6));

        $this->assertSame(0, $header['reserved'], 'Berkas bukan ICO yang valid.');
        $this->assertSame(1, $header['type'], 'Tipe ICO harus 1 (ikon).');
        $this->assertGreaterThanOrEqual(3, $header['count'], 'ICO harus memuat beberapa ukuran.');

        // offset entri pertama setelah header 6 byte + 16 byte per entri
        $first = unpack(
            'Cwidth/Cheight/Ccolors/Creserved/vplanes/vbpp/Vsize/Voffset',
            substr($raw, 6, 16),
        );
        $this->assertSame(16, $first['width'], 'Entri terkecil seharusnya 16px.');
        $this->assertSame(32, $first['bpp'], 'Ikon harus 32-bit (RGBA).');
        $this->assertSame(6 + 16 * $header['count'], $first['offset'], 'Offset data ICO salah.');
    }

    public function test_layout_declares_the_icon_links(): void
    {
        $admin = User::query()->where('role', UserRole::Admin)->firstOrFail();

        $html = $this->actingAs($admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('rel="icon"', $html);
        $this->assertStringContainsString('favicon-32.png', $html);
        $this->assertStringContainsString('favicon-16.png', $html);
        $this->assertStringContainsString('favicon.ico', $html);
        $this->assertStringContainsString('apple-touch-icon', $html);
    }

    public function test_standalone_pages_also_declare_the_icon(): void
    {
        /*
         * Login, sertifikat, dan verifikasi publik TIDAK memakai layouts.app —
         * masing-masing menulis <head> sendiri. Halaman pertama yang dilihat
         * orang adalah login, jadi tab tanpa ikon di sana paling terlihat.
         */
        $login = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('favicon-32.png', $login, 'Halaman login belum punya favicon.');
        $this->assertStringContainsString('apple-touch-icon', $login);

        $verify = $this->get('/verifikasi')->assertOk()->getContent();
        $this->assertStringContainsString('favicon-32.png', $verify, 'Halaman verifikasi belum punya favicon.');
    }

    public function test_manifest_is_valid_and_points_at_existing_icons(): void
    {
        $raw = file_get_contents(public_path('site.webmanifest'));
        $manifest = json_decode($raw, true);

        $this->assertIsArray($manifest, 'site.webmanifest bukan JSON yang valid.');
        $this->assertSame('OspekQuest', $manifest['short_name']);
        $this->assertSame('/dashboard', $manifest['start_url']);
        $this->assertSame('#2758a3', $manifest['theme_color']);
        $this->assertNotEmpty($manifest['icons']);

        // Setiap ikon yang didaftarkan harus benar-benar ada, kalau tidak
        // Chrome menolak memasang PWA tanpa pesan yang jelas.
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(
                public_path(ltrim($icon['src'], '/')),
                "Ikon manifest {$icon['src']} tidak ada.",
            );
        }
    }
}