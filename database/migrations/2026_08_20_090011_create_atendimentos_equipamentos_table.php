<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos_equipamentos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('aten_equip_id')->autoIncrement();
            $table->integer('aten_equip_atendimento_id');
            $table->string('aten_equip_descricao', 255);

            $table->index('aten_equip_atendimento_id', 'fk_aten_equip_atendimento_id_idx');
            $table->foreign('aten_equip_atendimento_id', 'fk_aten_equip_atendimento_id')
                ->references('aten_id')->on('atendimentos')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos_equipamentos');
    }
};
