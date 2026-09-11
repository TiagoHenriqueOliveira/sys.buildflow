<?php

namespace App\Enums;

// Pedido do cliente (2026-09-11): o campo "Ativo" do Roteiro de Viagem nao
// tinha uso real (so pintava a linha de vermelho na listagem, sem filtro
// nem outro efeito) — trocado por um status de viagem de verdade.
enum StatusRoteiroViagem: int
{
    case NaoIniciada = 0;
    case EmAndamento = 1;
    case Concluida = 2;
    case Cancelada = 3;

    public function label(): string
    {
        return match ($this) {
            self::NaoIniciada => 'Não iniciada',
            self::EmAndamento => 'Em andamento',
            self::Concluida => 'Concluída',
            self::Cancelada => 'Cancelada',
        };
    }

    public function badgeType(): string
    {
        return match ($this) {
            self::NaoIniciada => 'neutral',
            self::EmAndamento => 'info',
            self::Concluida => 'success',
            self::Cancelada => 'error',
        };
    }
}