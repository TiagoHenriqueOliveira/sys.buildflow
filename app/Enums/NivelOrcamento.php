<?php

namespace App\Enums;

enum NivelOrcamento: int
{
    case Simples = 0;
    case Medio = 1;
    case Complexo = 2;

    public function label(): string
    {
        return match ($this) {
            self::Simples => 'Simples',
            self::Medio => 'Médio',
            self::Complexo => 'Complexo',
        };
    }

    /**
     * CRM02 — prazo mockado nesta etapa (dias corridos a partir de hoje).
     * A fórmula real fica para a sessão de persistência (07).
     */
    public function prazoEmDiasMockado(): int
    {
        return match ($this) {
            self::Simples => 3,
            self::Medio => 7,
            self::Complexo => 15,
        };
    }
}