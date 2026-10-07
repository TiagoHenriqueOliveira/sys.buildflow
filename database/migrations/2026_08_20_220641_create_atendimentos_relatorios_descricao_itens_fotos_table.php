<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// RF001 (revisão) — um item de descrição passa a aceitar várias fotos, não
// só uma. Cria a tabela filha 1:N (mesmo padrão de fotos/vídeos/anexos do
// relatório), migra a foto única já existente para a tabela nova e só então
// remove a coluna antiga.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_relatorios_descricao_itens_fotos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';
            $table->integer('aten_rel_desc_foto_id')->autoIncrement();
            $table->integer('aten_rel_desc_foto_item_id');
            $table->string('aten_rel_desc_foto_path', 255)
                ->charset('utf8mb3')->collation('utf8mb3_unicode_ci');
            $table->dateTime('aten_rel_desc_foto_criado_em')->useCurrent();

            $table->index('aten_rel_desc_foto_item_id', 'fk_aten_rel_desc_foto_item_id_idx');
            $table->foreign('aten_rel_desc_foto_item_id', 'fk_aten_rel_desc_foto_item_id')
                ->references('aten_rel_desc_id')->on('atendimentos_relatorios_descricao_itens')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        DB::table('atendimentos_relatorios_descricao_itens')
            ->whereNotNull('aten_rel_desc_foto_path')
            ->get(['aten_rel_desc_id', 'aten_rel_desc_foto_path'])
            ->each(function ($item) {
                DB::table('atendimentos_relatorios_descricao_itens_fotos')->insert([
                    'aten_rel_desc_foto_item_id'   => $item->aten_rel_desc_id,
                    'aten_rel_desc_foto_path'      => $item->aten_rel_desc_foto_path,
                    'aten_rel_desc_foto_criado_em' => now(),
                ]);
            });

        Schema::table('atendimentos_relatorios_descricao_itens', function (Blueprint $table) {
            $table->dropColumn('aten_rel_desc_foto_path');
        });
    }

    public function down(): void
    {
        Schema::table('atendimentos_relatorios_descricao_itens', function (Blueprint $table) {
            $table->string('aten_rel_desc_foto_path', 255)
                ->charset('utf8mb3')->collation('utf8mb3_unicode_ci')
                ->nullable()
                ->after('aten_rel_desc_texto');
        });

        // Best-effort: restaura só a primeira foto de cada item — itens com
        // mais de uma foto perdem as demais ao reverter esta migration.
        DB::table('atendimentos_relatorios_descricao_itens_fotos')
            ->orderBy('aten_rel_desc_foto_id')
            ->get()
            ->groupBy('aten_rel_desc_foto_item_id')
            ->each(function ($fotos, $itemId) {
                DB::table('atendimentos_relatorios_descricao_itens')
                    ->where('aten_rel_desc_id', $itemId)
                    ->update(['aten_rel_desc_foto_path' => $fotos->first()->aten_rel_desc_foto_path]);
            });

        Schema::dropIfExists('atendimentos_relatorios_descricao_itens_fotos');
    }
};
