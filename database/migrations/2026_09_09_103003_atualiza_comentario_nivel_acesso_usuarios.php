<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// BF02 — só atualiza o comentário da coluna (documentação do schema) para
// incluir o novo valor 2 (Comercial); nenhum dado é alterado, a coluna já
// aceita qualquer inteiro. Usa DB::statement em vez de Blueprint::change()
// para não depender de doctrine/dbal.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE usuarios MODIFY user_nivel_acesso INT NOT NULL COMMENT '0 - Administrador\n1 - Técnico\n2 - Comercial'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE usuarios MODIFY user_nivel_acesso INT NOT NULL COMMENT '0 - Administrador\n1 - Técnico'"
        );
    }
};
