<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Sessao 08 - aten_rel_modelo_relatorio_id era NOT NULL (modelos_relatorios
// era obrigatorio). Agora que o Configurador e obrigatorio no lugar dele
// (ver NaturezaAtendimentoRequest) e o modelo legado passou a ser opcional
// na tela de Natureza, um relatorio criado para uma natureza SEM modelo
// legado precisa poder gravar essa coluna como NULL. Sem doctrine/dbal
// instalado neste projeto, ALTER via SQL bruto em vez de ->change().
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE atendimentos_relatorios MODIFY aten_rel_modelo_relatorio_id INT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE atendimentos_relatorios MODIFY aten_rel_modelo_relatorio_id INT NOT NULL');
    }
};