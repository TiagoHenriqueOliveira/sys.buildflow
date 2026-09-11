<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-11): algumas perguntas (ex.: "Descricao do
// servico realizado" com anexo) precisam ser respondidas VARIAS VEZES no
// mesmo relatorio (equivalente a repetir a antiga aba Descricao, mas por
// pergunta). Este flag, marcado no cadastro da pergunta, controla isso.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config_perguntas', function (Blueprint $table) {
            $table->boolean('cfg_perg_repetivel')->default(false)->after('cfg_perg_permite_anexo');
        });
    }

    public function down(): void
    {
        Schema::table('config_perguntas', function (Blueprint $table) {
            $table->dropColumn('cfg_perg_repetivel');
        });
    }
};