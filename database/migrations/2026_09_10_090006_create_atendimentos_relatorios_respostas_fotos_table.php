<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NC03 — foto anexada a uma resposta, com comentário próprio (diferente
// do anexo geral do relatório, NC04, que não tem comentário).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios_respostas_fotos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('aten_rel_resp_foto_id')->autoIncrement();
            $table->integer('aten_rel_resp_foto_resposta_id');
            $table->string('aten_rel_resp_foto_path', 255)
                ->charset('utf8mb3')->collation('utf8mb3_unicode_ci');
            $table->text('aten_rel_resp_foto_comentario')->nullable();

            $table->index('aten_rel_resp_foto_resposta_id', 'fk_aten_rel_resp_foto_resposta_id_idx');
            $table->foreign('aten_rel_resp_foto_resposta_id', 'fk_aten_rel_resp_foto_resposta_id')
                ->references('aten_rel_resp_id')->on('atendimentos_relatorios_respostas')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_relatorios_respostas_fotos');
    }
};
