<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-17): orcamento ganha um campo de resultado
// (Convertido/Nao Convertido/Adiado/Projeto Futuro) para os indicadores
// comerciais (CRM08) deixarem de usar a taxa de conversao mockada.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->tinyInteger('orc_resultado')->nullable()->after('orc_prazo_envio');
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn('orc_resultado');
        });
    }
};