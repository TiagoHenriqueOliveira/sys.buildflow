<?php

namespace App\Support;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrato fae/v1 §6 (auditoria S-14): o PUT de cliente, orçamento e
 * roteiro manda "If-Match: <versao>". Versão diferente da gravada: 409
 * "conflito_versao" com a representação atual em "atual". Sem o cabeçalho:
 * aceito enquanto API_EXIGIR_IF_MATCH=false (senão 428
 * "versao_obrigatoria"). Chamado dentro da transação, com o registro
 * travado (lockForUpdate).
 */
final class ConcorrenciaApi
{
    /** null = pode gravar; senão, a resposta de recusa. */
    public static function recusa(Request $request, int $versaoAtual, Closure $representacaoAtual): ?JsonResponse
    {
        $cabecalho = trim((string) $request->header('If-Match'));

        if ($cabecalho === '') {
            return config('api_fae.exigir_if_match')
                ? response()->json(['message' => 'Envie o cabeçalho If-Match com a versão do registro.', 'codigo' => 'versao_obrigatoria'], 428)
                : null;
        }

        $enviada = trim(str_replace(['W/', '"'], '', $cabecalho));

        if (! ctype_digit($enviada) || (int) $enviada !== $versaoAtual) {
            return response()->json([
                'message' => 'Este registro foi alterado por outra pessoa. Confira a versão atual antes de gravar.',
                'codigo' => 'conflito_versao',
                'atual' => $representacaoAtual(),
            ], 409);
        }

        return null;
    }
}
