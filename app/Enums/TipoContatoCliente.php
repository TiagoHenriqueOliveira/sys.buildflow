<?php

namespace App\Enums;

enum TipoContatoCliente: int
{
    case Tecnico = 0;
    case Comercial = 1;

    public function label(): string
    {
        return match ($this) {
            self::Tecnico => 'Técnico',
            self::Comercial => 'Comercial',
        };
    }
}
