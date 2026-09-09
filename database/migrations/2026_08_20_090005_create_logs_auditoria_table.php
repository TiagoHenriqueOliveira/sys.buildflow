<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
// PK unsigned (diferente da maioria das tabelas, que usa int signed) e
// charset utf8mb4 — confirmado no dump original.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            // increments() = int unsigned AUTO_INCREMENT PRIMARY KEY.
            $table->increments('log_aud_id');
            $table->string('log_aud_modulo', 50);
            $table->string('log_aud_acao', 20);
            $table->integer('log_aud_registro_id')->nullable();
            $table->integer('log_aud_usuario_id')->nullable();
            $table->json('log_aud_dados_anteriores')->nullable();
            $table->json('log_aud_dados_novos')->nullable();
            $table->timestamp('log_aud_created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_auditoria');
    }
};
