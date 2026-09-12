<?php

namespace Tests\Feature;

use App\Enums\NivelAcesso;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Segunda rodada de ajustes visuais pos-apresentacao (2026-09-11): cobre
 * apenas o que ganhou comportamento novo — niveis de acesso Assistencia/
 * Vendedor e o campo cli_link_mapa do Cliente. Ajustes puramente de
 * layout/coluna (col-md-*) e estilo de botao nao tem teste dedicado (nao
 * sao verificaveis via HTTP status/DB).
 */
class AjustesVisuaisRodada2FaeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cadastra_usuario_com_nivel_assistencia(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'user_nivel_acesso' => NivelAcesso::Assistencia->value,
            'user_nome' => 'Fulano Assistência',
            'user_email' => 'assistencia@fae.local',
            'user_senha' => 'senha123',
            'user_senha_confirmation' => 'senha123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('usuarios', [
            'user_email' => 'assistencia@fae.local',
            'user_nivel_acesso' => NivelAcesso::Assistencia->value,
        ]);
    }

    public function test_admin_cadastra_usuario_com_nivel_vendedor(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'user_nivel_acesso' => NivelAcesso::Vendedor->value,
            'user_nome' => 'Fulano Vendedor',
            'user_email' => 'vendedor@fae.local',
            'user_senha' => 'senha123',
            'user_senha_confirmation' => 'senha123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('usuarios', [
            'user_email' => 'vendedor@fae.local',
            'user_nivel_acesso' => NivelAcesso::Vendedor->value,
        ]);
    }

    public function test_cliente_salva_link_do_google_maps(): void
    {
        $comercial = Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Comercial->value]);

        $response = $this->actingAs($comercial)->post(route('clientes.store'), [
            'cli_nome' => 'Cliente Link Mapa',
            'cli_cnpj' => '98765432000188',
            'cli_cidade' => 'Joinville',
            'cli_uf' => 'SC',
            'cli_link_mapa' => 'https://maps.app.goo.gl/xyz789',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clientes', [
            'cli_nome' => 'Cliente Link Mapa',
            'cli_link_mapa' => 'https://maps.app.goo.gl/xyz789',
        ]);
    }

    public function test_link_do_mapa_do_cliente_invalido_e_rejeitado(): void
    {
        $comercial = Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Comercial->value]);

        $response = $this->actingAs($comercial)->post(route('clientes.store'), [
            'cli_nome' => 'Cliente Link Invalido',
            'cli_cnpj' => '11122233000144',
            'cli_cidade' => 'Joinville',
            'cli_uf' => 'SC',
            'cli_link_mapa' => 'nao-e-uma-url',
        ]);

        $response->assertSessionHasErrors('cli_link_mapa');
    }
}