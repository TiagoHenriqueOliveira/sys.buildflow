<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Auditoria 27/11/2026 (S-14) / contrato fae/v1 §6: versão que sobe a cada
// gravação de cliente, orçamento e roteiro (If-Match → 409 conflito_versao).
// Registros existentes começam em 0.
return new class extends Migration
{
    private const COLUNAS = [
        'clientes' => ['cli_versao', 'cli_ativo'],
        'orcamentos' => ['orc_versao', 'orc_ativo'],
        'crm_roteiros_viagem' => ['crm_rot_versao', 'crm_rot_status'],
    ];

    public function up(): void
    {
        foreach (self::COLUNAS as $tabela => [$coluna, $depois]) {
            Schema::table($tabela, function (Blueprint $table) use ($coluna, $depois) {
                $table->unsignedInteger($coluna)->default(0)->after($depois);
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUNAS as $tabela => [$coluna]) {
            Schema::table($tabela, function (Blueprint $table) use ($coluna) {
                $table->dropColumn($coluna);
            });
        }
    }
};
