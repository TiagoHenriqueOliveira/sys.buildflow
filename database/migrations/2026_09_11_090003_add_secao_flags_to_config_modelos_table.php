<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sessao 08 - Configurador substitui modelos_relatorios (ver CLAUDE.md e
// docs/cronograma/06-web-crm-telas.md/08-web-atendimento-telas.md): estas
// flags replicam exatamente as de modelos_relatorios (mod_rel_horarios,
// mod_rel_cond_clima, mod_rel_servicos_prestados, mod_rel_pecas_substituidas,
// mod_rel_ocorrencia, mod_rel_informacoes_adicionais), agora vivendo em
// config_modelos (setor Assistencia) em vez da tabela legada. Nao ha flag
// para "Descricao": a antiga aba de itens texto+foto livre e substituida
// pelas perguntas dinamicas do proprio modelo (NC02/NC03), que ja cobrem
// esse uso. Default true (diferente dos defaults mistos da tabela legada)
// porque aqui e o valor para um modelo NOVO criado do zero - a migracao de
// backfill seguinte copia o valor real de cada modelo legado existente.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config_modelos', function (Blueprint $table) {
            $table->boolean('cfg_mod_usa_horarios')->default(true)->after('cfg_mod_ativo');
            $table->boolean('cfg_mod_usa_clima')->default(true)->after('cfg_mod_usa_horarios');
            $table->boolean('cfg_mod_usa_servicos')->default(true)->after('cfg_mod_usa_clima');
            $table->boolean('cfg_mod_usa_pecas')->default(true)->after('cfg_mod_usa_servicos');
            $table->boolean('cfg_mod_usa_ocorrencias')->default(true)->after('cfg_mod_usa_pecas');
            $table->boolean('cfg_mod_usa_observacoes')->default(true)->after('cfg_mod_usa_ocorrencias');
        });
    }

    public function down(): void
    {
        Schema::table('config_modelos', function (Blueprint $table) {
            $table->dropColumn([
                'cfg_mod_usa_horarios',
                'cfg_mod_usa_clima',
                'cfg_mod_usa_servicos',
                'cfg_mod_usa_pecas',
                'cfg_mod_usa_ocorrencias',
                'cfg_mod_usa_observacoes',
            ]);
        });
    }
};