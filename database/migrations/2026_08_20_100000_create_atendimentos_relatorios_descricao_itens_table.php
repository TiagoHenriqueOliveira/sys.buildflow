<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RF001 — lista de itens de descrição (texto + foto opcional) por relatório,
// no mesmo padrão estrutural de atendimentos_relatorios_pecas. Tipos e
// collation replicam exatamente as tabelas irmãs (utf8mb3_unicode_ci) —
// confirmado via SHOW CREATE TABLE no schema atual antes de escrever esta
// migration (ver database/schema/mysql-schema.sql, baseline RNF002).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios_descricao_itens', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            // autoIncrement() já define esta coluna como PRIMARY KEY.
            $table->integer('aten_rel_desc_id')->autoIncrement();

            $table->integer('aten_rel_desc_relatorio_id');
            // "text" (não varchar(255) como as "peças"): o campo legado que
            // este item substitui (aten_rel_descricao) é longtext — cada
            // item pode conter um parágrafo, não só uma linha curta.
            $table->text('aten_rel_desc_texto');
            $table->string('aten_rel_desc_foto_path', 255)->nullable();
            $table->dateTime('aten_rel_desc_criado_em')->useCurrent();

            $table->index('aten_rel_desc_relatorio_id', 'fk_aten_rel_desc_relatorio_id_idx');
            $table->foreign('aten_rel_desc_relatorio_id', 'fk_aten_rel_desc_relatorio_id')
                ->references('aten_rel_id')->on('atendimentos_relatorios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_relatorios_descricao_itens');
    }
};
