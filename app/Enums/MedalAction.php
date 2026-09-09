<?php

declare(strict_types=1);

namespace App\Enums;

enum MedalAction: string
{
    case Assigned = 'assigned';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Asignada',
            self::Removed => 'Removida',
        };
    }
}
