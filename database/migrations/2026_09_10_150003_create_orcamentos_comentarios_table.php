<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CRM03 — comentários no orçamento, com autor/data/hora e alerta opcional
// para outro usuário.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos_comentarios', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('orc_com_id')->autoIncrement();
            $table->integer('orc_com_orcamento_id');
            $table->integer('orc_com_autor_id');
            $table->text('orc_com_texto');
            $table->integer('orc_com_alerta_usuario_id')->nullable();
            $table->dateTime('orc_com_criado_em')->useCurrent();

            $table->index('orc_com_orcamento_id', 'fk_orc_com_orcamento_id_idx');
            $table->index('orc_com_autor_id', 'fk_orc_com_autor_id_idx');
            $table->index('orc_com_alerta_usuario_id', 'fk_orc_com_alerta_usuario_id_idx');

            $table->foreign('orc_com_orcamento_id', 'fk_orc_com_orcamento_id')
                ->references('orc_id')->on('orcamentos')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('orc_com_autor_id', 'fk_orc_com_autor_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('orc_com_alerta_usuario_id', 'fk_orc_com_alerta_usuario_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos_comentarios');
    }
};