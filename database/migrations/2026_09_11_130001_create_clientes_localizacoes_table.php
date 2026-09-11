<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-11): alem da localizacao principal
// (cli_latitude/cli_longitude, usada no mapa de relacoes - CRM07), a aba
// Geolocalizacao precisa aceitar uma LISTA de localizacoes do cliente, cada
// uma com descricao livre (ex.: "Empresa", "Instalacao/Montagem",
// "Manutencao"). Mesmo padrao de clientes_contatos — lista repetivel
// editada junto do cadastro, sem afetar o ponto principal ja consumido
// pelo mapa/BF01.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes_localizacoes', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('cli_loc_id')->autoIncrement();
            $table->integer('cli_loc_cliente_id');
            $table->string('cli_loc_descricao', 100);
            $table->decimal('cli_loc_latitude', 10, 7);
            $table->decimal('cli_loc_longitude', 10, 7);

            $table->index('cli_loc_cliente_id', 'fk_cli_loc_cliente_id_idx');
            $table->foreign('cli_loc_cliente_id', 'fk_cli_loc_cliente_id')
                ->references('cli_id')->on('clientes')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_localizacoes');
    }
};