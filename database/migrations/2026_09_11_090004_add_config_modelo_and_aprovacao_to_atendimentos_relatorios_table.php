<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sessao 08: liga o relatorio ao modelo do Configurador (substitui
// aten_rel_modelo_relatorio_id daqui pra frente, mas essa coluna legada
// NAO e removida - relatorios antigos continuam lendo dela). Tambem traz
// BF10 (aprovacao: aprovador/data/observacao do supervisor) e BF11
// (observacao interna no nivel do relatorio, nunca exibida no PDF
// assinado - ver pdf.blade.php).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atendimentos_relatorios', function (Blueprint $table) {
            $table->integer('aten_rel_config_modelo_id')->nullable()->after('aten_rel_modelo_relatorio_id');
            $table->integer('aten_rel_aprovado_por')->nullable()->after('aten_rel_status');
            $table->dateTime('aten_rel_aprovado_em')->nullable()->after('aten_rel_aprovado_por');
            $table->text('aten_rel_observacao_supervisor')->nullable()->after('aten_rel_aprovado_em');
            $table->text('aten_rel_observacao_interna')->nullable()->after('aten_rel_observacao_supervisor');

            $table->foreign('aten_rel_config_modelo_id', 'fk_aten_rel_config_modelo_id')
                ->references('cfg_mod_id')->on('config_modelos')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('aten_rel_aprovado_por', 'fk_aten_rel_aprovado_por')
                ->references('user_id')->on('usuarios')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('atendimentos_relatorios', function (Blueprint $table) {
            $table->dropForeign('fk_aten_rel_config_modelo_id');
            $table->dropForeign('fk_aten_rel_aprovado_por');
            $table->dropColumn([
                'aten_rel_config_modelo_id',
                'aten_rel_aprovado_por',
                'aten_rel_aprovado_em',
                'aten_rel_observacao_supervisor',
                'aten_rel_observacao_interna',
            ]);
        });
    }
};