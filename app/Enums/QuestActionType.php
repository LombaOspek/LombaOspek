<?php

namespace App\Enums;

enum QuestActionType: string
{
    case Checkin = 'checkin';
    case Quiz = 'quiz';
    case Reflection = 'reflection';
    case Token = 'token';
    case PhotoProof = 'photo_proof';
    case Geolocation = 'geolocation';

    public function label(): string
    {
        return match ($this) {
            self::Checkin => 'Absensi Lokasi',
            self::Quiz => 'Kuis Interaktif',
            self::Reflection => 'Refleksi Tertulis',
            self::Token => 'Token Rahasia',
            self::PhotoProof => 'Bukti Foto',
            self::Geolocation => 'Verifikasi GPS',
        };
    }

    public function ctaLabel(): string
    {
        return match ($this) {
            self::Checkin => 'Konfirmasi Kehadiran',
            self::Quiz => 'Kerjakan Kuis',
            self::Reflection => 'Kirim Refleksi',
            self::Token => 'Buka Verifikasi Token',
            self::PhotoProof => 'Unggah Bukti Foto',
            self::Geolocation => 'Buka Verifikasi Lokasi',
        };
    }

    /**
     * Metode pembuktian yang melekat pada tipe aksi ini. Dipakai seeder dan
     * Quest Builder agar kolom `verification_method` tidak pernah kosong.
     */
    public function defaultVerification(): VerificationMethod
    {
        return match ($this) {
            self::Token => VerificationMethod::Token,
            self::PhotoProof => VerificationMethod::Photo,
            self::Geolocation => VerificationMethod::Gps,
            default => VerificationMethod::None,
        };
    }
}