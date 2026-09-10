<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BF04 — vínculo entre natureza de atendimento (categorização real e ativa
// no app hoje, ver CLAUDE.md/cronograma) e o modelo do Configurador de
// setor Assistência. O documento de requisitos cita "tipos_atendimentos",
// mas essa tabela está órfã no código (sem controller/rotas, resquício
// pré-migração sbadmin) — naturezas_atendimentos é quem efetivamente
// categoriza atendimentos em toda a aplicação. Convive com a coluna já
// existente nat_aten_mod_relatorio_id (conceito antigo, de flags).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('naturezas_atendimentos', function (Blueprint $table) {
            $table->integer('nat_aten_config_modelo_id')->nullable()->after('nat_aten_mod_relatorio_id');

            $table->index('nat_aten_config_modelo_id', 'fk_nat_aten_config_modelo_id_idx');
            $table->foreign('nat_aten_config_modelo_id', 'fk_nat_aten_config_modelo_id')
                ->references('cfg_mod_id')->on('config_modelos')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('naturezas_atendimentos', function (Blueprint $table) {
            $table->dropForeign('fk_nat_aten_config_modelo_id');
            $table->dropColumn('nat_aten_config_modelo_id');
        });
    }
};
