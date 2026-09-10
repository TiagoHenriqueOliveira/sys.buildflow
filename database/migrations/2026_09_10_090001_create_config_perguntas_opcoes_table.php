<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NC02 — opções de resposta de uma pergunta (só usadas quando
// cfg_perg_tipo é Múltipla escolha ou Escolha única).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_perguntas_opcoes', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('cfg_perg_op_id')->autoIncrement();
            $table->integer('cfg_perg_op_pergunta_id');
            $table->string('cfg_perg_op_texto', 255);

            $table->index('cfg_perg_op_pergunta_id', 'fk_cfg_perg_op_pergunta_id_idx');
            $table->foreign('cfg_perg_op_pergunta_id', 'fk_cfg_perg_op_pergunta_id')
                ->references('cfg_perg_id')->on('config_perguntas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_perguntas_opcoes');
    }
};
