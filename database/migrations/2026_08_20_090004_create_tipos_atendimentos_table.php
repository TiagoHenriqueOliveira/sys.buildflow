<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_atendimentos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('tp_aten_id')->autoIncrement();
            $table->string('tp_aten_descricao', 30);
            $table->tinyInteger('tp_aten_ativo')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_atendimentos');
    }
};
