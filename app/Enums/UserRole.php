<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Mentor = 'mentor';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Mahasiswa Baru',
            self::Mentor => 'Mentor Orientasi',
            self::Admin => 'Administrator',
        };
    }

    public function canReviewTickets(): bool
    {
        return $this === self::Mentor || $this === self::Admin;
    }
}
