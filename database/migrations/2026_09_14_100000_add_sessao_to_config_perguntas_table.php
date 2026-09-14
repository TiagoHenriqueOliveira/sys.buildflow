<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-14): uma "pergunta" pode ser marcada como
// Sessão — nesse caso ela não é uma pergunta de verdade (sem tipo de
// resposta/opções), é um marcador que vira uma ABA no preenchimento do
// relatório, agrupando as perguntas cadastradas logo depois dela na ordem
// do modelo (até a próxima Sessão ou o fim da lista) — mesma ideia das
// "seções" de formulários tipo Google Forms.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config_perguntas', function (Blueprint $table) {
            $table->boolean('cfg_perg_e_sessao')->default(false)->after('cfg_perg_repetivel');
            $table->string('cfg_perg_sessao_nome', 100)->nullable()->after('cfg_perg_e_sessao');
        });
    }

    public function down(): void
    {
        Schema::table('config_perguntas', function (Blueprint $table) {
            $table->dropColumn(['cfg_perg_e_sessao', 'cfg_perg_sessao_nome']);
        });
    }
};