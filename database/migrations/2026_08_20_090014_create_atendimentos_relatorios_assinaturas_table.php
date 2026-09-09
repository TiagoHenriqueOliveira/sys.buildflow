<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
// Colunas aten_rel_ass_nome/aten_rel_ass_cpf ficam na migration incremental
// já existente (2026_08_20_100001) — não replicadas aqui, este é só o
// estado do dump original.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios_assinaturas', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('aten_rel_ass_id')->autoIncrement();
            $table->integer('aten_rel_ass_relatorio_id');
            $table->string('aten_rel_ass_path', 255);
            $table->string('aten_rel_ass_tipo', 20);
            $table->dateTime('aten_rel_ass_assinado_em')->nullable();

            $table->index('aten_rel_ass_relatorio_id', 'fk_aten_rel_ass_relatorio_id_idx');
            $table->foreign('aten_rel_ass_relatorio_id', 'fk_aten_rel_ass_relatorio_id')
                ->references('aten_rel_id')->on('atendimentos_relatorios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_relatorios_assinaturas');
    }
};
