<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-11): remover o selo "Pre-cadastro/Aprovado"
// do cadastro de cliente — era so exibicao (default 1, nunca escrito pelo
// ClienteRepository) e nao tinha nenhum fluxo de aprovacao de verdade
// ainda (isso fica pra sessao de persistencia do NC01, quando fizer
// sentido reintroduzir um campo de status com regra real por tras).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('cli_status');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->tinyInteger('cli_status')->default(1)
                ->comment("0 - Pré-cadastro\n1 - Aprovado")
                ->after('cli_ativo');
        });
    }
};