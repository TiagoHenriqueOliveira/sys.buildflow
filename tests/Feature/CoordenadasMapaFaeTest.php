<?php

namespace Tests\Feature;

use App\Enums\NivelAcesso;
use App\Models\Atendimento;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Support\CoordenadasMapa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 2026-11-17: cliente com link do Google Maps colado (único campo de
 * localização da tela desde 2026-09-14) não aparecia no Mapa de Demandas,
 * porque os mapas usam latitude/longitude e elas só eram gravadas pelo
 * botão "Usar minha localização". Agora as coordenadas são derivadas do link.
 */
class CoordenadasMapaFaeTest extends TestCase
{
    use RefreshDatabase;

    private const LINK_PLACE = 'https://www.google.com/maps/place/Canto+Da+Amizade/@-27.6383873,-48.770004,17z/data=!3m1!4b1!4m6!3m5!1s0x9520c9e455818b67:0x3087d32f6fb232c6!8m2!3d-27.6383921!4d-48.7651385!16s%2Fg%2F11k3lt1b7m?entry=ttu';

    public function test_extrai_pino_do_lugar_em_vez_do_centro_da_camera(): void
    {
        $this->assertSame(['lat' => -27.6383921, 'lng' => -48.7651385], CoordenadasMapa::extrair(self::LINK_PLACE));
    }

    public function test_extrai_outros_formatos(): void
    {
        $this->assertSame(['lat' => -27.687419, 'lng' => -48.8172039], CoordenadasMapa::extrair('https://www.google.com/maps/@-27.687419,-48.8172039,86a,37.1y'));
        $this->assertSame(['lat' => -27.1004, 'lng' => -52.6152], CoordenadasMapa::extrair('https://maps.google.com/?q=-27.1004,-52.6152'));
        $this->assertSame(['lat' => -26.3, 'lng' => -48.84], CoordenadasMapa::extrair('https://www.google.com/maps/search/-26.3,+-48.84'));
        $this->assertNull(CoordenadasMapa::extrair('https://www.google.com/maps/place/Blumenau,+SC'));
    }

    public function test_link_curto_e_expandido_pelo_redirecionamento(): void
    {
        Http::fake([
            'maps.app.goo.gl/*' => Http::response('', 302, ['Location' => self::LINK_PLACE]),
        ]);

        $this->assertSame(['lat' => -27.6383921, 'lng' => -48.7651385], CoordenadasMapa::doLink('https://maps.app.goo.gl/AbCdEf123'));
    }

    public function test_link_curto_nao_segue_redirecionamento_para_fora_do_google(): void
    {
        Http::fake([
            'maps.app.goo.gl/*' => Http::response('', 302, ['Location' => 'https://exemplo.com/@-27.1,-52.6']),
        ]);

        $this->assertNull(CoordenadasMapa::doLink('https://maps.app.goo.gl/AbCdEf123'));
    }

    public function test_salvar_cliente_com_link_grava_coordenadas_e_aparece_no_mapa_de_demandas(): void
    {
        $admin = Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Administrador->value]);

        $this->actingAs($admin)->post(route('clientes.store'), [
            'cli_nome' => 'Frigorífico Teste',
            'cli_cnpj' => '12345678000199',
            'cli_cidade' => 'Santo Amaro da Imperatriz',
            'cli_uf' => 'SC',
            'cli_link_mapa' => self::LINK_PLACE,
        ])->assertRedirect();

        $cliente = Cliente::where('cli_cnpj', '12345678000199')->firstOrFail();
        $this->assertSame(-27.6383921, $cliente->cli_latitude);
        $this->assertSame(-48.7651385, $cliente->cli_longitude);

        Atendimento::factory()->create(['aten_cliente_id' => $cliente->cli_id]);

        $this->actingAs($admin)->get(route('mapa-demandas.index'))
            ->assertOk()
            ->assertSee('-27.6383921', false);
    }

    public function test_trocar_ou_remover_o_link_atualiza_as_coordenadas(): void
    {
        $admin = Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Administrador->value]);
        $cliente = Cliente::factory()->create(['cli_latitude' => -10, 'cli_longitude' => -50, 'cli_link_mapa' => 'https://maps.google.com/?q=-10,-50']);
        $base = ['cli_nome' => $cliente->cli_nome, 'cli_cnpj' => $cliente->cli_cnpj, 'cli_cidade' => 'Blumenau', 'cli_uf' => 'SC'];

        $this->actingAs($admin)->put(route('clientes.update', $cliente->cli_id), [...$base, 'cli_link_mapa' => 'https://maps.google.com/?q=-27.1004,-52.6152']);
        $cliente->refresh();
        $this->assertSame([-27.1004, -52.6152], [$cliente->cli_latitude, $cliente->cli_longitude]);

        $this->actingAs($admin)->put(route('clientes.update', $cliente->cli_id), [...$base, 'cli_link_mapa' => '']);
        $cliente->refresh();
        $this->assertNull($cliente->cli_latitude);
        $this->assertNull($cliente->cli_link_mapa);
    }

    public function test_coordenadas_sem_link_geram_o_link(): void
    {
        // Compatibilidade com o app: "usar minha localização" envia só lat/lng.
        $admin = Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Administrador->value]);

        $this->actingAs($admin)->post(route('clientes.store'), [
            'cli_nome' => 'Cliente GPS',
            'cli_cnpj' => '98765432000111',
            'cli_cidade' => 'Blumenau',
            'cli_uf' => 'SC',
            'cli_latitude' => -26.9194,
            'cli_longitude' => -49.0661,
        ])->assertRedirect();

        $cliente = Cliente::where('cli_cnpj', '98765432000111')->firstOrFail();
        $this->assertSame('https://www.google.com/maps?q=-26.9194,-49.0661', $cliente->cli_link_mapa);
    }

    public function test_tela_avisa_quando_o_link_nao_tem_posicao(): void
    {
        $admin = Usuario::factory()->create(['user_nivel_acesso' => NivelAcesso::Administrador->value]);
        $cliente = Cliente::factory()->create(['cli_latitude' => null, 'cli_longitude' => null, 'cli_link_mapa' => 'https://www.google.com/maps/place/Blumenau,+SC']);

        $this->actingAs($admin)->get(route('clientes.edit', $cliente->cli_id))
            ->assertOk()
            ->assertSee('Não foi possível identificar a posição neste link');
    }
}
