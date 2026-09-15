<?php

namespace App\Http\Controllers\Api\Fae;

use App\Http\Controllers\Controller;
use App\Models\ClassificacaoCliente;
use App\Models\Ocorrencia;
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
}