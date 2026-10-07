<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// RNF002 — marco de adoção de migrations neste repositório. O schema até
// este ponto (22 tabelas) nunca teve migrations versionadas; foi capturado
// via `php artisan schema:dump` em database/schema/baseline-schema.sql.
//
// O arquivo NÃO fica em database/schema/mysql-schema.sql (nome convencional)
// de propósito: esse nome aciona um atalho automático do Laravel (carregar o
// dump direto quando a tabela `migrations` está vazia) que não inclui a
// própria tabela `migrations` no dump — resultado: as 22 tabelas eram
// criadas, mas `migrations` ficava inexistente, quebrando tudo depois.
// Renomeado para não ser detectado por esse atalho; esta migration lê o
// arquivo diretamente, então a tabela `migrations` já existe (criada pelo
// Laravel antes de qualquer migration rodar) quando este up() executa.
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

        $sql = file_get_contents(database_path('schema/baseline-schema.sql'));
        DB::unprepared($sql);
    }

    public function down(): void
    {
        // Intencionalmente vazio — reverter um dump de 22 tabelas não é seguro
        // de automatizar aqui; ver comentário acima.
    }
};
