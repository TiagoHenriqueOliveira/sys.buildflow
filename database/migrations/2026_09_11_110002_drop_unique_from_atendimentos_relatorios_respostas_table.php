<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-11): perguntas repetiveis (cfg_perg_repetivel)
// precisam de N respostas por (relatorio, pergunta) - a constraint unica
// original (sessao 04, quando toda pergunta tinha resposta unica) nao
// serve mais. Perguntas NAO repetiveis continuam com no maximo 1 resposta,
// mas isso agora e regra de aplicacao (AtendimentosRelatoriosController::
// storeResposta), nao mais constraint de banco.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atendimentos_relatorios_respostas', function (Blueprint $table) {
            $table->dropUnique('atendimentos_relatorios_respostas_unica');
        });
    }

    public function down(): void
    {
        Schema::table('atendimentos_relatorios_respostas', function (Blueprint $table) {
            $table->unique(
                ['aten_rel_resp_relatorio_id', 'aten_rel_resp_pergunta_id'],
                'atendimentos_relatorios_respostas_unica'
            );
        });
    }
};