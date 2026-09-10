<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NC01 (cadastro unificado de cliente) + BF01 (geolocalização), ver
// docs/cronograma/02-web-nucleo-telas.md. Campos aditivos, todos nullable —
// nenhum dado existente é afetado. Defaults de pendências do cliente
// (segmento texto livre, classificação vazia, alerta por cliente) fechados
// em docs/cronograma/01-web-preparacao.md.
//
// cli_status é só exibição nesta etapa (fluxo de aprovação de pré-cadastro
// fica para a sessão de persistência) — default 1 (Aprovado) para não
// impactar os clientes já cadastrados.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('cli_contato_principal', 100)
                ->charset('utf8mb3')->collation('utf8mb3_unicode_ci')
                ->nullable()->after('cli_nome');

            $table->integer('cli_vendedor_id')->nullable()->after('cli_contato_principal');

            $table->string('cli_inscricao_estadual', 20)
                ->charset('utf8mb3')->collation('utf8mb3_unicode_ci')
                ->nullable()->after('cli_cnpj');

            $table->string('cli_segmento', 255)
                ->charset('utf8mb3')->collation('utf8mb3_unicode_ci')
                ->nullable()->after('cli_uf');

            $table->integer('cli_classificacao_id')->nullable()->after('cli_segmento');

            $table->integer('cli_dias_alerta_recontato')->nullable()->after('cli_classificacao_id');

            $table->tinyInteger('cli_status')->default(1)
                ->comment("0 - Pré-cadastro\n1 - Aprovado")
                ->after('cli_ativo');

            $table->decimal('cli_latitude', 10, 7)->nullable()->after('cli_status');
            $table->decimal('cli_longitude', 10, 7)->nullable()->after('cli_latitude');

            $table->index('cli_vendedor_id', 'fk_cli_vendedor_id_idx');
            $table->index('cli_classificacao_id', 'fk_cli_classificacao_id_idx');

            $table->foreign('cli_vendedor_id', 'fk_cli_vendedor_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('set null')->onUpdate('cascade');

            $table->foreign('cli_classificacao_id', 'fk_cli_classificacao_id')
                ->references('cla_cli_id')->on('classificacoes_cliente')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign('fk_cli_vendedor_id');
            $table->dropForeign('fk_cli_classificacao_id');
            $table->dropColumn([
                'cli_contato_principal',
                'cli_vendedor_id',
                'cli_inscricao_estadual',
                'cli_segmento',
                'cli_classificacao_id',
                'cli_dias_alerta_recontato',
                'cli_status',
                'cli_latitude',
                'cli_longitude',
            ]);
        });
    }
};
