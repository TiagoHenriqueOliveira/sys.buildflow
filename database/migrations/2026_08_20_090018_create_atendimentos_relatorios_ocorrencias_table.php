<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios_ocorrencias', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('aten_rel_ocor_id')->autoIncrement();
            $table->integer('aten_rel_ocor_relatorio_id');
            $table->integer('aten_rel_ocor_ocorrencia_id');
            $table->string('aten_rel_ocor_observacao', 255)->nullable();

            $table->unique(
                ['aten_rel_ocor_relatorio_id', 'aten_rel_ocor_ocorrencia_id'],
                'uq_aten_rel_ocor_relatorio_ocorrencia'
            );
            $table->index('aten_rel_ocor_relatorio_id', 'fk_aten_rel_ocor_relatorio_id');
            $table->index('aten_rel_ocor_ocorrencia_id', 'fk_aten_rel_ocor_ocorrencia_id');

            // RESTRICT (não cascade) — não pode excluir uma ocorrência do
            // catálogo enquanto ela estiver referenciada num relatório.
            $table->foreign('aten_rel_ocor_ocorrencia_id', 'fk_aten_rel_ocor_ocorrencia_id')
                ->references('ocor_id')->on('ocorrencias')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('aten_rel_ocor_relatorio_id', 'fk_aten_rel_ocor_relatorio_id')
                ->references('aten_rel_id')->on('atendimentos_relatorios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_relatorios_ocorrencias');
    }
};
