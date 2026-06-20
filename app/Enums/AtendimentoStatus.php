<?php

namespace App\Enums;

enum AtendimentoStatus: int
{
    case NaoIniciada = 0;
    case Paralisada  = 1;
    case EmAndamento = 2;
    case Concluida   = 3;

    public function label(): string
    {
        return match ($this) {
            self::NaoIniciada => 'Não iniciada',
            self::Paralisada  => 'Paralisada',
            self::EmAndamento => 'Em andamento',
            self::Concluida   => 'Concluída',
        };
    }
}
