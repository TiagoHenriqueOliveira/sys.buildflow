<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CRM02 — entidade central do CRM Comercial. nivel/prazo_envio são
// editáveis manualmente nesta etapa (cálculo automático real do prazo
// fica para a persistência, sessão 07).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('orc_id')->autoIncrement();
            $table->integer('orc_cliente_id');
            $table->integer('orc_vendedor_id');
            $table->integer('orc_tipo_orcamento_id')->nullable();
            $table->tinyInteger('orc_nivel')->nullable()
                ->comment("0 - Simples\n1 - Médio\n2 - Complexo");
            $table->date('orc_prazo_envio')->nullable();
            $table->tinyInteger('orc_ativo')->default(1);
            $table->dateTime('orc_criado_em')->useCurrent();

            $table->index('orc_cliente_id', 'fk_orc_cliente_id_idx');
            $table->index('orc_vendedor_id', 'fk_orc_vendedor_id_idx');
            $table->index('orc_tipo_orcamento_id', 'fk_orc_tipo_orcamento_id_idx');

            $table->foreign('orc_cliente_id', 'fk_orc_cliente_id')
                ->references('cli_id')->on('clientes')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('orc_vendedor_id', 'fk_orc_vendedor_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('orc_tipo_orcamento_id', 'fk_orc_tipo_orcamento_id')
                ->references('crm_tp_orc_id')->on('crm_tipos_orcamento')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};