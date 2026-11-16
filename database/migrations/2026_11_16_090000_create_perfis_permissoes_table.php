<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pedido do cliente (2026-11-16): acessos por perfil configuráveis no
// cadastro "Perfis de Acesso". Uma linha = permissão concedida ao nível
// (App\Enums\NivelAcesso, 1..4 — Administrador não precisa de linhas, tem
// acesso total automático). Chaves em App\Support\Permissoes.
// Nasce vazia de propósito (decisão do cliente): os perfis começam sem
// acesso até um administrador configurá-los. Para reproduzir o acesso que
// existia antes: php artisan db:seed --class=PermissoesPadraoSeeder
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfis_permissoes', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_unicode_ci';

            $table->integer('perf_perm_id')->autoIncrement();
            $table->integer('perf_perm_nivel')->comment('NivelAcesso: 1 Tecnico | 2 Comercial | 3 Assistencia | 4 Vendedor');
            $table->string('perf_perm_chave', 60)->comment('App\Support\Permissoes, ex.: orcamentos.ver');

            $table->unique(['perf_perm_nivel', 'perf_perm_chave'], 'uq_perf_perm_nivel_chave');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfis_permissoes');
    }
};
