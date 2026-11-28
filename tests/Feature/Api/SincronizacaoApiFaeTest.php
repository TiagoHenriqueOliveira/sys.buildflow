<?php

namespace Tests\Feature\Api;

use App\Enums\NivelOrcamento;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Auditoria 27/11/2026, S-14 (lado do servidor) / contrato fae/v1 §3, §4 e §6:
 * idempotência dos POST, versão + If-Match, PUT parcial e X-App-Version.
 * Tudo atrás de flags desligadas: o app publicado continua funcionando.
 */
class SincronizacaoApiFaeTest extends TestCase
{
    use RefreshDatabase;

    private const CHAVE = '3f0a1c9e-5b7d-4e21-9a3c-0d6f2b8e4a17';

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->administrador()->create();
    }

    private function api(?Usuario $usuario = null, array $cabecalhos = [])
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $this->withToken(($usuario ?? $this->admin)->createToken('t', ['fae-app'])->plainTextToken)->withHeaders($cabecalhos);
    }

    private function dadosCliente(array $extra = []): array
    {
        return $extra + ['cli_nome' => 'Usina Teste', 'cli_cnpj' => '12345678000190', 'cli_cidade' => 'Ribeirão Preto', 'cli_uf' => 'SP'];
    }

    // ---- §4 Idempotência ----------------------------------------------------------

    public function test_mesma_chave_e_mesmo_conteudo_devolve_a_resposta_guardada_sem_criar_outro(): void
    {
        $primeira = $this->api(null, ['Idempotency-Key' => self::CHAVE])->postJson('/api/fae/v1/clientes', $this->dadosCliente())->assertCreated();
        $segunda = $this->api(null, ['Idempotency-Key' => self::CHAVE])->postJson('/api/fae/v1/clientes', $this->dadosCliente())
            ->assertCreated()->assertHeader('Idempotent-Replayed', 'true');

        $this->assertSame($primeira->json('data.id'), $segunda->json('data.id'));
        $this->assertSame(1, Cliente::count());
    }

    public function test_mesma_chave_com_outro_conteudo_e_conflito(): void
    {
        $this->api(null, ['Idempotency-Key' => self::CHAVE])->postJson('/api/fae/v1/clientes', $this->dadosCliente())->assertCreated();

        $this->api(null, ['Idempotency-Key' => self::CHAVE])->postJson('/api/fae/v1/clientes', $this->dadosCliente(['cli_nome' => 'Outro']))
            ->assertStatus(409)->assertJsonPath('codigo', 'idempotencia_conflito');
        $this->assertSame(1, Cliente::count());
    }

    public function test_arquivo_diferente_com_a_mesma_chave_e_conflito(): void
    {
        $dados = fn (string $conteudo) => $this->dadosCliente(['anexo' => UploadedFile::fake()->createWithContent('a.txt', $conteudo)]);

        $this->api(null, ['Idempotency-Key' => self::CHAVE])->post('/api/fae/v1/clientes', $dados('um'), ['Accept' => 'application/json'])->assertCreated();
        $this->api(null, ['Idempotency-Key' => self::CHAVE])->post('/api/fae/v1/clientes', $dados('dois'), ['Accept' => 'application/json'])
            ->assertStatus(409)->assertJsonPath('codigo', 'idempotencia_conflito');
    }

    public function test_recusa_de_validacao_tambem_e_guardada(): void
    {
        $this->api(null, ['Idempotency-Key' => self::CHAVE])->postJson('/api/fae/v1/clientes', ['cli_nome' => 'Sem CNPJ'])->assertStatus(422);
        $this->api(null, ['Idempotency-Key' => self::CHAVE])->postJson('/api/fae/v1/clientes', ['cli_nome' => 'Sem CNPJ'])
            ->assertStatus(422)->assertHeader('Idempotent-Replayed', 'true');
    }

    public function test_chave_e_por_usuario(): void
    {
        $outro = Usuario::factory()->administrador()->create();

        $this->api(null, ['Idempotency-Key' => self::CHAVE])->postJson('/api/fae/v1/clientes', $this->dadosCliente())->assertCreated();
        $this->api($outro, ['Idempotency-Key' => self::CHAVE])->postJson('/api/fae/v1/clientes', $this->dadosCliente(['cli_cnpj' => '98765432000110']))
            ->assertCreated()->assertHeaderMissing('Idempotent-Replayed');
        $this->assertSame(2, Cliente::count());
    }

    public function test_sem_chave_segue_como_antes_e_com_a_flag_exige(): void
    {
        $this->api()->postJson('/api/fae/v1/clientes', $this->dadosCliente())->assertCreated();

        config(['api_fae.exigir_idempotencia' => true]);
        $this->api()->postJson('/api/fae/v1/clientes', $this->dadosCliente(['cli_cnpj' => '98765432000110']))
            ->assertStatus(428)->assertJsonPath('codigo', 'idempotencia_obrigatoria');
        $this->api(null, ['Idempotency-Key' => 'curta'])->postJson('/api/fae/v1/clientes', $this->dadosCliente(['cli_cnpj' => '98765432000110']))
            ->assertStatus(422)->assertJsonPath('codigo', 'idempotencia_chave_invalida');
    }

    public function test_chave_em_andamento_responde_409_e_abandonada_e_retomada(): void
    {
        \Illuminate\Support\Facades\DB::table('api_idempotencia')->insert([
            'idem_usuario_id' => $this->admin->user_id, 'idem_chave' => self::CHAVE, 'idem_rota' => 'api/fae/v1/clientes',
            'idem_hash' => str_repeat('0', 64), 'idem_criado_em' => now(),
        ]);
        // Hash diferente do registro: conflito antes de olhar o andamento.
        $this->api(null, ['Idempotency-Key' => self::CHAVE])->postJson('/api/fae/v1/clientes', $this->dadosCliente())
            ->assertStatus(409)->assertJsonPath('codigo', 'idempotencia_conflito');

        // Registro do mesmo conteúdo ainda sem resposta.
        $this->api(null, ['Idempotency-Key' => 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee'])->postJson('/api/fae/v1/clientes', $this->dadosCliente())->assertCreated();
        $hash = \Illuminate\Support\Facades\DB::table('api_idempotencia')->where('idem_chave', 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee')->value('idem_hash');
        \Illuminate\Support\Facades\DB::table('api_idempotencia')->where('idem_chave', self::CHAVE)->update(['idem_hash' => $hash]);
        $this->api(null, ['Idempotency-Key' => self::CHAVE])->postJson('/api/fae/v1/clientes', $this->dadosCliente())
            ->assertStatus(409)->assertJsonPath('codigo', 'idempotencia_em_andamento');

        $this->travel(6)->minutes();
        // Retomada: roda de novo (aqui o CNPJ já existe, então a regra recusa — e isso fica guardado).
        $this->api(null, ['Idempotency-Key' => self::CHAVE])->postJson('/api/fae/v1/clientes', $this->dadosCliente())->assertStatus(422);
    }

    // ---- §6 Versão + If-Match + PUT parcial -----------------------------------------

    public function test_versao_sobe_a_cada_gravacao_e_if_match_detecta_conflito(): void
    {
        $id = $this->api()->postJson('/api/fae/v1/clientes', $this->dadosCliente())->json('data.id');
        $this->api()->getJson("/api/fae/v1/clientes/{$id}")->assertJsonPath('data.versao', 0);

        $this->api(null, ['If-Match' => '0'])->putJson("/api/fae/v1/clientes/{$id}", ['cli_nome' => 'Nome A'])
            ->assertOk()->assertJsonPath('data.versao', 1);

        // Quem ainda tinha a versão 0 recebe o conflito com a versão atual.
        $this->api(null, ['If-Match' => '"0"'])->putJson("/api/fae/v1/clientes/{$id}", ['cli_nome' => 'Nome B'])
            ->assertStatus(409)->assertJsonPath('codigo', 'conflito_versao')
            ->assertJsonPath('atual.versao', 1)->assertJsonPath('atual.nome', 'Nome A');
        $this->assertSame('Nome A', Cliente::find($id)->cli_nome);
    }

    public function test_sem_if_match_aceita_e_com_a_flag_exige(): void
    {
        $id = $this->api()->postJson('/api/fae/v1/clientes', $this->dadosCliente())->json('data.id');
        $this->api()->putJson("/api/fae/v1/clientes/{$id}", ['cli_nome' => 'Sem If-Match'])->assertOk();

        config(['api_fae.exigir_if_match' => true]);
        $this->api()->putJson("/api/fae/v1/clientes/{$id}", ['cli_nome' => 'Outro'])
            ->assertStatus(428)->assertJsonPath('codigo', 'versao_obrigatoria');
    }

    public function test_gravacao_pelo_web_tambem_sobe_a_versao(): void
    {
        $cliente = Cliente::factory()->create();
        $this->actingAs($this->admin)->put(route('clientes.update', $cliente->cli_id), [
            'cli_nome' => 'Pelo web', 'cli_cnpj' => $cliente->cli_cnpj, 'cli_cidade' => 'X', 'cli_uf' => 'SP',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $cliente->refresh()->versao());
    }

    public function test_put_parcial_de_cliente_mantem_campos_e_listas_ausentes(): void
    {
        $id = $this->api()->postJson('/api/fae/v1/clientes', $this->dadosCliente([
            'cli_email' => 'contato@usina.com.br',
            'contatos' => [['nome' => 'Ana', 'tipo' => 0]],
            'equipamentos' => [['descricao' => 'Decanter']],
        ]))->json('data.id');

        $this->api()->putJson("/api/fae/v1/clientes/{$id}", ['cli_nome' => 'Só o nome'])->assertOk();

        $cliente = Cliente::with(['contatos', 'equipamentos'])->find($id);
        $this->assertSame('Só o nome', $cliente->cli_nome);
        $this->assertSame('contato@usina.com.br', $cliente->cli_email);
        $this->assertSame('12345678000190', $cliente->cli_cnpj);
        $this->assertCount(1, $cliente->contatos);
        $this->assertCount(1, $cliente->equipamentos);
        $this->assertSame('Decanter', $cliente->cli_equipamento_vendido);

        // Lista enviada vazia é tocada (remove).
        $this->api()->putJson("/api/fae/v1/clientes/{$id}", ['contatos' => []])->assertOk();
        $this->assertCount(0, $cliente->refresh()->contatos);
    }

    public function test_no_web_lista_ausente_continua_sendo_lista_vazia(): void
    {
        $cliente = Cliente::factory()->create();
        $cliente->contatos()->create(['cli_cont_nome' => 'Ana', 'cli_cont_tipo' => 0]);

        $this->actingAs($this->admin)->put(route('clientes.update', $cliente->cli_id), [
            'cli_nome' => $cliente->cli_nome, 'cli_cnpj' => $cliente->cli_cnpj, 'cli_cidade' => 'X', 'cli_uf' => 'SP',
        ])->assertSessionHasNoErrors();

        $this->assertCount(0, $cliente->refresh()->contatos);
    }

    public function test_put_parcial_de_orcamento_e_roteiro(): void
    {
        $vendedor = Usuario::factory()->comercial()->create();
        $cliente = Cliente::factory()->create();
        $adicional = Usuario::factory()->comercial()->create();
        $orcamento = Orcamento::create([
            'orc_cliente_id' => $cliente->cli_id, 'orc_vendedor_id' => $vendedor->user_id,
            'orc_nivel' => NivelOrcamento::Medio->value, 'orc_prazo_envio' => '2026-12-10', 'orc_ativo' => 1, 'orc_criado_em' => now(),
        ]);
        $orcamento->vendedoresAdicionais()->sync([$adicional->user_id]);

        $this->api($vendedor, ['If-Match' => '0'])->putJson("/api/fae/v1/orcamentos/{$orcamento->orc_id}", ['orc_resultado' => 1])
            ->assertOk()->assertJsonPath('data.versao', 1);
        $orcamento->refresh();
        $this->assertSame('2026-12-10', $orcamento->orc_prazo_envio?->toDateString());
        $this->assertSame($cliente->cli_id, (int) $orcamento->orc_cliente_id);
        $this->assertCount(1, $orcamento->vendedoresAdicionais);

        $roteiro = $this->api($vendedor)->postJson('/api/fae/v1/roteiros-viagem', [
            'crm_rot_vendedor_id' => $vendedor->user_id, 'crm_rot_periodo_inicio' => '2026-12-01', 'crm_rot_periodo_fim' => '2026-12-03',
            'clientes' => [$cliente->cli_id], 'resultados' => [$cliente->cli_id => 1], 'observacoes' => [$cliente->cli_id => 'Visitado'],
        ])->assertCreated()->json('data');

        $this->api($vendedor)->putJson("/api/fae/v1/roteiros-viagem/{$roteiro['id']}", ['crm_rot_link_mapa' => 'https://maps.google.com/?q=1,1'])
            ->assertOk()
            ->assertJsonPath('data.versao', 1)
            ->assertJsonPath('data.periodo_inicio', '2026-12-01')
            ->assertJsonPath('data.clientes.0.cliente_id', $cliente->cli_id)
            ->assertJsonPath('data.clientes.0.resultado', 1)
            ->assertJsonPath('data.clientes.0.observacao', 'Visitado');
    }

    // ---- §3 Versão do app -----------------------------------------------------------

    public function test_versao_do_app_abaixo_da_minima_recebe_426_so_com_a_atualizacao_obrigatoria(): void
    {
        config(['mobile_apk.versao_minima' => '1.4.0+9', 'mobile_apk.obrigatoria' => false]);
        $this->api(null, ['X-App-Version' => '1.3.0+8'])->getJson('/api/fae/v1/me')->assertOk();

        config(['mobile_apk.obrigatoria' => true]);
        $this->api(null, ['X-App-Version' => '1.3.0+8'])->getJson('/api/fae/v1/me')
            ->assertStatus(426)->assertJsonPath('codigo', 'versao_minima')->assertJsonPath('versao_minima', '1.4.0+9');
        $this->postJson('/api/fae/v1/login', ['email' => 'x@x.com', 'senha' => 'x'], ['X-App-Version' => '1.4.0+8'])->assertStatus(426);

        $this->api(null, ['X-App-Version' => '1.4.0+9'])->getJson('/api/fae/v1/me')->assertOk();
        $this->api(null, ['X-App-Version' => '1.10.0+1'])->getJson('/api/fae/v1/me')->assertOk();

        // Sem o cabeçalho (app antigo): passa até ligar a exigência.
        $this->api()->getJson('/api/fae/v1/me')->assertOk();
        config(['mobile_apk.exigir_cabecalho_versao' => true]);
        $this->api()->getJson('/api/fae/v1/me')->assertStatus(426);

        // Por /app/versao o app sempre descobre que precisa atualizar.
        $this->getJson('/api/fae/v1/app/versao', ['X-App-Version' => '1.0.0+1'])->assertOk()
            ->assertJsonPath('versao_minima', '1.4.0+9')->assertJsonStructure(['sha256_apk']);
    }
}
