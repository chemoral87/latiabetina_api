<?php

declare(strict_types=1);

namespace App\Enums;

enum ChurchMemberStatus: string
{
    case Active = 'ACTIVO';
    case NoAnswer = 'NO CONTESTA';
    case DoNotDisturb = 'NO MOLESTAR';
    case Visitor = 'VISITA';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::NoAnswer => 'No Contesta',
            self::DoNotDisturb => 'No Molestar',
            self::Visitor => 'Visita',
        };
    }
}
