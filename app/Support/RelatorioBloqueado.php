<?php

namespace App\Support;

use App\Enums\AtendimentoRelatorioStatus;
use App\Models\AtendimentoRelatorio;
use Illuminate\Http\JsonResponse;

/**
 * Auditoria 27/11/2026 (S-02, decisão D-03) / contrato fae/v1 §5: relatório
 * Aprovado é somente leitura no servidor, na web e na API. Depois de
 * Aprovado só continuam permitidos: a assinatura do cliente se ainda não
 * houver (uma vez), o compartilhamento/envio do PDF e a observação interna
 * (BF11). Com a assinatura do cliente, o relatório não volta mais de status.
 */
class RelatorioBloqueado
{
    public static function resposta(AtendimentoRelatorio $relatorio, ?string $mensagem = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $mensagem ?? 'Relatório aprovado: não pode mais ser alterado.',
            'codigo' => 'relatorio_bloqueado',
            'status_relatorio' => (int) $relatorio->aten_rel_status,
        ], 423);
    }

    public static function assinaturaJaRegistrada(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'A assinatura do cliente já foi registrada neste relatório aprovado.',
            'codigo' => 'assinatura_ja_registrada',
        ], 409);
    }

    public static function aprovado(AtendimentoRelatorio $relatorio): bool
    {
        return (int) $relatorio->aten_rel_status === AtendimentoRelatorioStatus::Aprovado->value;
    }

    /** Aprovado e já assinado pelo cliente: o status não muda mais (D-03). */
    public static function statusTravado(AtendimentoRelatorio $relatorio, int $novoStatus): bool
    {
        return self::aprovado($relatorio)
            && $novoStatus !== (int) $relatorio->aten_rel_status
            && $relatorio->assinaturaCliente() !== null;
    }
}
