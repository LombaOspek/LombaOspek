<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Menunggu Jawaban',
            self::Answered => 'Sudah Dijawab',
            self::Resolved => 'Selesai',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Open => 'bg-amber-200 text-amber-950',
            self::Answered => 'bg-sky-200 text-sky-950',
            self::Resolved => 'bg-emerald-200 text-emerald-950',
        };
    }
}
