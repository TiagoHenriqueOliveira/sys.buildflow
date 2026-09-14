<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Pedido do cliente (2026-09-14): removido o "Escolher no mapa" (busca +
// clique num Leaflet embutido) de todo o sistema — a busca nunca vai
// igualar a experiencia do app do Google, e o botao "Abrir no Google Maps"
// derivado de lat/lng tambem foi removido por nao servir pra nada na
// pratica. Cada localizacao passa a ser definida so por descricao + link
// do Google Maps colado (mesmo padrao ja usado no Roteiro de Viagem) - as
// coordenadas deixam de ser coletadas no formulario, entao a coluna
// precisa aceitar NULL (doctrine/dbal nao esta instalado, sem
// ->nullable()->change(), por isso SQL bruto).
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE clientes_localizacoes MODIFY cli_loc_latitude DECIMAL(10,7) NULL');
        DB::statement('ALTER TABLE clientes_localizacoes MODIFY cli_loc_longitude DECIMAL(10,7) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE clientes_localizacoes MODIFY cli_loc_latitude DECIMAL(10,7) NOT NULL');
        DB::statement('ALTER TABLE clientes_localizacoes MODIFY cli_loc_longitude DECIMAL(10,7) NOT NULL');
    }
};