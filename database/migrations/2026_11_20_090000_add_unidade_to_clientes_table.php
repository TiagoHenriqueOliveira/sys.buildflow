<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Unidade do cliente: a mesma empresa (mesmo CNPJ) atende vários
// municípios/unidades, e cada unidade é um cliente próprio. O CNPJ só
// pode repetir com unidade diferente — garantido também no banco pelo
// índice único (CNPJ, unidade). Vazio ('') = sem unidade, e não NULL:
// com NULL o índice único deixaria repetir o CNPJ entre clientes sem
// unidade. A collation unicode_ci faz "Itaquiraí" = "ITAQUIRAI".
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('cli_unidade', 100)
                ->charset('utf8mb3')->collation('utf8mb3_unicode_ci')
                ->default('')
                ->after('cli_nome');
            $table->unique(['cli_cnpj', 'cli_unidade'], 'uq_cli_cnpj_unidade');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('uq_cli_cnpj_unidade');
            $table->dropColumn('cli_unidade');
        });
    }
};
