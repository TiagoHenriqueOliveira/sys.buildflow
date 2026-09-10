<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NC03 — resposta de uma pergunta do Configurador dentro de um relatório
// de atendimento específico. Um valor por pergunta por relatório: texto
// livre, ou os IDs (serializados) das opções marcadas em escolha
// única/múltipla.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios_respostas', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('aten_rel_resp_id')->autoIncrement();
            $table->integer('aten_rel_resp_relatorio_id');
            $table->integer('aten_rel_resp_pergunta_id');
            $table->text('aten_rel_resp_valor')->nullable();

            $table->unique(
                ['aten_rel_resp_relatorio_id', 'aten_rel_resp_pergunta_id'],
                'atendimentos_relatorios_respostas_unica'
            );
            $table->index('aten_rel_resp_relatorio_id', 'fk_aten_rel_resp_relatorio_id_idx');
            $table->index('aten_rel_resp_pergunta_id', 'fk_aten_rel_resp_pergunta_id_idx');

            $table->foreign('aten_rel_resp_relatorio_id', 'fk_aten_rel_resp_relatorio_id')
                ->references('aten_rel_id')->on('atendimentos_relatorios')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('aten_rel_resp_pergunta_id', 'fk_aten_rel_resp_pergunta_id')
                ->references('cfg_perg_id')->on('config_perguntas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_relatorios_respostas');
    }
};
