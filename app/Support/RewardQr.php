<?php

namespace App\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * QR untuk kode klaim Bursa Hadiah.
 *
 * Kenapa QR, padahal kode teks sudah ada:
 * kode klaim berbentuk `OQ-QRRF-Z6UT`. Membacakan atau mengetik 10 karakter itu
 * di depan meja panitia saat ratusan mahasiswa mengantre adalah sumber salah
 * ketik dan antrean macet. QR memindahkan beban itu ke kamera ponsel panitia.
 *
 * Kode teks TETAP ada dan tetap ditampilkan: ponsel bisa mati, kamera bisa
 * gagal fokus, dan panitia tetap harus bisa menyerahkan hadiah tanpa jaringan.
 * QR adalah jalan pintas, bukan pengganti.
 *
 * Warna memakai palet WIRA (tinta di atas kertas), bukan hitam-putih murni,
 * supaya terbaca sebagai bagian aplikasi. Kontras tinta/kertas 15,9:1 jauh di
 * atas ambang minimum QR (~3:1), jadi tidak mengurangi keterbacaan.
 */
class RewardQr
{
    /**
     * SVG, bukan PNG: tetap tajam di layar apa pun dan ukurannya jauh lebih
     * kecil. QR adalah bentuk geometris murni, jadi kompresi raster tidak
     * memberi keuntungan apa pun.
     */
    public static function svg(string $payload, int $size = 240): string
    {
        $fill = Fill::uniformColor(
            new Rgb(251, 249, 244), // --color-paper
            new Rgb(23, 21, 15),    // --color-ink
        );

        $renderer = new ImageRenderer(
            new RendererStyle($size, 1, null, null, $fill),
            new SvgImageBackEnd(),
        );

        return (new Writer($renderer))->writeString($payload);
    }

    /**
     * Data URI untuk dipakai langsung di atribut src tanpa permintaan HTTP
     * tambahan. Dipakai saat QR dicetak (sertifikat/lembar klaim).
     */
    public static function dataUri(string $payload, int $size = 240): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::svg($payload, $size));
    }

    /**
     * URL yang ditanam di QR.
     *
     * KENAPA TIDAK MEMAKAI route() SAJA:
     * `route()` membentuk URL absolut dari `APP_URL`. Pada pemeriksaan
     * 9 November 2026 `.env` berisi `APP_URL=http://localhost:8000` sementara
     * aplikasi benar-benar dilayani di `127.0.0.1:8123`. QR yang dihasilkan
     * berisi:
     *
     *     http://localhost:8000/klaim/OQ-QRRF-Z6UT   <- tidak ada yang mendengar
     *
     * Panitia memindai QR itu, browser membuka "site can't be reached".
     * Dilaporkan sebagai "QR rusak padahal baru digenerate", padahal isinya
     * benar — hanya menunjuk ke host yang salah. QR ini dibagikan ke ponsel
     * mahasiswa di jaringan kampus, jadi host yang salah mematikan seluruh
     * alur penyerahan.
     *
     * Jadi: pakai host dari request yang sedang berjalan. QR yang dicetak dari
     * laptop panitia di `http://10.0.0.5:8123` akan berisi alamat itu, dan
     * ponsel di jaringan yang sama bisa membukanya. `APP_URL` hanya cadangan
     * ketika benar-benar tidak ada request (seeder, perintah artisan).
     */
    public static function claimPayload(string $claimCode): string
    {
        $path = '/klaim/'.rawurlencode($claimCode);
        $fallback = rtrim((string) config('app.url'), '/').$path;

        if (! app()->bound('request')) {
            return $fallback;
        }

        $request = request();

        /*
         * Deteksi request HTTP nyata lewat HTTP_HOST, BUKAN lewat
         * runningInConsole(). Di PHPUnit `runningInConsole()` juga bernilai
         * true, sehingga guard itu membuat tes mengukur APP_URL alih-alih
         * perilaku yang dipakai di produksi. HTTP_HOST hanya terisi pada
         * request sungguhan.
         *
         * Juga BUKAN `$request->root()`: di dalam PHPUnit method itu tetap
         * mengembalikan APP_URL (`http://localhost:8000`) walaupun HTTP_HOST
         * sudah diatur, sehingga tes tidak bisa membuktikan apa pun tentang
         * perilaku di produksi. Menyusun host dari HTTP_HOST langsung membuat
         * jalur yang diuji identik dengan jalur yang dipakai server.
         */
        $host = $request->server->get('HTTP_HOST');

        if (! is_string($host) || $host === '') {
            $host = $request->server->get('SERVER_NAME');
        }

        if (! is_string($host) || $host === '') {
            return $fallback;
        }

        // Skema mengikuti request sungguhan (dan X-Forwarded-Proto di balik
        // reverse proxy); HTTP_HOST sudah memuat port bila bukan 80/443.
        $scheme = $request->getScheme() ?: 'http';

        return $scheme.'://'.$host.$path;
    }
}