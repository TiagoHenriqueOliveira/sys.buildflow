<?php

namespace App\Enums;

enum AtendimentoRelatorioStatus: int
{
    case Preenchendo = 0;
    case Revisar     = 1;
    case Aprovado    = 2;

    public function label(): string
    {
        return match ($this) {
            self::Preenchendo => 'Preenchendo',
            self::Revisar     => 'Revisar',
            self::Aprovado    => 'Aprovado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Preenchendo => 'badge-info',
            self::Revisar     => 'badge-warning',
            self::Aprovado    => 'badge-success',
        };
    }
}
