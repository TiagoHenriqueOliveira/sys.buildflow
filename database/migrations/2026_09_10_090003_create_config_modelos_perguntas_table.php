<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NC02 — vínculo M:N entre modelo e pergunta: a mesma pergunta pode ser
// reaproveitada em vários modelos, sem duplicar cadastro (critério de
// aceite explícito da sessão 04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_modelos_perguntas', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('cfg_mod_perg_id')->autoIncrement();
            $table->integer('cfg_mod_perg_modelo_id');
            $table->integer('cfg_mod_perg_pergunta_id');
            $table->integer('cfg_mod_perg_ordem')->default(0);

            $table->unique(['cfg_mod_perg_modelo_id', 'cfg_mod_perg_pergunta_id'], 'config_modelos_perguntas_unica');
            $table->index('cfg_mod_perg_modelo_id', 'fk_cfg_mod_perg_modelo_id_idx');
            $table->index('cfg_mod_perg_pergunta_id', 'fk_cfg_mod_perg_pergunta_id_idx');

            $table->foreign('cfg_mod_perg_modelo_id', 'fk_cfg_mod_perg_modelo_id')
                ->references('cfg_mod_id')->on('config_modelos')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('cfg_mod_perg_pergunta_id', 'fk_cfg_mod_perg_pergunta_id')
                ->references('cfg_perg_id')->on('config_perguntas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_modelos_perguntas');
    }
};
