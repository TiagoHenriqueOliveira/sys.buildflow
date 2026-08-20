<?php

use Illuminate\Database\Migrations\Migration;

// RNF002 — marco de adoção de migrations neste repositório. O schema até
// este ponto (22 tabelas) nunca teve migrations versionadas; foi capturado
// via `php artisan schema:dump` em database/schema/mysql-schema.sql, que o
// Laravel carrega automaticamente ao rodar `migrate` numa base nova/vazia.
//
// Este registro está marcado como já executado na tabela `migrations` de
// todo ambiente que já tem o schema atual (produção incluída) — não
// recriar tabelas nem duplicar dados. Não remover nem reverter.
return new class extends Migration
{
    public function up(): void
    {
        // Intencionalmente vazio — o schema já existe nos ambientes atuais.
    }

    public function down(): void
    {
        // Intencionalmente vazio — ver comentário acima.
    }
};
