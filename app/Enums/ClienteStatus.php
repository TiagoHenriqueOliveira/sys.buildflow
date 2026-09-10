<?php

namespace App\Enums;

// Etapa 1 (telas): status só exibição, sem fluxo de aprovação ainda — ver
// docs/cronograma/02-web-nucleo-telas.md. O fluxo completo de pré-cadastro
// comercial com aprovação (NC01) é implementado na sessão de persistência.
enum ClienteStatus: int
{
    case PreCadastro = 0;
    case Aprovado = 1;

    public function label(): string
    {
        return match ($this) {
            self::PreCadastro => 'Pré-cadastro',
            self::Aprovado => 'Aprovado',
        };
    }

    public function badgeType(): string
    {
        return match ($this) {
            self::PreCadastro => 'warning',
            self::Aprovado => 'success',
        };
    }
}
