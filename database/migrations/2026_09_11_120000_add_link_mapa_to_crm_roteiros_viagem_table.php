<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-11): link do roteiro pronto no Google Maps
// (colado pelo vendedor, ex.: URL de "compartilhar rota"), para o app
// Android abrir direto no Maps em vez de refazer a rota campo a campo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_roteiros_viagem', function (Blueprint $table) {
            $table->string('crm_rot_link_mapa', 500)->nullable()->after('crm_rot_periodo_fim');
        });
    }

    public function down(): void
    {
        Schema::table('crm_roteiros_viagem', function (Blueprint $table) {
            $table->dropColumn('crm_rot_link_mapa');
        });
    }
};