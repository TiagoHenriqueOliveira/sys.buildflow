<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BF07 - historico/comprovante de compartilhamento do relatorio (o disparo
// em si e majoritariamente mobile - WhatsApp/menu nativo; aqui so guarda o
// comprovante gerado - data/hora + hash - para consulta no painel web).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios_compartilhamentos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('aten_rel_comp_id')->autoIncrement();
            $table->integer('aten_rel_comp_relatorio_id');
            $table->integer('aten_rel_comp_usuario_id')->nullable();
            $table->string('aten_rel_comp_canal', 50)->nullable();
            $table->string('aten_rel_comp_hash', 64);
            $table->dateTime('aten_rel_comp_criado_em');

            $table->index('aten_rel_comp_relatorio_id', 'fk_aten_rel_comp_relatorio_id_idx');
            $table->foreign('aten_rel_comp_relatorio_id', 'fk_aten_rel_comp_relatorio_id')
                ->references('aten_rel_id')->on('atendimentos_relatorios')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('aten_rel_comp_usuario_id', 'fk_aten_rel_comp_usuario_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_relatorios_compartilhamentos');
    }
};