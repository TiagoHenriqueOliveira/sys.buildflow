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

    public function badgeClass(): string
    {
        return match ($this) {
            self::NaoIniciada => 'badge-secondary',
            self::Paralisada  => 'badge-warning',
            self::EmAndamento => 'badge-primary',
            self::Concluida   => 'badge-success',
        };
    }
}
