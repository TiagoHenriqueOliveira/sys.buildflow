<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-14): cada "outra localização" também aceita
// um link do Google Maps próprio, mesmo padrão da localização principal
// (cli_link_mapa) e do Roteiro de Viagem (crm_rot_link_mapa).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes_localizacoes', function (Blueprint $table) {
            $table->string('cli_loc_link_mapa', 500)->nullable()->after('cli_loc_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('clientes_localizacoes', function (Blueprint $table) {
            $table->dropColumn('cli_loc_link_mapa');
        });
    }
};