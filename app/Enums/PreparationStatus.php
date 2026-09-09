<?php

declare(strict_types=1);

namespace App\Enums;

enum PreparationStatus: string
{
    case Pending = 'PEN';
    case Ready = 'REA';
    case Completed = 'COM';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Ready => 'Listo',
            self::Completed => 'Completada',
        };
    }
}
