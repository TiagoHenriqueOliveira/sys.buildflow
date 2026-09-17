<?php

namespace App\Enums;

enum ResultadoOrcamento: int
{
    case Convertido = 0;
    case NaoConvertido = 1;
    case Adiado = 2;
    case ProjetoFuturo = 3;

    public function label(): string
    {
        return match ($this) {
            self::Convertido => 'Convertido',
            self::NaoConvertido => 'Não convertido',
            self::Adiado => 'Adiado',
            self::ProjetoFuturo => 'Projeto futuro',
        };
    }

    /**
     * Tipo de badge do sbadmin (sbadmin-badge-*, ver
     * packages/sbadmin/resources/views/components/badge.blade.php).
     */
    public function badgeTipo(): string
    {
        return match ($this) {
            self::Convertido => 'success',
            self::NaoConvertido => 'error',
            self::Adiado => 'warning',
            self::ProjetoFuturo => 'info',
        };
    }
}