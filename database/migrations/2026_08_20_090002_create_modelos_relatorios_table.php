<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelos_relatorios', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('mod_rel_id')->autoIncrement();
            // Comentário no dump original usa "\\n" (barra invertida literal
            // seguida de "n"), não uma quebra de linha real — preservado
            // exatamente assim (string PHP em aspas simples não interpreta
            // \n como escape).
            $table->integer('mod_rel_tp_data')->default(0)
                ->comment('0 - relatório diário\n1 - relatório período');
            $table->string('mod_rel_descricao', 50);
            $table->tinyInteger('mod_rel_ativo')->default(1);
            $table->tinyInteger('mod_rel_descricao_secao')->default(0);
            $table->tinyInteger('mod_rel_servicos_prestados')->default(0);
            $table->tinyInteger('mod_rel_pecas_substituidas')->default(0);
            $table->tinyInteger('mod_rel_informacoes_adicionais')->default(0);
            $table->tinyInteger('mod_rel_horarios')->default(1);
            $table->tinyInteger('mod_rel_cond_clima')->default(1);
            $table->tinyInteger('mod_rel_ocorrencia')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos_relatorios');
    }
};
