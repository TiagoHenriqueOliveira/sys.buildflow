<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// CRM01 — tipos de sistema de orçamento (lista fechada do documento de
// requisitos) vinculados a um modelo do Configurador de setor Comercial.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_tipos_orcamento', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('crm_tp_orc_id')->autoIncrement();
            $table->string('crm_tp_orc_nome', 100);
            $table->integer('crm_tp_orc_config_modelo_id')->nullable();
            $table->tinyInteger('crm_tp_orc_ativo')->default(1);

            $table->index('crm_tp_orc_config_modelo_id', 'fk_crm_tp_orc_config_modelo_id_idx');
            $table->foreign('crm_tp_orc_config_modelo_id', 'fk_crm_tp_orc_config_modelo_id')
                ->references('cfg_mod_id')->on('config_modelos')
                ->onDelete('set null')->onUpdate('cascade');
        });

        // Lista fechada citada no documento de requisitos (CRM01) — semeada
        // aqui (não em seeder separado) para já existir em qualquer ambiente
        // que rodar as migrations, igual às demais listas fixas do domínio.
        DB::table('crm_tipos_orcamento')->insert([
            ['crm_tp_orc_nome' => 'Genérico', 'crm_tp_orc_ativo' => 1],
            ['crm_tp_orc_nome' => 'Sistema de Deságue de Lodo', 'crm_tp_orc_ativo' => 1],
            ['crm_tp_orc_nome' => 'ETE Nova Residencial', 'crm_tp_orc_ativo' => 1],
            ['crm_tp_orc_nome' => 'ETE Nova Industrial', 'crm_tp_orc_ativo' => 1],
            ['crm_tp_orc_nome' => 'ETE Melhoria Industrial', 'crm_tp_orc_ativo' => 1],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_tipos_orcamento');
    }
};