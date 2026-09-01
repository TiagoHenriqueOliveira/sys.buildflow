<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Item 2.8 do plano de correções: colunas de alto tráfego (filtro/ordenação
// em listagens e dashboard) sem índice — cada consulta varria a tabela
// inteira. Nomes de índice explícitos porque as tabelas usam PK/colunas
// nomeadas fora do padrão Laravel (aten_id, aten_rel_id etc.), o que faz o
// nome autogerado por índice ficar comprido; curto e descritivo é mais
// fácil de conferir num EXPLAIN.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atendimentos', function (Blueprint $table) {
            $table->index('aten_status', 'idx_aten_status');
            $table->index('aten_dt_inicio', 'idx_aten_dt_inicio');
            $table->index('aten_dt_fim', 'idx_aten_dt_fim');
        });

        Schema::table('atendimentos_relatorios', function (Blueprint $table) {
            $table->index('aten_rel_status', 'idx_aten_rel_status');
            $table->index('aten_rel_data', 'idx_aten_rel_data');
        });

        Schema::table('logs_auditoria', function (Blueprint $table) {
            $table->index('log_aud_created_at', 'idx_log_aud_created_at');
            $table->index('log_aud_modulo', 'idx_log_aud_modulo');
            $table->index('log_aud_acao', 'idx_log_aud_acao');
        });
    }

    public function down(): void
    {
        Schema::table('atendimentos', function (Blueprint $table) {
            $table->dropIndex('idx_aten_status');
            $table->dropIndex('idx_aten_dt_inicio');
            $table->dropIndex('idx_aten_dt_fim');
        });

        Schema::table('atendimentos_relatorios', function (Blueprint $table) {
            $table->dropIndex('idx_aten_rel_status');
            $table->dropIndex('idx_aten_rel_data');
        });

        Schema::table('logs_auditoria', function (Blueprint $table) {
            $table->dropIndex('idx_log_aud_created_at');
            $table->dropIndex('idx_log_aud_modulo');
            $table->dropIndex('idx_log_aud_acao');
        });
    }
};
