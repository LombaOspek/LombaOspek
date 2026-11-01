<?php

namespace App\Support;

use App\Enums\QuestActionType;
use App\Enums\VerificationMethod;

/**
 * Ilustrasi modul berukuran besar (96×96) untuk kartu misi dan detail modul.
 *
 * Kenapa dipisah dari App\Support\Icon:
 * - Icon adalah glyph 24×24 untuk UI (tombol, label, navigasi).
 * - Illustrasi di sini adalah gambar dekoratif 96×96 dengan beberapa bidang
 *   isi (fill) agar terbaca sebagai "adegan", bukan sekadar garis.
 *
 * Semua bentuk digambar pada grid 96px dengan ketebalan garis 3 agar konsisten
 * dengan gaya neo-brutalis (garis tebal, sudut membulat, tanpa gradien).
 * Palet mengikuti token WIRA: biru #2758a3, kuning #f7bc29, putih, dan ink.
 */
class QuestArt
{
    private const INK = '#17150f';
    private const BLUE = '#2758a3';
    private const BLUE_SOFT = '#c8d6ec';
    private const YELLOW = '#f7bc29';
    private const PAPER = '#fbf9f4';

    /**
     * Pilih ilustrasi berdasarkan jenis aksi modul, dengan verifikasi sebagai
     * cadangan bila aksi belum dipetakan.
     */
    public static function forQuest(\App\Models\Quest $quest): string
    {
        return self::for(
            $quest->action_type,
            $quest->verification_method,
            $quest->step_number,
        );
    }

    /**
     * @return string SVG lengkap (96×96) siap ditanam di Blade.
     */
    public static function for(
        ?QuestActionType $action = null,
        ?VerificationMethod $method = null,
        int $seed = 1,
    ): string {
        // Ilustrasi khusus per langkah: delapan modul harus terbaca berbeda,
        // bukan lima bentuk yang berulang. Bila langkah di luar 1–8, jatuh ke
        // pemetaan berdasarkan jenis aksi.
        $byStep = match ($seed) {
            1 => self::document(),   // Registrasi ulang & verifikasi berkas
            2 => self::library(),    // Jelajah perpustakaan
            3 => self::group(),      // Pertemuan kelompok & mentor
            4 => self::quiz(),       // Kuis SKS & etika akademik
            5 => self::building(),   // Apel di auditorium
            6 => self::booth($seed), // Stan MCC
            7 => self::project(),    // Proyek mini solusi digital
            8 => self::medal(),      // Penutupan & sertifikat
            default => null,
        };

        if ($byStep !== null) {
            return self::wrap($byStep);
        }

        $body = match ($action) {
            QuestActionType::PhotoProof => self::document(),
            QuestActionType::Token => self::booth($seed),
            QuestActionType::Reflection => self::group(),
            QuestActionType::Quiz => self::quiz(),
            QuestActionType::Geolocation => self::building(),
            QuestActionType::Checkin => self::ticket(),
            default => self::byMethod($method, $seed),
        };

        return self::wrap($body);
    }

    private static function byMethod(?VerificationMethod $method, int $seed): string
    {
        return match ($method) {
            VerificationMethod::Photo => self::document(),
            VerificationMethod::Token => self::booth($seed),
            VerificationMethod::Gps => self::building(),
            default => self::group(),
        };
    }

