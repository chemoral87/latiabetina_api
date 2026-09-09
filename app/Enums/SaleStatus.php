<?php

declare(strict_types=1);

namespace App\Enums;

enum SaleStatus: string
{
    case Pending = 'PEN';
    case Preparing = 'PRE';
    case Completed = 'COM';
    case Cancelled = 'CAN';
    case Refunded = 'REF';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Preparing => 'Preparando',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
            self::Refunded => 'Reembolsada',
        };
    }
}
