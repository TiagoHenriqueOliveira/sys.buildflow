<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-14): vinculo explicito pergunta -> sessao,
// substituindo o agrupamento so por ordem/adjacencia na lista "Perguntas do
// modelo" (na pratica, dificil de usar - nao dava pra reordenar uma
// pergunta pra dentro de uma sessao sem mexer em tudo que vinha no meio).
// Ver ConfigModelo::perguntasAgrupadasPorSessao().
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config_modelos_perguntas', function (Blueprint $table) {
            $table->integer('cfg_mod_perg_sessao_id')->nullable()->after('cfg_mod_perg_ordem');
            $table->foreign('cfg_mod_perg_sessao_id', 'fk_cfg_mod_perg_sessao_id')
                ->references('cfg_perg_id')->on('config_perguntas')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('config_modelos_perguntas', function (Blueprint $table) {
            $table->dropForeign('fk_cfg_mod_perg_sessao_id');
            $table->dropColumn('cfg_mod_perg_sessao_id');
        });
    }
};