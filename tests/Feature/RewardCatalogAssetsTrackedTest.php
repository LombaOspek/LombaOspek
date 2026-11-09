<?php

namespace Tests\Feature;

use App\Models\Reward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aset katalog harus ikut ter-deploy, bukan hanya ada di mesin pengembang.
 *
 * BUG YANG DIKUNCI
 * Pada pemeriksaan kesiapan produksi 10 November 2026, `git ls-files
 * storage/app/public/` hanya mengembalikan `.gitignore`. Kesembilan gambar
 * katalog tidak pernah masuk repo, karena `storage/app/public/.gitignore`
 * mengabaikan seluruh isi folder - aturan yang BENAR untuk unggahan pengguna,
 * tetapi salah untuk aset yang dikirim bersama aplikasi.
 *
 * Akibatnya di server produksi: `migrate --seed` membuat 9 hadiah dengan
 * `image_path` terisi, tetapi berkasnya tidak ada. Katalog tampil dengan
 * gambar kosong, dan tidak ada perintah artisan yang bisa memulihkannya karena
 * gambar dibuat manual dengan PIL, bukan dihasilkan seeder.
 *
 * Setiap tes yang hanya memeriksa `file_exists()` di mesin pengembang akan
 * lulus selamanya sambil produksi tetap rusak. Karena itu tes ini memeriksa
 * daftar berkas yang benar-benar dilacak git.
 */
class RewardCatalogAssetsTrackedTest extends TestCase
{
    use RefreshDatabase;

    /** Jalankan git dan kembalikan baris keluarannya. */
    private function git(string $args): array
    {
        $output = [];
        $code = 0;

        exec('git -C '.escapeshellarg(base_path()).' '.$args.' 2>&1', $output, $code);

        return $output;
    }

    public function test_the_catalog_images_are_committed_to_git(): void
    {
        $tracked = $this->git('ls-files storage/app/public/rewards');

        $jpg = array_values(array_filter(
            $tracked,
            fn (string $line) => str_ends_with(trim($line), '.jpg'),
        ));

        $this->assertNotEmpty(
            $jpg,
            'Tidak ada gambar katalog yang dilacak git. Di server produksi '
            .'katalog akan tampil tanpa gambar. Jalankan '
            .'`git add storage/app/public/rewards` - folder itu sengaja '
            .'dikecualikan dari pola abaikan di .gitignore.',
        );

        $this->assertCount(
            9,
            $jpg,
            'Jumlah gambar katalog yang dilacak git tidak sesuai (harus 9): '
            .implode(', ', array_map('basename', $jpg)),
        );
    }

    public function test_every_reward_image_is_tracked(): void
    {
        /*
         * Uji silang: setiap baris hadiah yang punya `image_path` harus punya
         * berkas yang dilacak git. Ini menangkap kasus "sembilan gambar ada,
         * tetapi satu di antaranya tidak dipakai / ada yang hilang".
         */
        $tracked = $this->git('ls-files storage/app/public');
        $trackedSet = array_map('trim', $tracked);

        $missing = [];

        foreach (Reward::query()->whereNotNull('image_path')->get() as $reward) {
            $expected = 'storage/app/public/'.$reward->image_path;

            if (! in_array($expected, $trackedSet, true)) {
                $missing[] = $reward->code.' -> '.$expected;
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Gambar hadiah tidak dilacak git (akan hilang di produksi):\n  "
            .implode("\n  ", $missing),
        );
    }

    public function test_the_gitignore_ignores_uploads_but_not_assets(): void
    {
        /*
         * Dua aturan harus hidup berdampingan:
         *   - unggahan pengguna (hadiah buatan admin) diabaikan
         *   - aset aplikasi (gambar katalog, logo) dilacak
         *
         * Memeriksa berkas .gitignore-nya langsung, karena inilah satu-satunya
         * tempat aturan itu dinyatakan.
         */
        $path = base_path('storage/app/public/.gitignore');

        $this->assertFileExists($path);
        $content = file_get_contents($path);

        $this->assertStringContainsString(
            '!/rewards/',
            $content,
            'Pengecualian untuk gambar katalog hilang dari '
            .'storage/app/public/.gitignore; aset akan berhenti ter-deploy.',
        );

        $this->assertStringContainsString(
            '!/brand/',
            $content,
            'Pengecualian untuk logo kampus hilang.',
        );

        // Pola `*` di awal harus tetap ada: unggahan pengguna tidak boleh masuk.
        $this->assertMatchesRegularExpression(
            '/^\*$/m',
            $content,
            'Pola abaikan `*` hilang; unggahan pengguna akan ikut ter-commit.',
        );
    }

    public function test_the_university_logo_is_available_as_an_asset(): void
    {
        /*
         * Logo dipakai gambar katalog (stiker, pin, lanyard, mug). Kalau ia
         * hilang, tidak ada yang rusak saat runtime - tetapi tidak ada cara
         * menghasilkan ulang gambar tanpa mengunduh ulang dari umk.ac.id.
         */
        $path = base_path('storage/app/public/brand/umk-logo.png');

        $this->assertFileExists($path);
        $this->assertGreaterThan(5000, filesize($path), 'Logo kampus tampak korup.');

        $info = getimagesize($path);
        $this->assertNotFalse($info, 'Logo kampus bukan berkas gambar yang valid.');
        $this->assertSame('image/png', $info['mime']);
    }
}