    private static function wrap(string $body): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96" fill="none" '
            .'stroke="'.self::INK.'" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" '
            .'aria-hidden="true" focusable="false">'.$body.'</svg>';
    }

    /**
     * Registrasi & verifikasi berkas: lembar dokumen dengan stempel sah.
     */
    private static function document(): string
    {
        return '<rect x="22" y="12" width="44" height="60" rx="4" fill="'.self::PAPER.'"/>'
            .'<path d="M33 26h22M33 36h22M33 46h13"/>'
            .'<circle cx="60" cy="64" r="15" fill="'.self::YELLOW.'"/>'
            .'<path d="m53 64 4.6 4.6L68 59"/>'
            .'<path d="M30 12h20l10 10v8"/>';
    }

    /**
     * Token / stan panitia: gerai dengan spanduk dan kode rahasia.
     */
    private static function booth(int $seed): string
    {
        // Papan kode di depan stan: dua baris "kode" yang berbeda per seed
        // supaya stan berbeda tidak tampak identik.
        $code = $seed % 2 === 0
            ? '<rect x="35" y="45" width="9" height="9" rx="1.5" fill="'.self::PAPER.'" stroke="none"/>'
              .'<path d="M48 49h12"/>'
            : '<rect x="35" y="45" width="9" height="9" rx="1.5" fill="'.self::PAPER.'" stroke="none"/>'
              .'<path d="M48 49h7M48 56h12"/>';

        return '<path d="M14 30h68l-7-14H21Z" fill="'.self::BLUE.'"/>'
            .'<path d="M18 30v50h60V30"/>'
            .'<path d="M18 44h60"/>'
            .'<rect x="30" y="42" width="36" height="24" rx="3" fill="'.self::YELLOW.'"/>'
            .$code
            .'<path d="M22 80h52"/>'
            .'<path d="M28 30v50M68 30v50"/>';
    }

    /**
     * Pertemuan kelompok / mentor: tiga sosok dalam lingkaran.
     */
    private static function group(): string
    {
        return '<circle cx="48" cy="34" r="11" fill="'.self::BLUE_SOFT.'"/>'
            .'<path d="M28 76c0-11 9-19 20-19s20 8 20 19"/>'
            .'<circle cx="24" cy="44" r="8" fill="'.self::YELLOW.'"/>'
            .'<circle cx="72" cy="44" r="8" fill="'.self::YELLOW.'"/>'
            .'<path d="M10 72c0-8 6-14 14-14M86 72c0-8-6-14-14-14"/>';
    }

    /**
     * Kuis: kartu soal dengan tanda tanya dan pilihan.
     */
    private static function quiz(): string
    {
        return '<rect x="16" y="18" width="64" height="60" rx="5" fill="'.self::PAPER.'"/>'
            .'<path d="M26 30h18"/>'
            .'<circle cx="30" cy="44" r="4" fill="'.self::BLUE.'"/>'
            .'<path d="M40 44h26"/>'
            .'<circle cx="30" cy="58" r="4" fill="'.self::YELLOW.'"/>'
            .'<path d="M40 58h20"/>'
            .'<path d="M56 26h14v14"/>';
    }

    /**
     * Penutupan / sertifikat: medali dengan pita.
     */
    private static function medal(): string
    {
        return '<path d="M34 14 42 40M62 14 54 40" stroke="'.self::BLUE.'" stroke-width="7"/>'
            .'<circle cx="48" cy="58" r="21" fill="'.self::YELLOW.'"/>'
            .'<circle cx="48" cy="58" r="13" fill="'.self::PAPER.'"/>'
            .'<path d="m42 58 4.4 4.4L56 54"/>'
            .'<path d="M32 14h12M52 14h12"/>';
    }

    /**
     * Proyek mini: papan tulis dengan diagram alur.
     */
    private static function project(): string
    {
        return '<rect x="12" y="20" width="72" height="48" rx="4" fill="'.self::PAPER.'"/>'
            .'<rect x="20" y="28" width="18" height="12" rx="2" fill="'.self::BLUE_SOFT.'"/>'
            .'<rect x="58" y="28" width="18" height="12" rx="2" fill="'.self::YELLOW.'"/>'
            .'<rect x="39" y="52" width="18" height="10" rx="2" fill="'.self::BLUE_SOFT.'"/>'
            .'<path d="M38 34h20M29 40v12h10M67 40v6h-10"/>'
            .'<path d="M48 68v8M38 76h20"/>';
    }

    /**
     * Perpustakaan / repositori: rak buku.
     */
    private static function library(): string
    {
        return '<rect x="16" y="18" width="64" height="60" rx="3" fill="'.self::PAPER.'"/>'
            .'<path d="M16 38h64M16 58h64"/>'
            .'<rect x="23" y="24" width="7" height="14" fill="'.self::BLUE.'"/>'
            .'<rect x="33" y="24" width="7" height="14" fill="'.self::YELLOW.'"/>'
            .'<rect x="43" y="24" width="7" height="14" fill="'.self::BLUE_SOFT.'"/>'
            .'<rect x="23" y="44" width="7" height="14" fill="'.self::YELLOW.'"/>'
            .'<rect x="33" y="44" width="7" height="14" fill="'.self::BLUE_SOFT.'"/>'
            .'<path d="M56 44h16M56 50h16M56 64h12"/>';
    }

    /**
     * Lokasi / auditorium: gedung dengan kolom dan pin.
     */
    private static function building(): string
    {
        return '<path d="M20 38 48 18l28 20Z" fill="'.self::BLUE.'"/>'
            .'<path d="M24 38v34M36 38v34M48 38v34M60 38v34M72 38v34"/>'
            .'<path d="M18 72h60M18 78h60"/>'
            .'<path d="M48 18v-6"/>'
            .'<circle cx="70" cy="60" r="13" fill="'.self::YELLOW.'"/>'
            .'<path d="M70 53v7l5 3"/>';
    }

    /**
     * Absensi / gelang masuk: tiket dengan sobekan.
     */
    private static function ticket(): string
    {
        return '<path d="M14 34h68v12a6 6 0 0 0 0 12v12H14V58a6 6 0 0 0 0-12Z" fill="'.self::BLUE_SOFT.'"/>'
            .'<path d="M48 36v8M48 52v8M48 68v0"/>'
            .'<path d="M26 48h12"/>'
            .'<circle cx="66" cy="48" r="7" fill="'.self::YELLOW.'"/>';
    }
}