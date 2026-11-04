<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Scrollbar sidebar.
 *
 * Sidebar adalah satu-satunya area yang isinya bisa melebihi tinggi layar
 * (menu mahasiswa + admin + akun). Tanpa gaya sendiri, ia mewarisi scrollbar
 * abu-abu membulat default yang tidak cocok dengan neo-brutalism.
 */
class SidebarScrollbarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_sidebar_uses_the_custom_scroll_container(): void
    {
        $admin = User::query()->where('role', UserRole::Admin)->firstOrFail();

        $html = $this->actingAs($admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('sidebar-scroll', $html);
        $this->assertStringContainsString('id="sidebar"', $html);

        // Kelasnya harus menempel pada <aside id="sidebar">, bukan elemen lain:
        // kalau tertukar, sidebar tetap memakai scrollbar default.
        $this->assertMatchesRegularExpression(
            '/id="sidebar"[\s\S]{0,400}sidebar-scroll|sidebar-scroll[\s\S]{0,400}id="sidebar"/',
            $html,
        );
    }

    public function test_scrollbar_styles_are_built_into_the_bundle(): void
    {
        /*
         * Gaya ini ada di resources/css/app.css, tetapi yang dipakai browser
         * adalah hasil build. Kalau Vite belum dijalankan, kelasnya ada di DOM
         * tanpa aturan apa pun dan sidebar kembali ke scrollbar default.
         */
        $manifest = public_path('build/manifest.json');

        $this->assertFileExists($manifest, 'Manifest build tidak ada: jalankan npm run build.');

        /*
         * Manifest memuat LEBIH DARI SATU berkas CSS - yang pertama adalah
         * bundel font, bukan aplikasi. Mengambil yang pertama membuat tes gagal
         * pada berkas yang salah, jadi semua CSS digabung dulu.
         */
        $allCss = collect(json_decode(file_get_contents($manifest), true))
            ->pluck('file')
            ->filter(fn (string $f) => str_ends_with($f, '.css'))
            ->map(fn (string $f) => public_path('build/'.$f))
            ->filter(fn (string $path) => is_file($path))
            ->map(fn (string $path) => file_get_contents($path))
            ->implode("\n");

        $this->assertNotSame('', $allCss, 'Tidak ada berkas CSS di manifest build.');

        $this->assertStringContainsString(
            'sidebar-scroll',
            $allCss,
            'Aturan .sidebar-scroll belum masuk bundle: jalankan npm run build.',
        );
    }
}