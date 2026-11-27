<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Auditoria 27/11/2026, S-04: /midia/{path} servia qualquer arquivo a
 * qualquer usuário logado. Agora segue a posse do atendimento (mesma regra
 * das telas), pela sessão web e pelo token do app.
 */
class MidiaPosseFaeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $tecnicoA;
    private string $fotoDoA;
    private string $anexoDoA;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->tecnicoA = Usuario::factory()->tecnico()->create();
        $atendimento = Atendimento::factory()->create(['aten_usuario_id' => $this->tecnicoA->user_id]);
        $relatorio = AtendimentoRelatorio::factory()->for($atendimento, 'atendimento')->create();

        $this->fotoDoA = "atendimentos_relatorios/{$relatorio->aten_rel_id}/assinaturas/cliente.png";
        $this->anexoDoA = "atendimentos/{$atendimento->aten_id}/anexos/contrato.pdf";
        Storage::disk('public')->put($this->fotoDoA, 'png');
        Storage::disk('public')->put($this->anexoDoA, 'pdf');
        Storage::disk('public')->put('outros/arquivo.txt', 'x');
    }

    /** Troca de usuário entre requisições: o guard guarda o usuário da anterior. */
    private function token(Usuario $usuario): string
    {
        $this->app['auth']->forgetGuards();

        return $usuario->createToken('test')->plainTextToken;
    }

    public function test_tecnico_b_nao_baixa_arquivo_do_atendimento_do_tecnico_a(): void
    {
        $tecnicoB = Usuario::factory()->tecnico()->create();

        $this->withToken($this->token($tecnicoB))->getJson('/midia/' . $this->fotoDoA)
            ->assertForbidden()->assertJsonPath('codigo', 'sem_acesso_arquivo');
        $this->withToken($this->token($tecnicoB))->getJson('/midia/' . $this->anexoDoA)->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->actingAs($tecnicoB)->get('/midia/' . $this->fotoDoA)->assertForbidden();
    }

    public function test_dono_administrador_e_assistencia_baixam(): void
    {
        $this->withToken($this->token($this->tecnicoA))->get('/midia/' . $this->fotoDoA)->assertOk();
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->tecnicoA)->get('/midia/' . $this->anexoDoA)->assertOk();

        $admin = Usuario::factory()->administrador()->create();
        $this->withToken($this->token($admin))->get('/midia/' . $this->fotoDoA)->assertOk();

        // Assistência aprova os relatórios de todos (Atendimento::veTodos).
        $assistencia = Usuario::factory()->assistencia()->create();
        $this->withToken($this->token($assistencia))->get('/midia/' . $this->fotoDoA)->assertOk();
    }

    public function test_vendedor_nao_baixa_arquivo_de_relatorio(): void
    {
        $vendedor = Usuario::factory()->vendedor()->create();

        $this->withToken($this->token($vendedor))->getJson('/midia/' . $this->fotoDoA)->assertForbidden();
    }

    public function test_arquivo_fora_das_pastas_conhecidas_so_com_acesso_total(): void
    {
        $this->withToken($this->token($this->tecnicoA))->getJson('/midia/outros/arquivo.txt')->assertForbidden();

        $admin = Usuario::factory()->administrador()->create();
        $this->withToken($this->token($admin))->get('/midia/outros/arquivo.txt')->assertOk();
    }

    public function test_caminho_com_subida_de_pasta_da_404(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->withToken($this->token($admin))->get('/midia/atendimentos/1/../../.env')->assertNotFound();
    }
}
