<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersisteFiltros;
use App\Enums\NivelAcesso;
use App\Models\Cliente;
use App\Models\ClassificacaoCliente;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapaRelacoesController extends Controller
{
    use PersisteFiltros;

    public function index(Request $request): View
    {
        $filtros = $this->filtrosPersistentes('mapa-relacoes', ['f_vendedor', 'f_estado', 'f_segmento', 'f_classificacao']);
        $filtroVendedor = $filtros['f_vendedor'];
        $filtroEstado = $filtros['f_estado'];
        $filtroSegmento = $filtros['f_segmento'];
        $filtroClassificacao = $filtros['f_classificacao'];

        $clientes = Cliente::query()
            ->with(['vendedor', 'classificacao'])
            ->whereNotNull('cli_latitude')
            ->whereNotNull('cli_longitude')
            ->when($filtroVendedor !== '', fn ($q) => $q->where('cli_vendedor_id', (int) $filtroVendedor))
            ->when($filtroEstado !== '', fn ($q) => $q->where('cli_uf', $filtroEstado))
            ->when($filtroSegmento !== '', fn ($q) => $q->where('cli_segmento', $filtroSegmento))
            ->when($filtroClassificacao !== '', fn ($q) => $q->where('cli_classificacao_id', (int) $filtroClassificacao))
            ->orderBy('cli_nome')
            ->get();

        return view('mapa-relacoes.index', [
            'clientes' => $clientes,
            'vendedores' => Usuario::where('user_nivel_acesso', NivelAcesso::Comercial->value)->where('user_ativo', 1)->orderBy('user_nome')->get(),
            'estados' => Cliente::query()->whereNotNull('cli_uf')->distinct()->orderBy('cli_uf')->pluck('cli_uf'),
            'segmentos' => Cliente::query()->whereNotNull('cli_segmento')->where('cli_segmento', '!=', '')->distinct()->orderBy('cli_segmento')->pluck('cli_segmento'),
            'classificacoes' => ClassificacaoCliente::orderBy('cla_cli_nome')->get(),
            'filtroVendedor' => $filtroVendedor,
            'filtroEstado' => $filtroEstado,
            'filtroSegmento' => $filtroSegmento,
            'filtroClassificacao' => $filtroClassificacao,
        ]);
    }
}