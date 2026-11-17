<?php

namespace Tests\Feature;

use App\Enums\AtendimentoStatus;
use App\Enums\NivelAcesso;
use App\Models\Atendimento;
use App\Models\Cliente;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BF08 (2026-11-17): atendimentos no mesmo local (mesmo cliente) viram um
 * único ponto no Mapa de Demandas, com a lista de todos no popup — antes as
 * bolinhas se sobrepunham e só a de cima era visível/clicável.
 */
class MapaDemandasPontosFaeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $tecnico1;
    private Usuario $tecnico2;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tecnico1 = Usuario::factory()->tecnico()->create(['user_nome' => 'Técnico Um']);
        $this->tecnico2 = Usuario::factory()->tecnico()->create(['user_nome' => 'Técnico Dois']);
        $this->cliente = Cliente::factory()->create(['cli_nome' => 'Cliente Compartilhado', 'cli_latitude' => -27.6383921, 'cli_longitude' => -48.7651385]);
        $natureza = NaturezaAtendimento::factory()->create();

        Atendimento::factory()->create([
            'aten_cliente_id' => $this->cliente->cli_id, 'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $this->tecnico1->user_id, 'aten_status' => AtendimentoStatus::Concluida->value,
        ]);
        Atendimento::factory()->create([
            'aten_cliente_id' => $this->cliente->cli_id, 'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $this->tecnico2->user_id, 'aten_status' => AtendimentoStatus::NaoIniciada->value,
        ]);

        $outro = Cliente::factory()->create(['cli_latitude' => -26.9, 'cli_longitude' => -49.06]);
        Atendimento::factory()->create([
            'aten_cliente_id' => $outro->cli_id, 'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $this->tecnico1->user_id, 'aten_status' => AtendimentoStatus::EmAndamento->value,
        ]);
    }

    public function test_web_agrupa_atendimentos_do_mesmo_local_em_um_ponto(): void
    {
        $admin = Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Administrador->value]);

        $response = $this->actingAs($admin)->get(route('mapa-demandas.index'))->assertOk();

        $pontos = $response->viewData('pontos');
        $this->assertCount(2, $pontos);

        $compartilhado = collect($pontos)->firstWhere('total', 2);
        $this->assertSame(['Cliente Compartilhado'], $compartilhado['clientes']);
        // Cor do ponto = a do mais pendente (Não iniciada), não a do concluído.
        $this->assertSame(AtendimentoStatus::NaoIniciada->value, $compartilhado['status']);
        $this->assertSame(['Técnico Dois', 'Técnico Um'], array_column($compartilhado['atendimentos'], 'tecnico'));
    }

    public function test_paralisada_tem_prioridade_sobre_nao_iniciada(): void
    {
        Atendimento::factory()->create([
            'aten_cliente_id' => $this->cliente->cli_id, 'aten_natureza_id' => NaturezaAtendimento::first()->nat_aten_id,
            'aten_usuario_id' => $this->tecnico1->user_id, 'aten_status' => AtendimentoStatus::Paralisada->value,
        ]);
        $admin = Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Administrador->value]);

        $pontos = $this->actingAs($admin)->get(route('mapa-demandas.index'))->viewData('pontos');

        $compartilhado = collect($pontos)->firstWhere('total', 3);
        $this->assertSame(AtendimentoStatus::Paralisada->value, $compartilhado['status']);
    }

    public function test_filtro_de_status_vale_dentro_do_ponto(): void
    {
        $admin = Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Administrador->value]);

        $pontos = $this->actingAs($admin)
            ->get(route('mapa-demandas.index', ['f_status' => AtendimentoStatus::Concluida->value]))
            ->viewData('pontos');

        $this->assertCount(1, $pontos);
        $this->assertSame(1, $pontos[0]['total']);
        $this->assertSame('Técnico Um', $pontos[0]['atendimentos'][0]['tecnico']);
    }

    public function test_link_para_abrir_atendimento_so_com_permissao_de_editar(): void
    {
        $admin = Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Administrador->value]);
        $this->actingAs($admin)->get(route('mapa-demandas.index'))->assertViewHas('podeAbrirAtendimento', true);

        // Técnico (seeder padrão) só tem atendimentos.ver.
        $this->actingAs($this->tecnico1)->get(route('mapa-demandas.index'))->assertViewHas('podeAbrirAtendimento', false);
    }

    public function test_api_devolve_pontos_agrupados_e_mantem_data(): void
    {
        $admin = Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Administrador->value]);
        $token = $admin->createToken('teste')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/fae/v1/mapa-demandas')->assertOk();

        $response->assertJsonCount(3, 'data');
        $response->assertJsonCount(2, 'pontos');
        $compartilhado = collect($response->json('pontos'))->firstWhere('total', 2);
        $this->assertSame(-27.6383921, $compartilhado['latitude']);
        $this->assertSame(AtendimentoStatus::NaoIniciada->value, $compartilhado['status']);
        $this->assertCount(2, $compartilhado['atendimentos']);
    }

    public function test_api_tecnico_ve_so_os_proprios_atendimentos_no_ponto(): void
    {
        $token = $this->tecnico2->createToken('teste')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/fae/v1/mapa-demandas')->assertOk();

        $response->assertJsonCount(1, 'pontos');
        $this->assertSame(1, $response->json('pontos.0.total'));
        $this->assertSame('Técnico Dois', $response->json('pontos.0.atendimentos.0.tecnico'));
    }
}
