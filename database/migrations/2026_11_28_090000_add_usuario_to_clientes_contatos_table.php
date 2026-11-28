<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Auditoria 27/11/2026 (S-03, decisão D-01): o contato guarda quem o
// cadastrou. Contato Comercial só aparece para o autor, para o vendedor
// responsável do cliente, para o perfil Comercial e para quem tem acesso
// total. Contatos já existentes ficam sem autor (nulo).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes_contatos', function (Blueprint $table) {
            $table->integer('cli_cont_usuario_id')->nullable()->after('cli_cont_tipo');

            $table->index('cli_cont_usuario_id', 'fk_cli_cont_usuario_id_idx');
            $table->foreign('cli_cont_usuario_id', 'fk_cli_cont_usuario_id')
                ->references('user_id')->on('usuarios')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('clientes_contatos', function (Blueprint $table) {
            $table->dropForeign('fk_cli_cont_usuario_id');
            $table->dropIndex('fk_cli_cont_usuario_id_idx');
            $table->dropColumn('cli_cont_usuario_id');
        });
    }
};
