<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atendimentos_relatorios_assinaturas', function (Blueprint $table) {
            $table->string('aten_rel_ass_tipo', 20)->nullable()->after('aten_rel_ass_path');
        });

        // Migração de dados: inferir tipo a partir do path existente
        DB::table('atendimentos_relatorios_assinaturas')
            ->whereNull('aten_rel_ass_tipo')
            ->get()
            ->each(function ($row) {
                $tipo = null;

                if (str_contains($row->aten_rel_ass_path, '/responsavel.')) {
                    $tipo = 'responsavel';
                } elseif (str_contains($row->aten_rel_ass_path, '/cliente.')) {
                    $tipo = 'cliente';
                }

                if ($tipo) {
                    DB::table('atendimentos_relatorios_assinaturas')
                        ->where('aten_rel_ass_id', $row->aten_rel_ass_id)
                        ->update(['aten_rel_ass_tipo' => $tipo]);
                }
            });

        // Após migração de dados, tornar coluna obrigatória
        Schema::table('atendimentos_relatorios_assinaturas', function (Blueprint $table) {
            $table->string('aten_rel_ass_tipo', 20)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('atendimentos_relatorios_assinaturas', function (Blueprint $table) {
            $table->dropColumn('aten_rel_ass_tipo');
        });
    }
};
