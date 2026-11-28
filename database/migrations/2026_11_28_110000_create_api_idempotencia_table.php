<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Auditoria 27/11/2026 (S-14) / contrato fae/v1 §4: respostas das
// gravações do app guardadas por (usuário, Idempotency-Key) durante 7 dias,
// para a retentativa de uma gravação que já aconteceu devolver a mesma
// resposta em vez de criar outro registro.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_idempotencia', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('idem_id');
            $table->integer('idem_usuario_id');
            $table->string('idem_chave', 64);
            $table->string('idem_rota', 191);
            $table->char('idem_hash', 64);
            $table->unsignedSmallInteger('idem_status')->nullable();
            $table->mediumText('idem_corpo')->nullable();
            $table->dateTime('idem_criado_em');
            $table->dateTime('idem_concluido_em')->nullable();

            $table->unique(['idem_usuario_id', 'idem_chave'], 'uq_idem_usuario_chave');
            $table->index('idem_criado_em', 'idx_idem_criado_em');
            $table->foreign('idem_usuario_id', 'fk_idem_usuario_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_idempotencia');
    }
};
