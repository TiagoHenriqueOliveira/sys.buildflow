<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RNF07 — ver comentário completo em 2026_08_20_090000_create_usuarios_table.php.
// Colunas adicionadas depois (aten_contato, índices de desempenho) ficam
// nas migrations incrementais já existentes (2026_08_20_100002 e
// 2026_09_01_101531) — não replicadas aqui, este é só o estado do dump
// original.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atendimentos', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('aten_id')->autoIncrement();
            $table->integer('aten_natureza_id');
            $table->integer('aten_cliente_id');
            $table->integer('aten_usuario_id');
            $table->integer('aten_status')->default(0)
                ->comment("0 - Não iniciada\n             1 - Paralisada\n             2 - Em andamento\n             3 - Concluída");
            $table->string('aten_nr_proposta', 20)->nullable();
            $table->string('aten_responsavel', 50)->nullable();
            $table->string('aten_telefone', 20)->nullable();
            $table->string('aten_endereco', 100)->nullable();
            $table->boolean('aten_entrega_tecnica')->default(false);
            $table->date('aten_dt_inicio');
            $table->date('aten_dt_fim');
            $table->longText('aten_obs_tecnica')->nullable();
            $table->longText('aten_obs_cliente')->nullable();
            $table->text('aten_obs_manutencao')->nullable();

            $table->index('aten_natureza_id', 'fk_aten_natureza_id_idx');
            $table->index('aten_cliente_id', 'fk_aten_cliente_id_idx');
            $table->index('aten_usuario_id', 'fk_aten_usuario_id_idx');

            $table->foreign('aten_cliente_id', 'fk_aten_cliente_id')
                ->references('cli_id')->on('clientes')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('aten_natureza_id', 'fk_aten_natureza_id')
                ->references('nat_aten_id')->on('naturezas_atendimentos')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('aten_usuario_id', 'fk_aten_usuario_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimentos');
    }
};
