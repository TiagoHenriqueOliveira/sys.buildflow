<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-11-03): um mesmo relatório pode cobrir o período
// inteiro do atendimento, então precisa registrar os horários de CADA dia
// (data + entrada/intervalo/saída), não mais uma linha única por relatório.
// O horário existente de cada relatório vira o horário do dia do próprio
// relatório (aten_rel_data). A coluna fica nullable só por compatibilidade
// com os controllers legados (Api\RelatoriosController / Api\Mcl), que ainda
// gravam sem data; o fluxo da FAÉ (web + api/fae/v1) sempre informa a data.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atendimentos_relatorios_horarios', function (Blueprint $table) {
            $table->date('aten_rel_hora_data')->nullable()->after('aten_rel_hora_relatorio_id');
        });

        DB::statement('
            UPDATE atendimentos_relatorios_horarios
            SET aten_rel_hora_data = (
                SELECT r.aten_rel_data FROM atendimentos_relatorios r
                WHERE r.aten_rel_id = atendimentos_relatorios_horarios.aten_rel_hora_relatorio_id
            )
        ');

        Schema::table('atendimentos_relatorios_horarios', function (Blueprint $table) {
            $table->dropUnique('uq_aten_rel_hora_relatorio');
            $table->unique(['aten_rel_hora_relatorio_id', 'aten_rel_hora_data'], 'uq_aten_rel_hora_relatorio_data');
        });
    }

    public function down(): void
    {
        // Voltar a 1 linha por relatório exige descartar os dias extras —
        // mantém o primeiro dia (menor id) de cada relatório.
        DB::statement('
            DELETE h FROM atendimentos_relatorios_horarios h
            JOIN atendimentos_relatorios_horarios h2
              ON h2.aten_rel_hora_relatorio_id = h.aten_rel_hora_relatorio_id
             AND h2.aten_rel_hora_id < h.aten_rel_hora_id
        ');

        Schema::table('atendimentos_relatorios_horarios', function (Blueprint $table) {
            $table->dropUnique('uq_aten_rel_hora_relatorio_data');
            $table->unique('aten_rel_hora_relatorio_id', 'uq_aten_rel_hora_relatorio');
            $table->dropColumn('aten_rel_hora_data');
        });
    }
};
