<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
// PK unsigned e charset utf8mb4 — confirmado no dump original.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_erros', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('log_err_id');
            $table->string('log_err_modulo', 50)->nullable();
            $table->string('log_err_nivel', 20)->default('error');
            $table->text('log_err_mensagem');
            $table->json('log_err_contexto')->nullable();
            $table->integer('log_err_usuario_id')->nullable();
            $table->timestamp('log_err_created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_erros');
    }
};
