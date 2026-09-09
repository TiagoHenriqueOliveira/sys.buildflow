<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — adoção de migrations versionadas por tabela nesta branch (FAÉ).
// A `feature/mcl` (de onde esta branch foi forkada) adotou migrations
// tardiamente e capturou o schema pré-existente num único dump SQL bruto
// (database/schema/baseline-schema.sql, via `php artisan schema:dump`),
// aplicado por uma única migration com DB::unprepared(). Isso funciona,
// mas deixa o histórico de schema opaco desde a tabela #1.
//
// Nesta branch (FAÉ), o schema começa do zero como migrations reais, uma
// por tabela, usando Schema::create()/Blueprint — sem SQL bruto. As 22
// migrations 2026_08_20_090000..090021 traduzem, tabela a tabela, o mesmo
// schema que o dump da MCL descreve (types, defaults, comments, índices e
// foreign keys idênticos), na ordem de dependência de FK. O arquivo
// database/schema/baseline-schema.sql é mantido no repositório só como
// referência histórica/auditoria — nenhuma migration mais o lê.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            // autoIncrement() já define esta coluna como PRIMARY KEY.
            $table->integer('user_id')->autoIncrement();
            $table->integer('user_nivel_acesso')->comment("0 - Administrador\n1 - Técnico");
            $table->string('user_nome', 50);
            $table->string('user_email', 100);
            $table->string('user_senha', 255);
            $table->tinyInteger('user_ativo')->default(1);
            $table->boolean('user_protegido')->default(false);

            $table->unique('user_email', 'usuarios_user_email_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
