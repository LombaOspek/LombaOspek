<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Tidak boleh ada kredensial plaintext yang tertinggal di storage/app.
 *
 * Insiden yang melatarbelakangi: `lo:generate` menulis
 * `storage/app/lo-credentials-<waktu>.csv` berisi NIM, nama, jurusan, dan
 * SANDI PLAINTEXT setiap kali dijalankan. Karena nama berkasnya memakai
 * timestamp, folder itu menumpuk tanpa batas - pada pemeriksaan 5 November
 * 2026 ada 75 berkas berisi ratusan sandi akun Liaison Officer.
 *
 * Berkas itu memang tidak pernah ter-commit (storage/app di-.gitignore), tetapi
 * tetap berbahaya: ikut tersalin saat backup/zip/deploy, dan sandi LO tidak
 * pernah kedaluwarsa sehingga kebocoran berlaku selamanya.
 */
class NoPlaintextCredentialsTest extends TestCase
{
    /** Pola nama berkas yang menandakan dump kredensial. */
    private const SUSPICIOUS = [
        'lo-credentials-*',
        '*-credentials-*.csv',
        '*-password*.csv',
        '*-sandi*.csv',
        'credentials.csv',
    ];

    public function test_no_credential_dump_is_left_in_storage(): void
    {
        $found = [];

        foreach (self::SUSPICIOUS as $pattern) {
            foreach (File::glob(storage_path('app').'/'.$pattern) as $path) {
                $found[] = basename($path);
            }
        }

        $this->assertSame(
            [],
            $found,
            "Berkas kredensial ditemukan di storage/app: \n  ".implode("\n  ", $found)
            ."\nHapus berkas itu dan jalankan `lo:generate` tanpa opsi penyimpanan.",
        );
    }

    public function test_the_lo_command_does_not_write_credentials_by_default(): void
    {
        /*
         * Perintah boleh menulis CSV, tetapi hanya bila `--csv=<path>` diminta
         * secara eksplisit. Kalau default-nya kembali menulis ke storage,
         * insiden yang sama akan terulang dalam hitungan jam.
         */
        $source = file_get_contents(app_path('Console/Commands/GenerateLoCommand.php'));

        $this->assertStringNotContainsString(
            "storage_path('app/lo-credentials-",
            $source,
            'GenerateLoCommand kembali menulis kredensial ke storage/app.',
        );

        $this->assertStringContainsString(
            "--csv=",
            $source,
            'Opsi --csv hilang; panitia tidak punya cara sah menyimpan kredensial.',
        );
    }

    public function test_storage_app_holds_only_expected_directories(): void
    {
        /*
         * storage/app adalah folder DATA: unggahan pengguna dan berkas kerja.
         * Skrip PHP, galeri HTML, atau dump CSV bukan isinya - semuanya pernah
         * ditemukan di sini (finish-all.php, gallery2.php, icon-gallery.html,
         * 74 CSV import sisa).
         */
        $allowed = ['private', 'public', 'imports', '.gitignore'];

        $unexpected = [];
        foreach (File::directories(storage_path('app')) as $dir) {
            $name = basename($dir);
            if (! in_array($name, $allowed, true)) {
                $unexpected[] = $name.'/';
            }
        }
        foreach (File::files(storage_path('app')) as $file) {
            $name = basename($file);
            if (! in_array($name, $allowed, true)) {
                $unexpected[] = $name;
            }
        }

        $this->assertSame(
            [],
            $unexpected,
            'Isi tak terduga di storage/app (folder ini hanya untuk data): '
            .implode(', ', $unexpected),
        );
    }

    public function test_no_handwritten_php_script_lives_under_storage_app(): void
    {
        /*
         * Skrip PHP di folder yang dapat ditulis aplikasi adalah risiko: kalau
         * web server salah dikonfigurasi, berkas itu dieksekusi sebagai kode.
         * Pernah ditemukan di sini: finish-all.php, gallery2.php,
         * sword-check.php, verify-csv-import.php.
         *
         * Dikecualikan `storage/framework/views` yang memang berisi hasil
         * kompilasi Blade dan `storage/framework/cache`; keduanya dikelola
         * Laravel, bukan ditulis tangan.
         */
        $scripts = [];
        foreach (File::allFiles(storage_path('app')) as $file) {
            if (in_array($file->getExtension(), ['php', 'phtml'], true)) {
                $scripts[] = str_replace(storage_path().'/', '', $file->getPathname());
            }
        }

        $this->assertSame(
            [],
            $scripts,
            'Skrip PHP tulisan tangan ditemukan di storage/app: '.implode(', ', $scripts),
        );
    }

    public function test_import_working_files_are_cleaned_up(): void
    {
        /*
         * Setiap import massal menyalin CSV ke storage/app/imports/<uuid>.csv.
         * Versi lama tidak pernah menghapusnya: 74 berkas (697 KB) berisi NIM,
         * nama, dan email mahasiswa menumpuk tanpa guna.
         */
        $source = file_get_contents(app_path('Http/Controllers/AccountController.php'));

        $this->assertStringContainsString(
            "@unlink(\$absolute)",
            $source,
            'AccountController tidak menghapus berkas kerja import setelah selesai.',
        );

        $pending = count(File::glob(storage_path('app/imports').'/*.csv'));
        $this->assertLessThanOrEqual(
            5,
            $pending,
            "Ada {$pending} berkas import tertinggal; seharusnya dibersihkan setelah import selesai.",
        );
    }
}