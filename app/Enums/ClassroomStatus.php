<?php

namespace App\Enums;

enum ClassroomStatus: string
{
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativa',
            self::Archived => 'Arquivada',
        };
    }
}
