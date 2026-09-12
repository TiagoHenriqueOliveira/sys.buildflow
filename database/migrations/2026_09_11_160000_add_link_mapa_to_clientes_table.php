<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-11): campo de link do Google Maps para a
// localizacao principal do cliente, mesmo padrao ja usado no Roteiro de
// Viagem (crm_rot_link_mapa) — nao substitui cli_latitude/cli_longitude
// (ainda usados pelo mapa de relacoes, CRM07), e um campo adicional.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('cli_link_mapa', 500)->nullable()->after('cli_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('cli_link_mapa');
        });
    }
};