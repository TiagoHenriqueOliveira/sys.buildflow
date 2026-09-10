<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CRM05 (saida) + CRM06 (retorno): um cliente por linha do roteiro, com
// ordem de visita e, depois da viagem, o resultado + observacao.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_roteiros_viagem_clientes', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('crm_rot_cli_id')->autoIncrement();
            $table->integer('crm_rot_cli_roteiro_id');
            $table->integer('crm_rot_cli_cliente_id');
            $table->integer('crm_rot_cli_ordem')->default(0);
            $table->tinyInteger('crm_rot_cli_resultado')->nullable()
                ->comment("0 - Visitado\n1 - Nao realizado\n2 - Reagendado");
            $table->text('crm_rot_cli_observacao')->nullable();

            $table->unique(['crm_rot_cli_roteiro_id', 'crm_rot_cli_cliente_id'], 'crm_roteiros_viagem_clientes_unica');
            $table->index('crm_rot_cli_roteiro_id', 'fk_crm_rot_cli_roteiro_id_idx');
            $table->index('crm_rot_cli_cliente_id', 'fk_crm_rot_cli_cliente_id_idx');

            $table->foreign('crm_rot_cli_roteiro_id', 'fk_crm_rot_cli_roteiro_id')
                ->references('crm_rot_id')->on('crm_roteiros_viagem')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('crm_rot_cli_cliente_id', 'fk_crm_rot_cli_cliente_id')
                ->references('cli_id')->on('clientes')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_roteiros_viagem_clientes');
    }
};