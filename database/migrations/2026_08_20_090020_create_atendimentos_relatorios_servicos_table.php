<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
// Mesma observação de nome de índice/constraint e ausência de ON UPDATE
// explícito que atendimentos_relatorios_pecas — ver comentário lá.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios_servicos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('aten_rel_serv_id')->autoIncrement();
            $table->integer('aten_rel_serv_relatorio_id');
            $table->string('aten_rel_serv_descricao', 255);

            $table->index('aten_rel_serv_relatorio_id', 'aten_rel_serv_relatorio_id');
            $table->foreign('aten_rel_serv_relatorio_id', 'atendimentos_relatorios_servicos_ibfk_1')
                ->references('aten_rel_id')->on('atendimentos_relatorios')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_relatorios_servicos');
    }
};
