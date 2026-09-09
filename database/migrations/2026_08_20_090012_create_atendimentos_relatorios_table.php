<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
// aten_rel_atendimento_id tem DOIS índices distintos no dump original
// (fk_aten_rel_atendimento_id e idx_aten_rel_atendimento, redundantes
// entre si) — preservados ambos para bater exatamente com o schema atual.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('aten_rel_id')->autoIncrement();
            $table->integer('aten_rel_atendimento_id');
            $table->integer('aten_rel_modelo_relatorio_id');
            $table->integer('aten_rel_status')->default(0)
                ->comment('0 - preenchendo | 1 - revisar | 2 - aprovado');
            $table->longText('aten_rel_descricao')->nullable();
            $table->longText('aten_rel_informacoes_adicionais')->nullable();
            $table->date('aten_rel_dt_fim')->nullable();
            $table->date('aten_rel_data');

            $table->index('aten_rel_atendimento_id', 'fk_aten_rel_atendimento_id');
            $table->index('aten_rel_modelo_relatorio_id', 'fk_aten_rel_modelo_relatorio_id');
            $table->index('aten_rel_atendimento_id', 'idx_aten_rel_atendimento');

            $table->foreign('aten_rel_atendimento_id', 'fk_aten_rel_atendimento_id')
                ->references('aten_id')->on('atendimentos')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('aten_rel_modelo_relatorio_id', 'fk_aten_rel_modelo_relatorio_id')
                ->references('mod_rel_id')->on('modelos_relatorios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_relatorios');
    }
};
