<?php

namespace App\Support;

/**
 * Lencana berbentuk perisai yang digambar sebagai SATU SVG.
 *
 * Kenapa tidak lagi memakai CSS `clip-path`:
 * percobaan pertama memotong kotak pembungkus yang juga memuat ikon, sehingga
 * bahu perisai yang miring ikut memotong bagian atas glyph (trofi kehilangan
 * cangkirnya, petir kehilangan ujungnya). Sekarang bentuk perisai dan ikon
 * adalah dua elemen terpisah di dalam satu viewBox: perisai sebagai garis
 * luar, ikon digeser dan diperkecil ke area aman di tengahnya. Tidak ada
 * masking, jadi tidak ada yang bisa terpotong.
 */
class Badge
{
    /**
     * Warna isi perisai, dipetakan dari kolom `achievements.badge_color`.
     *
     * @var array<string, array{fill: string, ring: string}>
     */
    private const TONES = [
        // Dipetakan ke palet WIRA UMK: biru #2758a3 & kuning #f7bc29.
        'brand' => ['fill' => '#c8d6ec', 'ring' => '#1c3f75'],
        'gold' => ['fill' => '#fbe6b4', 'ring' => '#8a5c00'],
        'success' => ['fill' => '#cfe4d8', 'ring' => '#14653f'],
        'vermilion' => ['fill' => '#f7ddd3', 'ring' => '#99300f'],
        'ink' => ['fill' => '#e7e3d7', 'ring' => '#17150f'],
        // Alias lama dipertahankan supaya data `badge_color` yang ada
        // (teal/amber/sky) tidak pecah.
        'teal' => ['fill' => '#c8d6ec', 'ring' => '#1c3f75'],
        'amber' => ['fill' => '#fbe6b4', 'ring' => '#8a5c00'],
        'sky' => ['fill' => '#dbe4f3', 'ring' => '#1c3f75'],
    ];

    /**
     * Outline perisai pada viewBox 48×48.
     *
     * Bentuknya khas lencana: puncak rata dengan sudut terpotong, sisi tegak,
     * lalu menyempit ke satu titik di bawah. Skala dibuat agar sisa ruang di
     * dalam (area aman ±19×19) cukup untuk ikon 24px yang diperkecil.
     */
    private const CREST = 'M24 3.6 42.4 8.4V25.6c0 9.9-8 16.4-18.4 19.4C13.6 42 5.6 35.5 5.6 25.6V8.4Z';

    /**
     * Render lencana lengkap.
     *
     * @param  string  $icon  Nama ikon pada App\Support\Icon
     * @param  string  $tone  Kunci pada TONES (biasanya dari badge_color)
     */
    public static function crest(
        string $icon,
        string $tone = 'teal',
        bool $unlocked = true,
        string $class = 'size-12',
        ?string $label = null,
    ): string {
        $palette = self::TONES[$tone] ?? self::TONES['brand'];

        $fill = $unlocked ? $palette['fill'] : '#eeeade';
        $ring = $unlocked ? '#17150f' : 'rgba(23, 21, 15, 0.38)';
        $glyph = $unlocked ? '#17150f' : 'rgba(23, 21, 15, 0.42)';
        $iconPath = Icon::path($icon);

        // Heredoc tidak meng-interpolasi pemanggilan statis seperti
        // `{self::CREST}`, jadi bentuk perisai harus disalin ke variabel dulu.
        $crest = self::CREST;

        $aria = $label !== null
            ? 'role="img" aria-label="'.e($label).'"'
            : 'aria-hidden="true"';

        // Ikon 24px diperkecil 0.72 lalu dipusatkan: 24*0.72 = 17.28,
        // diletakkan pada x/y 15.4 sehingga membentang 15.4–32.7 — berhenti
        // jauh sebelum lekukan perisai di bawah (y≈45).
        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" class="{$class}" {$aria}>
            <path d="{$crest}" fill="{$fill}" stroke="{$ring}" stroke-width="2.4" stroke-linejoin="round"/>
            <g transform="translate(15.4 15.4) scale(0.72)" fill="none" stroke="{$glyph}" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                {$iconPath}
            </g>
        </svg>
        SVG;
    }
}
