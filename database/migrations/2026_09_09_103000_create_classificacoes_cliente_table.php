<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NC01 (pendência #3 — ver docs/cronograma/01-web-preparacao.md): lista
// configurável de classificação de cliente, criada vazia — a UI deve
// funcionar com zero opções cadastradas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classificacoes_cliente', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('cla_cli_id')->autoIncrement();
            $table->string('cla_cli_nome', 50);
            $table->tinyInteger('cla_cli_ativo')->default(1);

            $table->unique('cla_cli_nome', 'classificacoes_cliente_nome_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classificacoes_cliente');
    }
};
