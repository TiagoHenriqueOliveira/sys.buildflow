<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-11): remove o checklist de secoes do
// checklist de modelo - Dados/Horarios/Anexos/Observacoes Gerais/
// Assinatura ficam SEMPRE fixos; Clima/Servicos/Pecas/Ocorrencias viram
// puramente perguntas do Configurador (sem toggle por modelo). Ver
// migration 2026_09_11_090003 que criou essas colunas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config_modelos', function (Blueprint $table) {
            $table->dropColumn([
                'cfg_mod_usa_horarios',
                'cfg_mod_usa_clima',
                'cfg_mod_usa_servicos',
                'cfg_mod_usa_pecas',
                'cfg_mod_usa_ocorrencias',
                'cfg_mod_usa_observacoes',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('config_modelos', function (Blueprint $table) {
            $table->boolean('cfg_mod_usa_horarios')->default(true)->after('cfg_mod_ativo');
            $table->boolean('cfg_mod_usa_clima')->default(true)->after('cfg_mod_usa_horarios');
            $table->boolean('cfg_mod_usa_servicos')->default(true)->after('cfg_mod_usa_clima');
            $table->boolean('cfg_mod_usa_pecas')->default(true)->after('cfg_mod_usa_servicos');
            $table->boolean('cfg_mod_usa_ocorrencias')->default(true)->after('cfg_mod_usa_pecas');
            $table->boolean('cfg_mod_usa_observacoes')->default(true)->after('cfg_mod_usa_ocorrencias');
        });
    }
};