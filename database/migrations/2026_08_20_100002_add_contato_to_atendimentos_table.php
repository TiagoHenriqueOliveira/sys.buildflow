<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RF011 — campo "Contato" no cadastro de atendimento, separado do
// "Responsável" já existente. Mesmo tipo/tamanho de aten_responsavel
// (varchar(50), utf8mb3_unicode_ci) para manter consistência entre os
// dois campos irmãos.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atendimentos', function (Blueprint $table) {
            $table->string('aten_contato', 50)
                ->charset('utf8mb3')->collation('utf8mb3_unicode_ci')
                ->nullable()
                ->after('aten_nr_proposta');
        });
    }

    public function down(): void
    {
        Schema::table('atendimentos', function (Blueprint $table) {
            $table->dropColumn('aten_contato');
        });
    }
};
