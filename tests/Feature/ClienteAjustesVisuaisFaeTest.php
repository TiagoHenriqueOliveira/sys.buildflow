<?php

namespace Tests\Feature;

use App\Enums\NivelAcesso;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ajustes visuais pos-apresentacao ao cliente (2026-09-11): cobre apenas o
 * que ganhou comportamento novo nesta rodada — edicao de cliente permanece
 * na propria tela (nao redireciona pra listagem), e as abas Historico
 * (equipamentos) e Geolocalizacao (localizacoes) passam de campo unico
 * para lista repetivel, com `cli_equipamento_vendido` (CRM07) sincronizado
 * como resumo a partir da lista de equipamentos.
 */
class ClienteAjustesVisuaisFaeTest extends TestCase
{
    use RefreshDatabase;

    private function criarComercial(): Usuario
    {
        return Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Comercial->value]);
    }

    private function payloadBase(): array
    {
        return [
            'cli_nome' => 'Cliente Teste Ajustes',
            'cli_cnpj' => '12345678000199',
            'cli_cidade' => 'Blumenau',
            'cli_uf' => 'SC',
        ];
    }

    public function test_cadastro_novo_de_cliente_redireciona_para_listagem(): void
    {
        $usuario = $this->criarComercial();

        $response = $this->actingAs($usuario)->post(route('clientes.store'), $this->payloadBase());

        $response->assertRedirect(route('clientes.index'));
    }

    public function test_edicao_de_cliente_permanece_na_tela_de_edicao(): void
    {
        $usuario = $this->criarComercial();
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($usuario)->put(route('clientes.update', $cliente->cli_id), [
            ...$this->payloadBase(),
            'cli_cnpj' => $cliente->cli_cnpj,
        ]);

        $response->assertRedirect(route('clientes.edit', $cliente->cli_id));
    }

    public function test_cadastra_varios_equipamentos_e_sincroniza_resumo_legado(): void
    {
        $usuario = $this->criarComercial();

        $response = $this->actingAs($usuario)->post(route('clientes.store'), [
            ...$this->payloadBase(),
            'equipamentos' => [
                ['descricao' => 'ETE compacta 50m3/dia'],
                ['descricao' => 'Sistema de desague de lodo'],
            ],
        ]);

        $response->assertRedirect();
        $cliente = Cliente::first();
        $this->assertCount(2, $cliente->equipamentos);
        $this->assertSame('ETE compacta 50m3/dia, Sistema de desague de lodo', $cliente->cli_equipamento_vendido);
    }

    public function test_atualizar_equipamentos_substitui_lista_anterior(): void
    {
        $usuario = $this->criarComercial();
        $cliente = Cliente::factory()->create();
        $cliente->equipamentos()->create(['cli_equip_descricao' => 'Equipamento antigo']);

        $response = $this->actingAs($usuario)->put(route('clientes.update', $cliente->cli_id), [
            ...$this->payloadBase(),
            'cli_cnpj' => $cliente->cli_cnpj,
            'equipamentos' => [
                ['descricao' => 'Equipamento novo'],
            ],
        ]);

        $response->assertRedirect();
        $cliente->refresh();
        $this->assertCount(1, $cliente->equipamentos);
        $this->assertSame('Equipamento novo', $cliente->equipamentos->first()->cli_equip_descricao);
        $this->assertSame('Equipamento novo', $cliente->cli_equipamento_vendido);
    }

    public function test_cadastra_varias_localizacoes_do_cliente(): void
    {
        $usuario = $this->criarComercial();

        $response = $this->actingAs($usuario)->post(route('clientes.store'), [
            ...$this->payloadBase(),
            'localizacoes' => [
                ['descricao' => 'Empresa', 'latitude' => -26.9194, 'longitude' => -49.0661],
                ['descricao' => 'Instalação/Montagem', 'latitude' => -26.9, 'longitude' => -49.0],
            ],
        ]);

        $response->assertRedirect();
        $cliente = Cliente::first();
        $this->assertCount(2, $cliente->localizacoes);
        $this->assertSame('Empresa', $cliente->localizacoes->first()->cli_loc_descricao);
    }

    public function test_localizacao_sem_coordenadas_e_ignorada(): void
    {
        $usuario = $this->criarComercial();

        $response = $this->actingAs($usuario)->post(route('clientes.store'), [
            ...$this->payloadBase(),
            'localizacoes' => [
                ['descricao' => 'Sem ponto no mapa ainda', 'latitude' => '', 'longitude' => ''],
            ],
        ]);

        $response->assertRedirect();
        $cliente = Cliente::first();
        $this->assertCount(0, $cliente->localizacoes);
    }
}