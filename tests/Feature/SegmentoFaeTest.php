<?php

namespace Tests\Feature;

use App\Models\Segmento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pedido do cliente (2026-09-17) — modulo "Segmentos" em Configuracoes,
 * mesmo padrao de ClassificacaoClienteFaeTest.
 */
class SegmentoFaeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cadastra_segmento(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($admin)->post(route('segmentos.store'), [
            'seg_descricao' => 'Sucroenergético',
        ]);

        $response->assertRedirect(route('segmentos.index'));
        $this->assertDatabaseHas('segmentos', [
            'seg_descricao' => 'Sucroenergético',
            'seg_ativo' => 1,
        ]);
    }

    public function test_nao_permite_descricoes_duplicadas(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        Segmento::factory()->create(['seg_descricao' => 'Alimentício']);

        $response = $this->actingAs($admin)->post(route('segmentos.store'), [
            'seg_descricao' => 'Alimentício',
        ]);

        $response->assertSessionHasErrors('seg_descricao');
    }

    public function test_admin_atualiza_e_inativa_segmento(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $segmento = Segmento::factory()->create(['seg_descricao' => 'Têxtil']);

        $response = $this->actingAs($admin)->put(route('segmentos.update', $segmento->seg_id), [
            'seg_descricao' => 'Têxtil - revisado',
            'seg_ativo' => false,
        ]);

        $response->assertRedirect(route('segmentos.index'));
        $this->assertDatabaseHas('segmentos', [
            'seg_id' => $segmento->seg_id,
            'seg_descricao' => 'Têxtil - revisado',
            'seg_ativo' => 0,
        ]);
    }

    public function test_tecnico_nao_acessa_segmentos(): void
    {
        $tecnico = Usuario::factory()->create();

        $this->actingAs($tecnico)->get(route('segmentos.index'))->assertForbidden();
    }

    public function test_cadastro_de_cliente_lista_segmentos_ativos(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        Segmento::factory()->create(['seg_descricao' => 'Sucroenergético', 'seg_ativo' => true]);
        Segmento::factory()->create(['seg_descricao' => 'Descontinuado', 'seg_ativo' => false]);

        $response = $this->actingAs($admin)->get(route('clientes.create'));

        $response->assertOk();
        $response->assertSee('Sucroenergético');
        $response->assertDontSee('Descontinuado');
    }
}