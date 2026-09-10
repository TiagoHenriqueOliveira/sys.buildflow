<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NC02 — banco de perguntas reutilizável do Configurador. Prefixo
// "config_"/"cfg_" para não colidir com o conceito não relacionado de
// `modelos_relatorios` (tabela de flags, ver docs/cronograma/04-web-configurador-telas.md).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_perguntas', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('cfg_perg_id')->autoIncrement();
            $table->text('cfg_perg_texto');
            $table->tinyInteger('cfg_perg_tipo')
                ->comment("0 - Múltipla escolha\n1 - Escolha única\n2 - Texto livre");
            $table->boolean('cfg_perg_permite_anexo')->default(false);
            $table->tinyInteger('cfg_perg_ativo')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_perguntas');
    }
};
