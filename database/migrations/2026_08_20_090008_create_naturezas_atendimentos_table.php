<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('naturezas_atendimentos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('nat_aten_id')->autoIncrement();
            $table->integer('nat_aten_mod_relatorio_id');
            $table->string('nat_aten_descricao', 50);
            $table->tinyInteger('nat_aten_ativo')->default(1);

            $table->index('nat_aten_mod_relatorio_id', 'fk_nat_aten_mod_relatorio_id_idx');
            $table->foreign('nat_aten_mod_relatorio_id', 'fk_nat_aten_mod_relatorio_id')
                ->references('mod_rel_id')->on('modelos_relatorios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('naturezas_atendimentos');
    }
};
