<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Segurança do acesso web (2026-11-27, pedido do cliente: "igual ao
// promaq-sys"):
// - MFA por app autenticador (TOTP), obrigatório para todos: segredo
//   criptografado (cast "encrypted" no model) + data da confirmação.
// - "Lembrar dados" no login: aparelhos (navegadores) lembrados, para o
//   próximo acesso ir direto ao código do autenticador. Só o hash do token
//   fica no banco; o token vai num cookie criptografado e HttpOnly.
// - Tabela de sessões, para SESSION_DRIVER=database (permite encerrar as
//   sessões de um usuário ao trocar a senha ou desativá-lo).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->text('user_mfa_secret')->nullable()->after('user_senha');
            $table->dateTime('user_mfa_confirmado_em')->nullable()->after('user_mfa_secret');
        });

        Schema::create('usuarios_dispositivos_lembrados', function (Blueprint $table) {
            $table->integer('disp_id')->autoIncrement();
            $table->integer('disp_usuario_id');
            $table->char('disp_token_hash', 64)->unique('uq_disp_token_hash');
            $table->string('disp_user_agent', 255)->nullable();
            $table->string('disp_ip', 45)->nullable();
            $table->dateTime('disp_criado_em');
            $table->dateTime('disp_ultimo_uso_em')->nullable();
            $table->dateTime('disp_expira_em');

            $table->index('disp_usuario_id', 'fk_disp_usuario_id_idx');
            $table->foreign('disp_usuario_id', 'fk_disp_usuario_id')
                ->references('user_id')->on('usuarios')
                ->cascadeOnDelete();
        });

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('usuarios_dispositivos_lembrados');
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['user_mfa_secret', 'user_mfa_confirmado_em']);
        });
    }
};
