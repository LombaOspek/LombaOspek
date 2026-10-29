<?php

namespace App\Enums;

enum QuestActionType: string
{
    case Checkin = 'checkin';
    case Quiz = 'quiz';
    case Reflection = 'reflection';

    public function label(): string
    {
        return match ($this) {
            self::Checkin => 'Absensi Lokasi',
            self::Quiz => 'Kuis Interaktif',
            self::Reflection => 'Refleksi Tertulis',
        };
    }

    public function ctaLabel(): string
    {
        return match ($this) {
            self::Checkin => 'Konfirmasi Kehadiran',
            self::Quiz => 'Kerjakan Kuis',
            self::Reflection => 'Kirim Refleksi',
        };
    }
}
