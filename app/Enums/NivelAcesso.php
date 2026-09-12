<?php

namespace App\Enums;

enum NivelAcesso: int
{
    case Administrador = 0;
    case Tecnico = 1;
    case Comercial = 2;
    // Pedido do cliente (2026-09-11): dois niveis novos, regras de acesso
    // ainda nao definidas (ficam para depois da aprovacao das telas) —
    // Assistencia sera uma especie de administrador do setor tecnico
    // (mais acesso que Tecnico); Vendedor sera o operacional de campo do
    // comercial (menos acesso que Comercial, que vira administrador do
    // seu setor). Por enquanto so existem como opcao selecionavel.
    case Assistencia = 3;
    case Vendedor = 4;

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Tecnico => 'Técnico',
            self::Comercial => 'Comercial',
            self::Assistencia => 'Assistência',
            self::Vendedor => 'Vendedor',
        };
    }
}
