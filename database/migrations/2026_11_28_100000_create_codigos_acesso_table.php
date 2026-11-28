<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Auditoria 27/11/2026 (S-08): códigos de uso único gerados pelo
// administrador — ativação do autenticador (conta nova ou MFA redefinido,
// vale 72 h) e redefinição de senha pelo próprio usuário (vale 24 h). Só o
// HMAC do código fica no banco; o código aparece uma única vez na tela do
// administrador.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigos_acesso', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('cod_id')->autoIncrement();
            $table->integer('cod_usuario_id');
            $table->string('cod_tipo', 20);
            $table->char('cod_hash', 64);
            $table->dateTime('cod_expira_em');
            $table->dateTime('cod_usado_em')->nullable();
            $table->dateTime('cod_revogado_em')->nullable();
            $table->integer('cod_criado_por')->nullable();
            $table->timestamps();

            $table->index(['cod_usuario_id', 'cod_tipo'], 'idx_cod_usuario_tipo');
            $table->foreign('cod_usuario_id', 'fk_cod_usuario_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('cod_criado_por', 'fk_cod_criado_por')
                ->references('user_id')->on('usuarios')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_acesso');
    }
};
