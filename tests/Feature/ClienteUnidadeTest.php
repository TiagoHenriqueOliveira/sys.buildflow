<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Unidade do cliente: o mesmo CNPJ pode repetir com unidade diferente, e a
// unidade aparece ao lado do nome, com o rótulo ("- Unidade: X"), na web, no
// PDF e na API do app (que exibe o texto pronto, sem APK novo).
class ClienteUnidadeTest extends TestCase
{
    use RefreshDatabase;

    private const CNPJ = '40074069000184';

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Usuario::factory()->administrador()->create();
    }

    private function dados(array $sobrescrever = []): array
    {
        return array_merge([
            'cli_nome'     => 'AMBIENTAL MS PANTANAL SPE S.A.',
            'cli_unidade'  => 'Itaquiraí',
            'cli_cnpj'     => '40.074.069/0001-84',
            'cli_cidade'   => 'ITAQUIRAI',
            'cli_uf'       => 'MS',
            'cli_telefone' => '',
            'cli_email'    => '',
            'cli_ativo'    => 1,
        ], $sobrescrever);
    }

    private function clienteFatima(): Cliente
    {
        return Cliente::factory()->create([
            'cli_nome'    => 'AMBIENTAL MS PANTANAL SPE S.A.',
            'cli_unidade' => 'Fátima do Sul',
            'cli_cnpj'    => self::CNPJ,
        ]);
    }

    // ── Cadastro ─────────────────────────────────────────────────────────────

    public function test_cadastra_o_mesmo_cnpj_com_outra_unidade(): void
    {
        $this->clienteFatima();

        $this->actingAs($this->admin)->postJson('/clientes', $this->dados())->assertOk();

        $this->assertSame(2, Cliente::where('cli_cnpj', self::CNPJ)->count());
        $this->assertDatabaseHas('clientes', ['cli_cnpj' => self::CNPJ, 'cli_unidade' => 'Itaquiraí']);
    }

    public function test_recusa_a_mesma_unidade_mesmo_com_grafia_diferente(): void
    {
        $this->clienteFatima();

        $this->actingAs($this->admin)
            ->postJson('/clientes', $this->dados(['cli_unidade' => '  FATIMA   DO SUL ']))
            ->assertStatus(422)
            ->assertJsonPath('errors.cli_cnpj.0', 'Já existe um cliente com este CNPJ e esta unidade.');

        $this->assertSame(1, Cliente::where('cli_cnpj', self::CNPJ)->count());
    }

    public function test_sem_unidade_continua_um_cliente_por_cnpj_e_orienta_a_preencher(): void
    {
        Cliente::factory()->create(['cli_cnpj' => self::CNPJ, 'cli_unidade' => '']);

        $this->actingAs($this->admin)
            ->postJson('/clientes', $this->dados(['cli_unidade' => '']))
            ->assertStatus(422)
            ->assertJsonPath('errors.cli_cnpj.0', 'Este CNPJ já está cadastrado. Para cadastrar outra unidade da mesma empresa, preencha o campo Unidade.');

        // com unidade, passa
        $this->actingAs($this->admin)->postJson('/clientes', $this->dados())->assertOk();
    }

    public function test_cadastro_sem_o_campo_unidade_grava_vazio(): void
    {
        $dados = $this->dados();
        unset($dados['cli_unidade']);

        $this->actingAs($this->admin)->postJson('/clientes', $dados)->assertOk();

        $this->assertSame('', Cliente::where('cli_cnpj', self::CNPJ)->value('cli_unidade'));
    }

    public function test_edita_mantendo_cnpj_e_unidade(): void
    {
        $fatima = $this->clienteFatima();

        $this->actingAs($this->admin)
            ->postJson("/clientes/{$fatima->cli_id}", $this->dados([
                '_method' => 'PUT', 'cli_unidade' => 'Fátima do Sul', 'cli_email' => 'novo@exemplo.com',
            ]))
            ->assertOk();

        $this->assertSame('novo@exemplo.com', $fatima->fresh()->cli_email);
    }

    public function test_edicao_nao_pode_repetir_a_unidade_de_outro_cliente_do_mesmo_cnpj(): void
    {
        $this->clienteFatima();
        $itaquirai = Cliente::factory()->create(['cli_cnpj' => self::CNPJ, 'cli_unidade' => 'Itaquiraí']);

        $this->actingAs($this->admin)
            ->postJson("/clientes/{$itaquirai->cli_id}", $this->dados(['_method' => 'PUT', 'cli_unidade' => 'Fátima do Sul']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cli_cnpj');

        $this->assertSame('Itaquiraí', $itaquirai->fresh()->cli_unidade);
    }

    public function test_banco_tambem_barra_cnpj_e_unidade_repetidos(): void
    {
        $this->clienteFatima();

        $this->expectException(QueryException::class);
        Cliente::factory()->create(['cli_cnpj' => self::CNPJ, 'cli_unidade' => 'FATIMA DO SUL']);
    }

    public function test_listagem_de_clientes_tem_a_unidade_e_busca_por_ela(): void
    {
        $this->clienteFatima();
        Cliente::factory()->create(['cli_nome' => 'OUTRA EMPRESA', 'cli_unidade' => '']);

        $this->actingAs($this->admin)
            ->getJson('/clientes?draw=1&start=0&length=25&search[value]=fátima', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.cli_unidade', 'Fátima do Sul');
    }

    // ── Escolha do cliente e telas web ───────────────────────────────────────

    public function test_autocomplete_de_cliente_mostra_e_busca_pela_unidade(): void
    {
        $fatima = $this->clienteFatima();
        Cliente::factory()->create(['cli_nome' => 'AMBIENTAL MS PANTANAL SPE S.A.', 'cli_unidade' => 'Itaquiraí', 'cli_cnpj' => self::CNPJ]);

        $this->actingAs($this->admin)
            ->getJson('/clientes/autocomplete?term=PANTANAL')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.label', 'AMBIENTAL MS PANTANAL SPE S.A. - Unidade: Fátima do Sul')
            ->assertJsonPath('1.label', 'AMBIENTAL MS PANTANAL SPE S.A. - Unidade: Itaquiraí');

        $this->actingAs($this->admin)
            ->getJson('/clientes/autocomplete?term=fatima')
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $fatima->cli_id);
    }

    public function test_tabelas_de_atendimentos_e_relatorios_mostram_a_unidade(): void
    {
        $atendimento = Atendimento::factory()->create(['aten_cliente_id' => $this->clienteFatima()->cli_id]);
        AtendimentoRelatorio::factory()->for($atendimento, 'atendimento')->create();
        $esperado = 'AMBIENTAL MS PANTANAL SPE S.A. - Unidade: Fátima do Sul';
        $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

        $this->actingAs($this->admin)
            ->getJson('/atendimentos?draw=1&start=0&length=25&search[value]=fátima', $ajax)
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.cliente', e($esperado));

        $this->actingAs($this->admin)
            ->getJson('/atendimentos-relatorios?draw=1&start=0&length=25&search[value]=fátima', $ajax)
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.cliente', e($esperado));

        $this->actingAs($this->admin)
            ->getJson('/atendimentos-relatorios/autocomplete?term=fatima')
            ->assertOk()
            ->assertJsonPath('0.id', $atendimento->aten_id);
        $this->assertStringStartsWith($esperado, $this->actingAs($this->admin)
            ->getJson('/atendimentos-relatorios/autocomplete?term=fatima')->json('0.label'));
    }

    public function test_dashboard_separa_as_unidades_de_mesmo_nome(): void
    {
        $fatima = $this->clienteFatima();
        $itaquirai = Cliente::factory()->create(['cli_nome' => 'AMBIENTAL MS PANTANAL SPE S.A.', 'cli_unidade' => 'Itaquiraí', 'cli_cnpj' => self::CNPJ]);
        Atendimento::factory()->count(2)->create(['aten_cliente_id' => $fatima->cli_id]);
        Atendimento::factory()->create(['aten_cliente_id' => $itaquirai->cli_id]);

        $labels = $this->actingAs($this->admin)->getJson('/dashboard/data/mais-relatorios')->assertOk()->json('labels');

        $this->assertSame([
            'AMBIENTAL MS PANTANAL SPE S.A. - Unidade: Fátima do Sul',
            'AMBIENTAL MS PANTANAL SPE S.A. - Unidade: Itaquiraí',
        ], $labels);
    }

    // ── API do app ───────────────────────────────────────────────────────────

    public function test_api_do_app_manda_o_nome_com_a_unidade(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = Atendimento::factory()->create([
            'aten_cliente_id' => $this->clienteFatima()->cli_id,
            'aten_usuario_id' => $tecnico->user_id,
        ]);
        $relatorio = AtendimentoRelatorio::factory()->for($atendimento, 'atendimento')->create();
        $token = $tecnico->createToken('t')->plainTextToken;
        $esperado = 'AMBIENTAL MS PANTANAL SPE S.A. - Unidade: Fátima do Sul';

        $lista = $this->withToken($token)->getJson('/api/mcl/v1/atendimentos?search=fátima')->assertOk();
        $lista->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cliente.nome', $esperado)
            ->assertJsonPath('data.0.cliente.unidade', 'Fátima do Sul');
        $this->assertStringEndsWith(' – ' . $esperado, $lista->json('data.0.descricao'));

        $this->withToken($token)->getJson("/api/mcl/v1/atendimentos/{$atendimento->aten_id}")
            ->assertOk()
            ->assertJsonPath('data.cliente.nome', $esperado);

        $rel = $this->withToken($token)->getJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}")->assertOk();
        $rel->assertJsonPath('data.atendimento.cliente', $esperado)
            ->assertJsonPath('data.atendimento.unidade', 'Fátima do Sul');
        $this->assertStringEndsWith(' – ' . $esperado, $rel->json('data.atendimento.descricao'));
    }

    public function test_api_do_app_sem_unidade_nao_muda(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $cliente = Cliente::factory()->create(['cli_nome' => 'ZANOTTI LTDA', 'cli_unidade' => '']);
        Atendimento::factory()->create(['aten_cliente_id' => $cliente->cli_id, 'aten_usuario_id' => $tecnico->user_id]);

        $this->withToken($tecnico->createToken('t')->plainTextToken)
            ->getJson('/api/mcl/v1/atendimentos')
            ->assertOk()
            ->assertJsonPath('data.0.cliente.nome', 'ZANOTTI LTDA')
            ->assertJsonPath('data.0.cliente.unidade', null);
    }

    // ── PDF ──────────────────────────────────────────────────────────────────

    private function htmlPdf(AtendimentoRelatorio $relatorio): string
    {
        $relatorio = $relatorio->fresh();
        $prazo = $relatorio->calcularPrazo();

        return view('atendimentos-relatorios.pdf', [
            'relatorio'      => $relatorio,
            'prazoTotal'     => $prazo['prazo_total'],
            'prazoDecorrido' => $prazo['prazo_decorrido'],
            'prazoAVencer'   => $prazo['prazo_a_vencer'],
        ])->render();
    }

    public function test_pdf_mostra_a_unidade_com_rotulo_ao_lado_do_nome(): void
    {
        $atendimento = Atendimento::factory()->create(['aten_cliente_id' => $this->clienteFatima()->cli_id]);
        $html = $this->htmlPdf(AtendimentoRelatorio::factory()->for($atendimento, 'atendimento')->create());

        $this->assertStringContainsString(
            'AMBIENTAL MS PANTANAL SPE S.A. - <span class="field-label-inline-right">Unidade:</span>Fátima do Sul</td>',
            $html
        );
        $this->assertStringContainsString('<div class="assinatura-nome">AMBIENTAL MS PANTANAL SPE S.A. - Unidade: Fátima do Sul</div>', $html);
    }

    public function test_pdf_sem_unidade_nao_mostra_o_rotulo(): void
    {
        $cliente = Cliente::factory()->create(['cli_nome' => 'ZANOTTI LTDA', 'cli_unidade' => '']);
        $atendimento = Atendimento::factory()->create(['aten_cliente_id' => $cliente->cli_id]);
        $html = $this->htmlPdf(AtendimentoRelatorio::factory()->for($atendimento, 'atendimento')->create());

        $this->assertStringContainsString('<span class="field-label-inline">Cliente:</span>ZANOTTI LTDA</td>', $html);
        $this->assertStringNotContainsString('Unidade:', $html);
    }
}
