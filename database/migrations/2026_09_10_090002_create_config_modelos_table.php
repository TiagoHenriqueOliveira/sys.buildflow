<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NC02 — modelo do Configurador: agrupa perguntas reutilizáveis por setor
// (Comercial, usado pelo CRM/orçamento; Assistência, usado pelo relatório
// de atendimento via BF04). Não confundir com `modelos_relatorios`
// (tabela de flags, conceito antigo e não relacionado).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_modelos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('cfg_mod_id')->autoIncrement();
            $table->string('cfg_mod_nome', 100);
            $table->tinyInteger('cfg_mod_setor')
                ->comment("0 - Comercial\n1 - Assistência");
            $table->tinyInteger('cfg_mod_ativo')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_modelos');
    }
};
