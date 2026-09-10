<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CRM05 - roteiro de viagem de saida: vendedor, periodo, lista de
// clientes a visitar (a lista fica na tabela filha
// crm_roteiros_viagem_clientes, que tambem guarda o retorno - CRM06).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_roteiros_viagem', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('crm_rot_id')->autoIncrement();
            $table->integer('crm_rot_vendedor_id');
            $table->date('crm_rot_periodo_inicio');
            $table->date('crm_rot_periodo_fim');
            $table->tinyInteger('crm_rot_ativo')->default(1);
            $table->dateTime('crm_rot_criado_em')->useCurrent();

            $table->index('crm_rot_vendedor_id', 'fk_crm_rot_vendedor_id_idx');
            $table->foreign('crm_rot_vendedor_id', 'fk_crm_rot_vendedor_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_roteiros_viagem');
    }
};