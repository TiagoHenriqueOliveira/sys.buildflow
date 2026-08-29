<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// RNF002 — marco de adoção de migrations neste repositório. O schema até
// este ponto (22 tabelas) nunca teve migrations versionadas; foi capturado
// via `php artisan schema:dump` em database/schema/mysql-schema.sql.
//
// Em produção/dev, este registro já está marcado como executado na tabela
// `migrations` — up() nunca roda de novo ali, então nada muda. A checagem
// abaixo existe só para bases genuinamente novas (banco de teste, ambiente
// novo): sem ela, nenhuma tabela era criada num `migrate` do zero, porque
// este método ficava vazio.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('atendimentos')) {
            return;
        }

        $sql = file_get_contents(database_path('schema/mysql-schema.sql'));
        DB::unprepared($sql);
    }

    public function down(): void
    {
        // Intencionalmente vazio — reverter um dump de 22 tabelas não é seguro
        // de automatizar aqui; ver comentário acima.
    }
};
