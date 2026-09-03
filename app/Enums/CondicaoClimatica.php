<?php

namespace App\Enums;

enum CondicaoClimatica: int
{
    case Ensolarado = 1;
    case Nublado    = 2;
    case Chuvoso    = 3;

    public function label(): string
    {
        return match ($this) {
            self::Ensolarado => 'ensolarado',
            self::Nublado    => 'nublado',
            self::Chuvoso    => 'chuvoso',
        };
    }

    public static function fromLabel(string $label): self
    {
        return match ($label) {
            'ensolarado' => self::Ensolarado,
            'nublado'    => self::Nublado,
            'chuvoso'    => self::Chuvoso,
            default      => self::Ensolarado,
        };
    }
}
