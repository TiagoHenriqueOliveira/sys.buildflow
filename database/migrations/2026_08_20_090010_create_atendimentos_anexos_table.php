<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
// PK unsigned e charset utf8mb4 — confirmado no dump original. Atenção:
// aten_anexo_atendimento_id é int unsigned mas SEM foreign key e SEM
// índice no schema original (diferente de todas as outras tabelas
// "filhas" de atendimentos, que têm FK explícita) — preservado exatamente
// assim, não é um esquecimento desta migration.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_anexos', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('aten_anexo_id');
            $table->unsignedInteger('aten_anexo_atendimento_id');
            $table->string('aten_anexo_path', 255);
            $table->string('aten_anexo_nome_original', 255);
            $table->timestamp('aten_anexo_created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_anexos');
    }
};
