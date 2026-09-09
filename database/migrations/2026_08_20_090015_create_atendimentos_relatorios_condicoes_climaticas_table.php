<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios_condicoes_climaticas', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('aten_rel_clima_id')->autoIncrement();
            $table->integer('aten_rel_clima_relatorio_id');
            $table->integer('aten_rel_clima_periodo')
                ->comment('0 - Manhã | 1 - Tarde | 2 - Noite');
            $table->integer('aten_rel_clima_condicao')
                ->comment('0 - Claro | 1 - Nublado | 2 - Chuvoso');

            $table->unique(
                ['aten_rel_clima_relatorio_id', 'aten_rel_clima_periodo'],
                'uq_aten_rel_clima_relatorio_periodo'
            );
            $table->index('aten_rel_clima_relatorio_id', 'fk_aten_rel_clima_relatorio_id');

            $table->foreign('aten_rel_clima_relatorio_id', 'fk_aten_rel_clima_relatorio_id')
                ->references('aten_rel_id')->on('atendimentos_relatorios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_relatorios_condicoes_climaticas');
    }
};
