<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios_horarios', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('aten_rel_hora_id')->autoIncrement();
            $table->integer('aten_rel_hora_relatorio_id');
            $table->time('aten_rel_hora_entrada');
            $table->time('aten_rel_hora_inicio_intervalo')->nullable();
            $table->time('aten_rel_hora_fim_intervalo')->nullable();
            $table->time('aten_rel_hora_saida');

            $table->unique('aten_rel_hora_relatorio_id', 'uq_aten_rel_hora_relatorio');
            $table->index('aten_rel_hora_relatorio_id', 'fk_aten_rel_hora_relatorio_id');

            $table->foreign('aten_rel_hora_relatorio_id', 'fk_aten_rel_hora_relatorio_id')
                ->references('aten_rel_id')->on('atendimentos_relatorios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_relatorios_horarios');
    }
};
