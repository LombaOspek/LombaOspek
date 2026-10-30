<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case LiaisonOfficer = 'lo';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Mahasiswa Baru',
            self::LiaisonOfficer => 'LO (Kakak Tingkat)',
            self::Admin => 'Superadmin',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Student => 'bg-teal/20 text-teal-deep',
            self::LiaisonOfficer => 'bg-vermilion/15 text-vermilion-deep',
            self::Admin => 'bg-amber/40 text-amber-deep',
        };
    }

    /**
     * LO dan admin sama-sama boleh membalas tiket bantuan.
     */
    public function canReviewTickets(): bool
    {
        return $this === self::LiaisonOfficer || $this === self::Admin;
    }

    /**
     * Hanya LO yang ditugaskan pada satu gugus yang memverifikasi bukti misi
     * mahasiswa gugus tersebut. Admin dapat melihat semuanya.
     */
    public function canVerifySubmissions(): bool
    {
        return $this === self::LiaisonOfficer || $this === self::Admin;
    }

    public function canManageAccounts(): bool
    {
        return $this === self::Admin;
    }

    public function canBuildQuests(): bool
    {
        return $this === self::Admin;
    }
}