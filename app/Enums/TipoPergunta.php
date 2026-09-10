<?php

namespace App\Enums;

enum TipoPergunta: int
{
    case MultiplaEscolha = 0;
    case EscolhaUnica = 1;
    case TextoLivre = 2;

    public function label(): string
    {
        return match ($this) {
            self::MultiplaEscolha => 'Múltipla escolha',
            self::EscolhaUnica => 'Escolha única',
            self::TextoLivre => 'Texto livre',
        };
    }

    public function temOpcoes(): bool
    {
        return $this !== self::TextoLivre;
    }
}
