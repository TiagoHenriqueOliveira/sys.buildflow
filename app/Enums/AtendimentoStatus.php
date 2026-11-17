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

    /**
     * BF08 (2026-11-17): ordem do "mais pendente" para o "mais resolvido" —
     * define a cor do ponto do Mapa de Demandas quando vários atendimentos
     * caem no mesmo local (ver App\Support\PontosMapaDemandas).
     */
    public function prioridadeNoMapa(): int
    {
        return match ($this) {
            self::Paralisada  => 0,
            self::NaoIniciada => 1,
            self::EmAndamento => 2,
            self::Concluida   => 3,
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
