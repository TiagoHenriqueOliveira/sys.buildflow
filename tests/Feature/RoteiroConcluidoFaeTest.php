<?php

namespace Tests\Feature;

use App\Enums\NivelAcesso;
use App\Enums\StatusRoteiroViagem;
use App\Models\Cliente;
use App\Models\PerfilPermissao;
use App\Models\RoteiroViagem;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pedido do cliente (2026-11-27): roteiro concluído não permite edição
 * (inclusive da situação); só Administrador/Comercial reabrem.
 */
class RoteiroConcluidoFaeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $vendedor;
    private RoteiroViagem $roteiro;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        PerfilPermissao::sincronizar(NivelAcesso::Vendedor->value, ['roteiros.ver', 'roteiros.editar', 'clientes.ver', 'vendedor']);
        $this->vendedor = Usuario::factory()->vendedor()->create();
        $this->cliente = Cliente::factory()->create(['cli_vendedor_id' => $this->vendedor->user_id]);
        $this->roteiro = RoteiroViagem::create([
            'crm_rot_vendedor_id' => $this->vendedor->user_id,
            'crm_rot_periodo_inicio' => '2026-12-01',
            'crm_rot_periodo_fim' => '2026-12-03',
            'crm_rot_status' => StatusRoteiroViagem::Concluida->value,
        ]);
        $this->roteiro->clientes()->create(['crm_rot_cli_cliente_id' => $this->cliente->cli_id, 'crm_rot_cli_ordem' => 0]);
    }

    private function payload(array $extra = []): array
    {
        return [
            'crm_rot_vendedor_id' => $this->vendedor->user_id,
            'crm_rot_periodo_inicio' => '2026-12-01',
            'crm_rot_periodo_fim' => '2026-12-05',
            'crm_rot_status' => StatusRoteiroViagem::EmAndamento->value,
            'clientes' => [$this->cliente->cli_id],
            ...$extra,
        ];
    }

    public function test_roteiro_concluido_nao_aceita_edicao_nem_da_situacao(): void
    {
        $this->actingAs($this->vendedor)
            ->put(route('roteiros-viagem.update', $this->roteiro->crm_rot_id), $this->payload())
            ->assertSessionHasErrors('roteiro');

        $this->roteiro->refresh();
        $this->assertSame(StatusRoteiroViagem::Concluida, $this->roteiro->crm_rot_status);
        $this->assertSame('2026-12-03', $this->roteiro->crm_rot_periodo_fim->format('Y-m-d'));
    }

    public function test_tela_de_roteiro_concluido_fica_somente_leitura(): void
    {
        $this->actingAs($this->vendedor)->get(route('roteiros-viagem.edit', $this->roteiro->crm_rot_id))
            ->assertOk()
            ->assertSee('somente leitura')
            ->assertSee('<fieldset disabled', false)
            ->assertDontSee('Reabrir roteiro com a situação');
    }

    public function test_vendedor_nao_reabre_roteiro(): void
    {
        $this->actingAs($this->vendedor)
            ->patch(route('roteiros-viagem.reabrir', $this->roteiro->crm_rot_id), ['crm_rot_status' => StatusRoteiroViagem::EmAndamento->value])
            ->assertForbidden();
    }

    public function test_comercial_reabre_e_a_edicao_volta(): void
    {
        $comercial = Usuario::factory()->comercial()->create();

        $this->actingAs($comercial)->get(route('roteiros-viagem.edit', $this->roteiro->crm_rot_id))
            ->assertSee('Reabrir roteiro com a situação');

        $this->actingAs($comercial)
            ->patch(route('roteiros-viagem.reabrir', $this->roteiro->crm_rot_id), ['crm_rot_status' => StatusRoteiroViagem::EmAndamento->value])
            ->assertRedirect(route('roteiros-viagem.edit', $this->roteiro->crm_rot_id));
        $this->assertSame(StatusRoteiroViagem::EmAndamento, $this->roteiro->refresh()->crm_rot_status);

        $this->actingAs($this->vendedor)
            ->put(route('roteiros-viagem.update', $this->roteiro->crm_rot_id), $this->payload())
            ->assertSessionHasNoErrors();
        $this->assertSame('2026-12-05', $this->roteiro->refresh()->crm_rot_periodo_fim->format('Y-m-d'));
    }

    public function test_reabrir_nao_aceita_situacao_concluida(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->actingAs($admin)
            ->patch(route('roteiros-viagem.reabrir', $this->roteiro->crm_rot_id), ['crm_rot_status' => StatusRoteiroViagem::Concluida->value])
            ->assertSessionHasErrors('crm_rot_status');
    }

    public function test_api_recusa_editar_roteiro_concluido(): void
    {
        $token = $this->vendedor->createToken('t')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/fae/v1/roteiros-viagem/' . $this->roteiro->crm_rot_id, $this->payload())
            ->assertStatus(422);
    }
}
