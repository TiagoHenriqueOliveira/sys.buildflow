<?php

namespace App\Http\Controllers;

use App\Enums\AtendimentoStatus;
use App\Models\Atendimento;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapaDemandasController extends Controller
{
    /**
     * BF08 - mapa de demandas por atendimento. Usa a geolocalizacao do
     * CLIENTE (BF01) como posicao do marcador - o atendimento em si nao tem
     * lat/lng proprio, acontece no endereco do cliente. A especificacao cita
     * 3 status ("Pendente de Start"/"Visita técnica agendada"/"Manutenção
     * agendada") que nao correspondem a nenhum campo existente - usamos os 4
     * status reais de AtendimentoStatus (unica fonte de verdade sobre o
     * andamento do atendimento hoje), rotulados como ja sao em todo o
     * sistema, em vez de inventar uma categorizacao paralela sem lastro no
     * schema.
     */
    public function index(Request $request): View
    {
        $filtroStatus = $request->get('f_status', '');
        $filtroNatureza = $request->get('f_natureza', '');
        $filtroTecnico = $request->get('f_tecnico', '');

        $usuario = $request->user();
        $idVisivel = Atendimento::idVisivelPara($usuario);

        $atendimentos = Atendimento::query()
            ->with(['cliente', 'natureza', 'usuario'])
            ->whereHas('cliente', fn ($q) => $q->whereNotNull('cli_latitude')->whereNotNull('cli_longitude'))
            ->when($idVisivel !== null, fn ($q) => $q->where('aten_usuario_id', $idVisivel))
            ->when($filtroStatus !== '', fn ($q) => $q->where('aten_status', (int) $filtroStatus))
            ->when($filtroNatureza !== '', fn ($q) => $q->where('aten_natureza_id', (int) $filtroNatureza))
            ->when($filtroTecnico !== '', fn ($q) => $q->where('aten_usuario_id', (int) $filtroTecnico))
            ->orderByDesc('aten_id')
            ->get();

        return view('mapa-demandas.index', [
            'atendimentos' => $atendimentos,
            'statusList' => AtendimentoStatus::cases(),
            'naturezas' => NaturezaAtendimento::where('nat_aten_ativo', 1)->orderBy('nat_aten_descricao')->get(),
            'tecnicos' => Usuario::where('user_ativo', 1)->orderBy('user_nome')->get(),
            'filtroStatus' => $filtroStatus,
            'filtroNatureza' => $filtroNatureza,
            'filtroTecnico' => $filtroTecnico,
        ]);
    }
}