<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF04 - CNPJ so era validado a nivel de aplicacao (Rule::unique no
// ClienteRequest); esta migration garante a integridade tambem no banco.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->unique('cli_cnpj', 'clientes_cli_cnpj_unique');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_cli_cnpj_unique');
        });
    }
};