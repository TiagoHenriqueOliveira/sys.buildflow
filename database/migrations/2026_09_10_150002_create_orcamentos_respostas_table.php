<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Resposta de uma pergunta do Configurador (setor Comercial) dentro de um
// orçamento específico — mesmo conceito de atendimentos_relatorios_respostas
// (sessão 04), mas para orçamento, não relatório de atendimento.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos_respostas', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('orc_resp_id')->autoIncrement();
            $table->integer('orc_resp_orcamento_id');
            $table->integer('orc_resp_pergunta_id');
            $table->text('orc_resp_valor')->nullable();

            $table->unique(['orc_resp_orcamento_id', 'orc_resp_pergunta_id'], 'orcamentos_respostas_unica');
            $table->index('orc_resp_orcamento_id', 'fk_orc_resp_orcamento_id_idx');
            $table->index('orc_resp_pergunta_id', 'fk_orc_resp_pergunta_id_idx');

            $table->foreign('orc_resp_orcamento_id', 'fk_orc_resp_orcamento_id')
                ->references('orc_id')->on('orcamentos')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('orc_resp_pergunta_id', 'fk_orc_resp_pergunta_id')
                ->references('cfg_perg_id')->on('config_perguntas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos_respostas');
    }
};