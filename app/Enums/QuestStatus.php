<?php

namespace App\Enums;

enum QuestStatus: string
{
    case Locked = 'locked';
    case Available = 'available';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Locked => 'Terkunci',
            self::Available => 'Siap Dikerjakan',
            self::InProgress => 'Sedang Berjalan',
            self::Completed => 'Selesai',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Available || $this === self::InProgress;
    }
}
