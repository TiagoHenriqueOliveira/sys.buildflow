<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-17): modulo "Segmentos" em Configuracoes,
// mesmo padrao de classificacoes_cliente - lista configuravel, criada
// vazia, usada pelo select de "Segmento" no cadastro de Cliente.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segmentos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('seg_id')->autoIncrement();
            $table->string('seg_descricao', 100);
            $table->tinyInteger('seg_ativo')->default(1);

            $table->unique('seg_descricao', 'segmentos_descricao_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segmentos');
    }
};