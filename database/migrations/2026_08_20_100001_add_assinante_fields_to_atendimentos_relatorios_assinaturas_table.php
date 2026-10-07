<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// RF006 — nome (obrigatório) e CPF (opcional) de quem assinou o relatório,
// em vez de assumir o responsável cadastrado no atendimento. Coluna
// nome não pode ser NOT NULL direto numa tabela com dados existentes
// (linhas antigas não têm valor) — adiciona nullable e a aplicação passa a
// exigir preenchimento em toda assinatura NOVA a partir de agora (ver
// RF006 no roteiro técnico: validação fica no controller, não no banco).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atendimentos_relatorios_assinaturas', function (Blueprint $table) {
            $table->string('aten_rel_ass_nome', 100)
                ->charset('utf8mb3')->collation('utf8mb3_unicode_ci')
                ->nullable()
                ->after('aten_rel_ass_tipo');
            $table->string('aten_rel_ass_cpf', 14)
                ->charset('utf8mb3')->collation('utf8mb3_unicode_ci')
                ->nullable()
                ->after('aten_rel_ass_nome');
        });
    }

    public function down(): void
    {
        Schema::table('atendimentos_relatorios_assinaturas', function (Blueprint $table) {
            $table->dropColumn(['aten_rel_ass_nome', 'aten_rel_ass_cpf']);
        });
    }
};
