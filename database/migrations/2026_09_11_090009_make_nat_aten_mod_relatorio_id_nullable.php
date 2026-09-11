<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Sessao 08 - mesma razao da migration 2026_09_11_090008 (atendimentos_
// relatorios.aten_rel_modelo_relatorio_id): nat_aten_mod_relatorio_id
// passou a ser opcional no formulario (NaturezaAtendimentoRequest), mas a
// coluna em si ainda era NOT NULL. Sem doctrine/dbal, ALTER via SQL bruto.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE naturezas_atendimentos MODIFY nat_aten_mod_relatorio_id INT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE naturezas_atendimentos MODIFY nat_aten_mod_relatorio_id INT NOT NULL');
    }
};