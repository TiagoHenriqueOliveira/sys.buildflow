<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->boolean('user_protegido')->default(false)->after('user_ativo');
        });

        // Marcar o admin master como protegido
        DB::table('usuarios')
            ->where('user_email', 'admin@admin.com')
            ->update(['user_protegido' => true]);
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('user_protegido');
        });
    }
};
