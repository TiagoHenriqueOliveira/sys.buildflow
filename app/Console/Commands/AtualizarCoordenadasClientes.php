<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\ClienteLocalizacao;
use App\Support\CoordenadasMapa;
use Illuminate\Console\Command;

/**
 * Preenche latitude/longitude a partir do link do Google Maps dos clientes
 * já cadastrados (ver App\Support\CoordenadasMapa). Necessário uma vez após
 * a implantação de 2026-11-17: cadastros salvos antes disso têm só o link,
 * e por isso não aparecem no Mapa de Demandas/Mapa de Relações.
 *
 * Por padrão só SIMULA (lista o que mudaria); grava com --gravar.
 */
class AtualizarCoordenadasClientes extends Command
{
    protected $signature = 'clientes:atualizar-coordenadas {--gravar : Grava as coordenadas no banco (sem isso, só simula)}';

    protected $description = 'Deriva latitude/longitude dos clientes a partir do link do Google Maps';

    public function handle(): int
    {
        $gravar = (bool) $this->option('gravar');
        $linhas = [];

        foreach (Cliente::whereNotNull('cli_link_mapa')->where('cli_link_mapa', '<>', '')->orderBy('cli_id')->get() as $cliente) {
            $c = CoordenadasMapa::doLink($cliente->cli_link_mapa);
            $mudou = $c !== null && ((float) $cliente->cli_latitude !== $c['lat'] || (float) $cliente->cli_longitude !== $c['lng']);
            $linhas[] = [
                'Cliente ' . $cliente->cli_id,
                $cliente->cli_nome,
                $cliente->temGeolocalizacao() ? $cliente->cli_latitude . ', ' . $cliente->cli_longitude : '-',
                $c ? $c['lat'] . ', ' . $c['lng'] : 'link sem coordenadas',
                $c === null ? 'mantido' : ($mudou ? 'atualiza' : 'igual'),
            ];
            if ($gravar && $mudou) {
                $cliente->forceFill(['cli_latitude' => $c['lat'], 'cli_longitude' => $c['lng']])->save();
            }
        }

        foreach (ClienteLocalizacao::with('cliente')->whereNotNull('cli_loc_link_mapa')->where('cli_loc_link_mapa', '<>', '')->orderBy('cli_loc_id')->get() as $loc) {
            $c = CoordenadasMapa::doLink($loc->cli_loc_link_mapa);
            $mudou = $c !== null && ((float) $loc->cli_loc_latitude !== $c['lat'] || (float) $loc->cli_loc_longitude !== $c['lng']);
            $linhas[] = [
                'Localização ' . $loc->cli_loc_id,
                optional($loc->cliente)->cli_nome . ' / ' . $loc->cli_loc_descricao,
                $loc->cli_loc_latitude !== null ? $loc->cli_loc_latitude . ', ' . $loc->cli_loc_longitude : '-',
                $c ? $c['lat'] . ', ' . $c['lng'] : 'link sem coordenadas',
                $c === null ? 'mantido' : ($mudou ? 'atualiza' : 'igual'),
            ];
            if ($gravar && $mudou) {
                $loc->forceFill(['cli_loc_latitude' => $c['lat'], 'cli_loc_longitude' => $c['lng']])->save();
            }
        }

        $this->table(['Registro', 'Nome', 'Coordenadas atuais', 'Coordenadas do link', 'Ação'], $linhas);
        $this->info($gravar ? 'Coordenadas gravadas.' : 'Simulação — nada foi gravado. Rode com --gravar para aplicar.');

        return self::SUCCESS;
    }
}
