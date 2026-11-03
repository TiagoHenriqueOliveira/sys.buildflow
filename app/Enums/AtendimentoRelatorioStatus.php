<?php

namespace App\Enums;

use App\Models\Usuario;

enum AtendimentoRelatorioStatus: int
{
    case Preenchendo = 0;
    case Revisar     = 1;
    case Aprovado    = 2;
    // Pedido do cliente (2026-11-03): o técnico deixa o relatório pronto
    // ("liberado") e só a Assistência (ou o Administrador) aprova. Valor 3
    // (e não reordenado) pra não mudar o significado dos status já gravados.
    case Liberado    = 3;

    public function label(): string
    {
        return match ($this) {
            self::Preenchendo => 'Preenchendo',
            self::Revisar     => 'Revisar',
            self::Aprovado    => 'Aprovado',
            self::Liberado    => 'Liberado para aprovação',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Preenchendo => 'badge-info',
            self::Revisar     => 'badge-warning',
            self::Aprovado    => 'badge-success',
            self::Liberado    => 'badge-primary',
        };
    }

    /** Tipo do <x-sbadmin::badge> / sufixo de btn-outline-* na web. */
    public function badgeTipo(): string
    {
        return match ($this) {
            // Violeta: neutral parecia "sem cor" e o azul se confundia com o
            // layout (pedidos de 2026-11-03).
            self::Preenchendo => 'purple',
            self::Revisar     => 'warning',
            self::Aprovado    => 'success',
            self::Liberado    => 'info',
        };
    }

    /** Sufixo da classe do botão de status (.btn-status-*, ver _components.scss). */
    public function corBotao(): string
    {
        return match ($this) {
            self::Preenchendo => 'purple',
            self::Revisar     => 'warning',
            self::Aprovado    => 'success',
            self::Liberado    => 'info',
        };
    }

    /** Ordem de exibição na tela (fluxo do relatório, não o valor gravado). */
    public static function emOrdemDoFluxo(): array
    {
        return [self::Preenchendo, self::Revisar, self::Liberado, self::Aprovado];
    }

    /** Só Assistência e Administrador aprovam relatório (pedido 2026-11-03). */
    public static function usuarioPodeAprovar(?Usuario $usuario): bool
    {
        return $usuario !== null && in_array((int) $usuario->user_nivel_acesso, [
            NivelAcesso::Administrador->value,
            NivelAcesso::Assistencia->value,
        ], true);
    }

    /** Status que o usuário pode escolher na tela. */
    public static function permitidosPara(?Usuario $usuario): array
    {
        return array_values(array_filter(
            self::emOrdemDoFluxo(),
            fn (self $s) => $s !== self::Aprovado || self::usuarioPodeAprovar($usuario),
        ));
    }
}
