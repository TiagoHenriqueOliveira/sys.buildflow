<?php

namespace App\Enums;

enum NivelAcesso: int
{
    case Administrador = 0;
    case Tecnico = 1;
    case Comercial = 2;

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Tecnico => 'Técnico',
            self::Comercial => 'Comercial',
        };
    }
}
