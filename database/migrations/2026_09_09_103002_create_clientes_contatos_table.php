<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NC01 — contatos adicionais do cliente (Nome, Cargo, Telefone, E-mail,
// Tipo Técnico/Comercial), lista repetível editada junto do cadastro.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes_contatos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('cli_cont_id')->autoIncrement();
            $table->integer('cli_cont_cliente_id');
            $table->string('cli_cont_nome', 100);
            $table->string('cli_cont_cargo', 100)->nullable();
            $table->string('cli_cont_telefone', 11)->nullable();
            $table->string('cli_cont_email', 100)->nullable();
            $table->tinyInteger('cli_cont_tipo')->default(0)->comment("0 - Técnico\n1 - Comercial");

            $table->index('cli_cont_cliente_id', 'fk_cli_cont_cliente_id_idx');
            $table->foreign('cli_cont_cliente_id', 'fk_cli_cont_cliente_id')
                ->references('cli_id')->on('clientes')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_contatos');
    }
};
