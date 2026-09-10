<?php

namespace App\Enums;

enum SetorModelo: int
{
    case Comercial = 0;
    case Assistencia = 1;

    public function label(): string
    {
        return match ($this) {
            self::Comercial => 'Comercial',
            self::Assistencia => 'Assistência',
        };
    }
}
