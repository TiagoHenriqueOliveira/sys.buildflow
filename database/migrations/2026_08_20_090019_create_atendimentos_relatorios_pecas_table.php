<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
// Nome do índice e da constraint seguem o padrão antigo do MySQL
// (auto-gerado, sem prefixo fk_) porque é assim que estão no dump
// original (atendimentos_relatorios_pecas_ibfk_1) — preservado
// literalmente. Também sem ON UPDATE explícito no dump original (default
// do MySQL quando omitido); não adicionamos onUpdate('cascade') aqui de
// propósito, para bater exatamente com o schema atual.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios_pecas', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('aten_rel_peca_id')->autoIncrement();
            $table->integer('aten_rel_peca_relatorio_id');
            $table->string('aten_rel_peca_descricao', 255);

            $table->index('aten_rel_peca_relatorio_id', 'aten_rel_peca_relatorio_id');
            $table->foreign('aten_rel_peca_relatorio_id', 'atendimentos_relatorios_pecas_ibfk_1')
                ->references('aten_rel_id')->on('atendimentos_relatorios')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_relatorios_pecas');
    }
};
