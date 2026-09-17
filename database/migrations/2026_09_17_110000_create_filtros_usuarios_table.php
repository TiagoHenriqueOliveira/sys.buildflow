<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-09-17): filtros aplicados em qualquer tela devem
// ser lembrados por usuario logado, sem precisar preencher de novo a cada
// visita. Uma linha por (usuario, tela), com os valores dos filtros daquela
// tela guardados como JSON - ver App\Http\Controllers\Concerns\PersisteFiltros.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filtros_usuarios', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('filt_id')->autoIncrement();
            $table->integer('filt_usuario_id');
            $table->string('filt_tela', 60);
            $table->json('filt_valores');

            $table->unique(['filt_usuario_id', 'filt_tela'], 'filtros_usuarios_usuario_tela_unique');

            $table->foreign('filt_usuario_id', 'fk_filt_usuario_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filtros_usuarios');
    }
};