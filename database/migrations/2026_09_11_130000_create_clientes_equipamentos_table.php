<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-11): a aba Histórico do cliente precisa
// permitir varios equipamentos, nao so um texto livre — mesmo padrao de
// clientes_contatos (lista repetivel editada junto do cadastro). O campo
// legado `cli_equipamento_vendido` (CRM07) continua existindo e passa a
// ser sincronizado como resumo (join por virgula) desta lista, pra nao
// precisar mexer no popup do mapa de relacoes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes_equipamentos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('cli_equip_id')->autoIncrement();
            $table->integer('cli_equip_cliente_id');
            $table->string('cli_equip_descricao', 255);

            $table->index('cli_equip_cliente_id', 'fk_cli_equip_cliente_id_idx');
            $table->foreign('cli_equip_cliente_id', 'fk_cli_equip_cliente_id')
                ->references('cli_id')->on('clientes')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_equipamentos');
    }
};