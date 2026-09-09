<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('cli_id')->autoIncrement();
            $table->string('cli_nome', 100);
            $table->string('cli_cnpj', 14);
            $table->string('cli_cidade', 100);
            $table->string('cli_uf', 2);
            $table->string('cli_telefone', 11)->nullable();
            $table->string('cli_email', 100)->nullable();
            $table->tinyInteger('cli_ativo')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
