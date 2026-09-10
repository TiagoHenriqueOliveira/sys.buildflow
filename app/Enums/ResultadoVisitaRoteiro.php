<?php

namespace App\Enums;

enum ResultadoVisitaRoteiro: int
{
    case Visitado = 0;
    case NaoRealizado = 1;
    case Reagendado = 2;

    public function label(): string
    {
        return match ($this) {
            self::Visitado => 'Visitado',
            self::NaoRealizado => 'Não realizado',
            self::Reagendado => 'Reagendado',
        };
    }

    public function badgeType(): string
    {
        return match ($this) {
            self::Visitado => 'success',
            self::NaoRealizado => 'error',
            self::Reagendado => 'warning',
        };
    }
}