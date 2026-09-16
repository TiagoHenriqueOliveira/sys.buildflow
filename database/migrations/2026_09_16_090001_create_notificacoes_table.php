<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-16): Sistema de Notificacoes generico (sino no
// Web) - usado tanto pelo alerta de recontato de cliente quanto pelo alerta
// de comentario de orcamento (CRM03, que ate aqui so salvava o destinatario
// sem nunca notificar de verdade).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacoes', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('notif_id')->autoIncrement();
            $table->integer('notif_usuario_id');
            $table->string('notif_tipo', 50);
            $table->string('notif_titulo', 150);
            $table->text('notif_mensagem');
            $table->string('notif_link', 500)->nullable();
            $table->boolean('notif_lida')->default(false);
            $table->timestamp('notif_criado_em')->useCurrent();

            $table->index('notif_usuario_id', 'fk_notif_usuario_id_idx');
            $table->index(['notif_usuario_id', 'notif_lida'], 'notif_usuario_lida_idx');

            $table->foreign('notif_usuario_id', 'fk_notif_usuario_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes');
    }
};