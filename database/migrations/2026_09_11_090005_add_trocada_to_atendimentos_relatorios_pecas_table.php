<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BF09 - falta o checklist booleano de peca trocada (a tabela ja existia
// so com descricao livre, ver docs/cronograma/08-web-atendimento-telas.md).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atendimentos_relatorios_pecas', function (Blueprint $table) {
            $table->boolean('aten_rel_peca_trocada')->default(false)->after('aten_rel_peca_descricao');
        });
    }

    public function down(): void
    {
        Schema::table('atendimentos_relatorios_pecas', function (Blueprint $table) {
            $table->dropColumn('aten_rel_peca_trocada');
        });
    }
};