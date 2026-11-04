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
     * Isi QR sengaja BUKAN kode klaim mentah, melainkan URL verifikasi.
     *
     * Alasannya: kalau panitia memindai QR kode lama yang sudah diserahkan,
     * mereka perlu tahu statusnya saat itu juga — bukan sekadar melihat teks.
     * URL ini membuka halaman yang menampilkan hadiah, status, dan pemiliknya.
     */
    public static function claimPayload(string $claimCode): string
    {
        return route('rewards.claim-verify', ['code' => $claimCode]);
    }
}