<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Pedido do cliente (2026-09-11): substitui o campo booleano
// crm_rot_ativo (sem uso real - so tingia a linha na listagem) por um
// status de viagem de verdade (Nao iniciada/Em andamento/Concluida/
// Cancelada - ver App\Enums\StatusRoteiroViagem). Roteiro que estava
// marcado inativo vira Cancelada no backfill, pra preservar a unica
// distincao que o campo antigo realmente fazia.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_roteiros_viagem', function (Blueprint $table) {
            $table->tinyInteger('crm_rot_status')->default(0)->after('crm_rot_link_mapa');
        });

        DB::table('crm_roteiros_viagem')->where('crm_rot_ativo', 0)->update(['crm_rot_status' => 3]);

        Schema::table('crm_roteiros_viagem', function (Blueprint $table) {
            $table->dropColumn('crm_rot_ativo');
        });
    }

    public function down(): void
    {
        Schema::table('crm_roteiros_viagem', function (Blueprint $table) {
            $table->tinyInteger('crm_rot_ativo')->default(1)->after('crm_rot_link_mapa');
        });

        DB::table('crm_roteiros_viagem')->where('crm_rot_status', 3)->update(['crm_rot_ativo' => 0]);

        Schema::table('crm_roteiros_viagem', function (Blueprint $table) {
            $table->dropColumn('crm_rot_status');
        });
    }
};