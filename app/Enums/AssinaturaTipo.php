<?php

namespace App\Enums;

enum AssinaturaTipo: string
{
    case Responsavel = 'responsavel';
    case Cliente     = 'cliente';

    public function label(): string
    {
        return match ($this) {
            self::Responsavel => 'Responsável',
            self::Cliente     => 'Cliente',
        };
    }
}
