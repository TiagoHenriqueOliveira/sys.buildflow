<?php

namespace App\Support;

use App\Models\ConfigPergunta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Auditoria 27/11/2026 (S-22) / contrato fae/v1 §8: as regras do
 * Configurador valem no servidor, não só na tela — mesma regra no web e na
 * API (storeResposta dos dois controllers):
 * - a pergunta precisa ser do modelo do relatório: 422 "pergunta_fora_do_modelo";
 * - foto só em pergunta com "permite anexo": 422 "anexo_nao_permitido".
 */
final class RegraRespostaFormulario
{
    public static function recusa(?ConfigPergunta $pergunta, Request $request): ?JsonResponse
    {
        if (! $pergunta) {
            return response()->json([
                'message' => 'Esta pergunta não faz parte do modelo deste relatório.',
                'codigo' => 'pergunta_fora_do_modelo',
                'errors' => ['pergunta_id' => ['Esta pergunta não faz parte do modelo deste relatório.']],
            ], 422);
        }

        if ($request->hasFile('foto') && ! $pergunta->cfg_perg_permite_anexo) {
            return response()->json([
                'message' => 'Esta pergunta não aceita foto.',
                'codigo' => 'anexo_nao_permitido',
                'errors' => ['foto' => ['Esta pergunta não aceita foto.']],
            ], 422);
        }

        return null;
    }
}
