<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pedido do cliente (2026-09-17): filtros aplicados em qualquer tela devem
 * ser lembrados por usuario logado, sem precisar preencher de novo -
 * App\Http\Controllers\Concerns\PersisteFiltros, aplicado a todas as
 * controllers com filtro de listagem. Teste usa Clientes como amostra
 * representativa (mesmo mecanismo genérico vale para as outras 15 telas).
 */
class FiltrosPersistentesFaeTest extends TestCase
{
    use RefreshDatabase;

    public function test_filtro_aplicado_e_lembrado_na_proxima_visita_sem_querystring(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        Cliente::factory()->create(['cli_nome' => 'Alfa Indústria']);
        Cliente::factory()->create(['cli_nome' => 'Beta Comércio']);

        // Aplica o filtro (com querystring) - deve salvar e já filtrar.
        $primeira = $this->actingAs($admin)->get(route('clientes.index', ['f_nome' => 'Alfa']));
        $primeira->assertSee('Alfa Indústria');
        $primeira->assertDontSee('Beta Comércio');

        // Visita "limpa" (sem querystring nenhuma) - deve reaplicar o
        // filtro salvo automaticamente, sem o usuário preencher de novo.
        $segunda = $this->actingAs($admin)->get(route('clientes.index'));
        $segunda->assertSee('Alfa Indústria');
        $segunda->assertDontSee('Beta Comércio');
        $segunda->assertViewHas('filtroNome', 'Alfa');
    }

    public function test_filtro_salvo_e_isolado_por_usuario(): void
    {
        $admin1 = Usuario::factory()->administrador()->create();
        $admin2 = Usuario::factory()->administrador()->create();
        Cliente::factory()->create(['cli_nome' => 'Alfa Indústria']);
        Cliente::factory()->create(['cli_nome' => 'Beta Comércio']);

        $this->actingAs($admin1)->get(route('clientes.index', ['f_nome' => 'Alfa']));

        // admin2 nunca filtrou - visita limpa deve mostrar todos, sem herdar
        // o filtro salvo do admin1.
        $response = $this->actingAs($admin2)->get(route('clientes.index'));
        $response->assertSee('Alfa Indústria');
        $response->assertSee('Beta Comércio');
        $response->assertViewHas('filtroNome', '');
    }

    public function test_limpar_filtro_persiste_como_vazio(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        Cliente::factory()->create(['cli_nome' => 'Alfa Indústria']);

        $this->actingAs($admin)->get(route('clientes.index', ['f_nome' => 'Alfa']));

        // Envia o campo vazio explicitamente ("Limpar filtro") - deve
        // sobrescrever o valor salvo, não manter o antigo.
        $this->actingAs($admin)->get(route('clientes.index', ['f_nome' => '']));

        $response = $this->actingAs($admin)->get(route('clientes.index'));
        $response->assertViewHas('filtroNome', '');
    }
}