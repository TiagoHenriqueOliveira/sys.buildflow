<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CRM07 - dados exibidos no popup do mapa de relacoes de clientes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('cli_equipamento_vendido', 255)
                ->charset('utf8mb3')->collation('utf8mb3_unicode_ci')
                ->nullable()->after('cli_segmento');
            $table->boolean('cli_caso_sucesso')->default(false)->after('cli_equipamento_vendido');
            $table->text('cli_caso_sucesso_descricao')->nullable()->after('cli_caso_sucesso');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['cli_equipamento_vendido', 'cli_caso_sucesso', 'cli_caso_sucesso_descricao']);
        });
    }
};