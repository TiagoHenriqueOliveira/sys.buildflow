<?php

namespace App\Http\Controllers\Api\Fae;

use App\Http\Controllers\Controller;
use App\Enums\NivelAcesso;
use App\Models\ClassificacaoCliente;
use App\Models\CrmTipoOrcamento;
use App\Models\Ocorrencia;
use App\Models\Segmento;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;

class CatalogoController extends Controller
{
    public function ocorrencias(): JsonResponse
    {
        $rows = Ocorrencia::orderBy('ocor_descricao')->get()->map(fn($o) => [
            'id'        => $o->ocor_id,
            'descricao' => $o->ocor_descricao,
        ]);

        return response()->json(['data' => $rows]);
    }

    /**
     * NC01 - opcoes para o campo Classificacao do cadastro de Cliente.
     * Lista configuravel (pendencia #3 do cliente) - hoje pode estar vazia.
     */
    public function classificacoesCliente(): JsonResponse
    {
        $rows = ClassificacaoCliente::where('cla_cli_ativo', 1)
            ->orderBy('cla_cli_nome')
            ->get()
            ->map(fn ($c) => [
                'id'   => $c->cla_cli_id,
                'nome' => $c->cla_cli_nome,
            ]);

        return response()->json(['data' => $rows]);
    }

    /**
     * NC01 - opcoes para o campo Segmento do cadastro de Cliente (modulo
     * "Segmentos" em Configuracoes, pedido do cliente 2026-09-17 - nao
     * existia quando os outros endpoints de Cliente foram criados).
     */
    public function segmentos(): JsonResponse
    {
        $rows = Segmento::where('seg_ativo', 1)
            ->orderBy('seg_descricao')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->seg_id,
                'descricao' => $s->seg_descricao,
            ]);

        return response()->json(['data' => $rows]);
    }

    /**
     * CRM09 - lista de usuarios com perfil Comercial, usada nos dropdowns de
     * vendedor (Orcamento, Roteiro de Viagem) e no filtro de Mapa de
     * Relacoes.
     */
    public function vendedoresComerciais(): JsonResponse
    {
        $rows = Usuario::where('user_nivel_acesso', NivelAcesso::Comercial->value)
            ->where('user_ativo', 1)
            ->orderBy('user_nome')
            ->get()
            ->map(fn ($u) => ['id' => $u->user_id, 'nome' => $u->user_nome]);

        return response()->json(['data' => $rows]);
    }

    /**
     * CRM01/CRM09 - tipos de orcamento ativos, com as perguntas do modelo
     * vinculado (setor Comercial) ja incluidas - a tela de Orcamento usa
     * isso pra montar a aba "Perguntas" ao escolher o tipo.
     */
    public function tiposOrcamento(): JsonResponse
    {
        $rows = CrmTipoOrcamento::where('crm_tp_orc_ativo', 1)
            ->with('configModelo.perguntas.opcoes')
            ->orderBy('crm_tp_orc_nome')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->crm_tp_orc_id,
                'nome' => $t->crm_tp_orc_nome,
                'perguntas' => ($t->configModelo?->perguntas ?? collect())->map(fn ($p) => [
                    'id' => $p->cfg_perg_id,
                    'texto' => $p->cfg_perg_texto,
                    'tipo' => $p->cfg_perg_tipo->value,
                    'opcoes' => $p->opcoes->map(fn ($o) => ['id' => $o->cfg_perg_op_id, 'texto' => $o->cfg_perg_op_texto])->values(),
                ])->values(),
            ]);

        return response()->json(['data' => $rows]);
    }
}