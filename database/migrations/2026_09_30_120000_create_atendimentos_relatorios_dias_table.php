<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RF012 — hotfix MCL Vale: horário e clima passam a ser lançados por dia
// (em vez de uma única linha por relatório em atendimentos_relatorios_horarios
// / atendimentos_relatorios_condicoes_climaticas). Tipos/charset replicam as
// tabelas irmãs (utf8mb3_unicode_ci), mesmo padrão de
// 2026_08_20_100000_create_atendimentos_relatorios_descricao_itens_table.
// Clima usa os valores de App\Enums\CondicaoClimatica (1/2/3), não os 0/1/2
// do comentário legado em atendimentos_relatorios_condicoes_climaticas (esse
// comentário está desatualizado; o código sempre usou 1/2/3).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios_dias', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            // autoIncrement() já define esta coluna como PRIMARY KEY.
            $table->integer('aten_rel_dia_id')->autoIncrement();

            $table->integer('aten_rel_dia_relatorio_id');
            $table->date('aten_rel_dia_data');
            $table->time('aten_rel_dia_hora_entrada')->nullable();
            $table->time('aten_rel_dia_hora_inicio_intervalo')->nullable();
            $table->time('aten_rel_dia_hora_fim_intervalo')->nullable();
            $table->time('aten_rel_dia_hora_saida')->nullable();
            $table->tinyInteger('aten_rel_dia_clima_manha')->nullable();
            $table->tinyInteger('aten_rel_dia_clima_tarde')->nullable();
            $table->tinyInteger('aten_rel_dia_clima_noite')->nullable();
            $table->dateTime('aten_rel_dia_criado_em')->useCurrent();

            $table->unique(
                ['aten_rel_dia_relatorio_id', 'aten_rel_dia_data'],
                'uq_aten_rel_dia_relatorio_data'
            );
            $table->index('aten_rel_dia_relatorio_id', 'fk_aten_rel_dia_relatorio_id_idx');
            $table->foreign('aten_rel_dia_relatorio_id', 'fk_aten_rel_dia_relatorio_id')
                ->references('aten_rel_id')->on('atendimentos_relatorios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_relatorios_dias');
    }
};
