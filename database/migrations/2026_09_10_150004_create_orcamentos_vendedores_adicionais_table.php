<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CRM04 — vendedores adicionais indicados junto no orçamento (indicação
// conjunta), sem nenhum campo de comissão (fora de escopo, ver
// docs/cronograma/06-web-crm-telas.md).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos_vendedores_adicionais', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('orc_vend_ad_id')->autoIncrement();
            $table->integer('orc_vend_ad_orcamento_id');
            $table->integer('orc_vend_ad_usuario_id');

            $table->unique(['orc_vend_ad_orcamento_id', 'orc_vend_ad_usuario_id'], 'orcamentos_vendedores_adicionais_unica');
            $table->index('orc_vend_ad_orcamento_id', 'fk_orc_vend_ad_orcamento_id_idx');
            $table->index('orc_vend_ad_usuario_id', 'fk_orc_vend_ad_usuario_id_idx');

            $table->foreign('orc_vend_ad_orcamento_id', 'fk_orc_vend_ad_orcamento_id')
                ->references('orc_id')->on('orcamentos')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('orc_vend_ad_usuario_id', 'fk_orc_vend_ad_usuario_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos_vendedores_adicionais');
    }
};