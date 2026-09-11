<?php

use App\Enums\SetorModelo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Sessao 08 - backfill de dados: para cada modelos_relatorios (legado)
// ainda em uso por alguma natureza sem config_modelo vinculado, cria um
// config_modelos (setor Assistencia) equivalente, copiando as flags de
// secao 1:1, e liga a natureza a ele. Isso e o que de fato "substitui
// modelos_relatorios" (decisao confirmada em 2026-09-10, ver
// project_fae_bioenergia na memoria) sem quebrar nenhum relatorio ja
// existente: modelos_relatorios continua no banco (FK antiga preservada
// para leitura historica), so deixa de ser a fonte usada para relatorios
// NOVOS a partir desta migracao.
return new class extends Migration
{
    public function up(): void
    {
        $naturezasSemVinculo = DB::table('naturezas_atendimentos')
            ->whereNull('nat_aten_config_modelo_id')
            ->whereNotNull('nat_aten_mod_relatorio_id')
            ->get();

        $mapaModeloParaConfig = [];

        foreach ($naturezasSemVinculo as $natureza) {
            $modeloLegadoId = $natureza->nat_aten_mod_relatorio_id;

            if (!isset($mapaModeloParaConfig[$modeloLegadoId])) {
                $modeloLegado = DB::table('modelos_relatorios')->where('mod_rel_id', $modeloLegadoId)->first();

                if (!$modeloLegado) {
                    continue;
                }

                $mapaModeloParaConfig[$modeloLegadoId] = DB::table('config_modelos')->insertGetId([
                    'cfg_mod_nome' => $modeloLegado->mod_rel_descricao,
                    'cfg_mod_setor' => SetorModelo::Assistencia->value,
                    'cfg_mod_ativo' => $modeloLegado->mod_rel_ativo,
                    'cfg_mod_usa_horarios' => $modeloLegado->mod_rel_horarios,
                    'cfg_mod_usa_clima' => $modeloLegado->mod_rel_cond_clima,
                    'cfg_mod_usa_servicos' => $modeloLegado->mod_rel_servicos_prestados,
                    'cfg_mod_usa_pecas' => $modeloLegado->mod_rel_pecas_substituidas,
                    'cfg_mod_usa_ocorrencias' => $modeloLegado->mod_rel_ocorrencia,
                    'cfg_mod_usa_observacoes' => $modeloLegado->mod_rel_informacoes_adicionais,
                ]);
            }

            DB::table('naturezas_atendimentos')
                ->where('nat_aten_id', $natureza->nat_aten_id)
                ->update(['nat_aten_config_modelo_id' => $mapaModeloParaConfig[$modeloLegadoId]]);
        }
    }

    public function down(): void
    {
        // Backfill de dados - sem rollback automatico (não há como distinguir
        // com seguranca um config_modelos criado por esta migracao de um
        // criado manualmente depois pelo usuario via tela do Configurador).
    }
};