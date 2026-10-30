<?php

namespace App\Enums;

/**
 * Cara sistem membuktikan mahasiswa benar-benar mengerjakan misi.
 *
 * Dipakai untuk mencegah kecurangan: token fisik di stan, foto bukti yang
 * ditinjau LO, atau koordinat GPS yang divalidasi server.
 */
enum VerificationMethod: string
{
    case None = 'none';
    case Token = 'token';
    case Photo = 'photo';
    case Gps = 'gps';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Tanpa bukti',
            self::Token => 'Token Rahasia Stan',
            self::Photo => 'Unggah Bukti Foto',
            self::Gps => 'Deteksi Lokasi GPS',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::None => 'Mahasiswa cukup menandai selesai sendiri.',
            self::Token => 'Kode dari panitia di lokasi, sekali pakai per akun.',
            self::Photo => 'Bukti foto ditinjau Liaison Officer sebelum XP diberikan.',
            self::Gps => 'Koordinat divalidasi server terhadap radius lokasi.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::None => 'check',
            self::Token => 'key',
            self::Photo => 'camera',
            self::Gps => 'pin',
        };
    }

    /**
     * True bila XP baru diberikan setelah ada yang meninjau bukti.
     */
    public function requiresReview(): bool
    {
        return $this === self::Photo;
    }

    /**
     * True bila verifikasi bisa langsung selesai di tempat (server memutuskan).
     */
    public function isInstant(): bool
    {
        return $this === self::Token || $this === self::Gps;
    }
}