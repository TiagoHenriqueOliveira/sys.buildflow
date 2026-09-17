<?php

namespace Tests\Feature\Api;

use App\Enums\NivelOrcamento;
use App\Enums\SetorModelo;
use App\Enums\TipoPergunta;
use App\Models\Cliente;
use App\Models\ConfigModelo;
use App\Models\ConfigPergunta;
use App\Models\CrmTipoOrcamento;
use App\Models\Orcamento;
use App\Models\RoteiroViagem;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmApiFaeTest extends TestCase
{
    use RefreshDatabase;

    private function token(Usuario $usuario): string
    {
        return $usuario->createToken('test')->plainTextToken;
    }

    private function criarComercial(): Usuario
    {
        return Usuario::factory()->comercial()->create();
    }

    public function test_lista_orcamentos_sem_token_retorna_401(): void
    {
        $response = $this->getJson('/api/fae/v1/orcamentos');
        $response->assertUnauthorized();
    }

    public function test_tecnico_nao_acessa_orcamentos(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();

        $response = $this->withToken($this->token($tecnico))->getJson('/api/fae/v1/orcamentos');

        $response->assertForbidden();
    }

    public function test_comercial_cria_orcamento_com_resposta_de_pergunta_dinamica(): void
    {
        $vendedor = $this->criarComercial();
        $cliente = Cliente::factory()->create();
        $pergunta = ConfigPergunta::create(['cfg_perg_texto' => 'Volume?', 'cfg_perg_tipo' => TipoPergunta::TextoLivre->value]);
        $modelo = ConfigModelo::create(['cfg_mod_nome' => 'Orcamento Padrao', 'cfg_mod_setor' => SetorModelo::Comercial->value]);
        $modelo->perguntas()->sync([$pergunta->cfg_perg_id]);
        $tipo = CrmTipoOrcamento::create(['crm_tp_orc_nome' => 'ETE Teste', 'crm_tp_orc_config_modelo_id' => $modelo->cfg_mod_id]);

        $response = $this->withToken($this->token($vendedor))->postJson('/api/fae/v1/orcamentos', [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_tipo_orcamento_id' => $tipo->crm_tp_orc_id,
            'orc_nivel' => NivelOrcamento::Medio->value,
            'respostas' => [$pergunta->cfg_perg_id => 'Cinquenta m3/dia'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.cliente.id', $cliente->cli_id)
            ->assertJsonPath("data.respostas.{$pergunta->cfg_perg_id}", 'Cinquenta m3/dia');
        $this->assertDatabaseHas('orcamentos_respostas', [
            'orc_resp_pergunta_id' => $pergunta->cfg_perg_id,
            'orc_resp_valor' => 'Cinquenta m3/dia',
        ]);
    }

    public function test_comercial_comenta_e_exclui_comentario_do_orcamento(): void
    {
        $vendedor = $this->criarComercial();
        $cliente = Cliente::factory()->create();
        $orcamento = Orcamento::create([
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_ativo' => 1,
            'orc_criado_em' => now(),
        ]);

        $comentar = $this->withToken($this->token($vendedor))->postJson(
            "/api/fae/v1/orcamentos/{$orcamento->orc_id}/comentarios",
            ['orc_com_texto' => 'Cliente pediu revisão de prazo.']
        );
        $comentar->assertCreated();
        $comentarioId = $comentar->json('data.id');
        $this->assertDatabaseHas('orcamentos_comentarios', ['orc_com_id' => $comentarioId]);

        $excluir = $this->withToken($this->token($vendedor))->deleteJson(
            "/api/fae/v1/orcamentos/{$orcamento->orc_id}/comentarios/{$comentarioId}"
        );
        $excluir->assertOk();
        $this->assertDatabaseMissing('orcamentos_comentarios', ['orc_com_id' => $comentarioId]);
    }

    /**
     * Pedido do cliente (2026-09-17) - orc_resultado faltava no payload da
     * API (campo criado depois deste endpoint existir).
     */
    public function test_orcamento_retorna_resultado_na_resposta(): void
    {
        $vendedor = $this->criarComercial();
        $cliente = Cliente::factory()->create();

        $response = $this->withToken($this->token($vendedor))->postJson('/api/fae/v1/orcamentos', [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_resultado' => \App\Enums\ResultadoOrcamento::Convertido->value,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.resultado', \App\Enums\ResultadoOrcamento::Convertido->value)
            ->assertJsonPath('data.resultado_label', 'Convertido');
    }

    /**
     * Pedido do cliente (2026-09-17) - o alerta de comentario via API nao
     * disparava notificacao (so o endpoint web fazia isso ate agora).
     */
    public function test_comentario_com_alerta_via_api_cria_notificacao(): void
    {
        $vendedor = $this->criarComercial();
        $colega = $this->criarComercial();
        $cliente = Cliente::factory()->create();
        $orcamento = Orcamento::create([
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_ativo' => 1,
            'orc_criado_em' => now(),
        ]);

        $this->withToken($this->token($vendedor))->postJson(
            "/api/fae/v1/orcamentos/{$orcamento->orc_id}/comentarios",
            ['orc_com_texto' => 'Revisar prazo.', 'orc_com_alerta_usuario_id' => $colega->user_id]
        )->assertCreated();

        $this->assertDatabaseHas('notificacoes', [
            'notif_usuario_id' => $colega->user_id,
            'notif_tipo' => 'comentario_orcamento',
        ]);
    }

    public function test_catalogo_segmentos_lista_apenas_ativos(): void
    {
        $vendedor = $this->criarComercial();
        \App\Models\Segmento::create(['seg_descricao' => 'Sucroenergético', 'seg_ativo' => true]);
        \App\Models\Segmento::create(['seg_descricao' => 'Descontinuado', 'seg_ativo' => false]);

        $response = $this->withToken($this->token($vendedor))->getJson('/api/fae/v1/catalogos/segmentos');

        $response->assertOk();
        $response->assertJsonFragment(['descricao' => 'Sucroenergético']);
        $response->assertJsonMissing(['descricao' => 'Descontinuado']);
    }

    public function test_comercial_cria_roteiro_de_viagem_com_clientes(): void
    {
        $vendedor = $this->criarComercial();
        $cliente1 = Cliente::factory()->create();
        $cliente2 = Cliente::factory()->create();

        $response = $this->withToken($this->token($vendedor))->postJson('/api/fae/v1/roteiros-viagem', [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => now()->toDateString(),
            'crm_rot_periodo_fim' => now()->addDays(3)->toDateString(),
            'crm_rot_link_mapa' => 'https://maps.google.com/?q=1,1',
            'clientes' => [$cliente1->cli_id, $cliente2->cli_id],
        ]);

        $response->assertCreated()->assertJsonCount(2, 'data.clientes');
        $this->assertDatabaseHas('crm_roteiros_viagem_clientes', [
            'crm_rot_cli_cliente_id' => $cliente1->cli_id,
            'crm_rot_cli_ordem' => 0,
        ]);
    }

    public function test_roteiro_sem_cliente_retorna_422(): void
    {
        $vendedor = $this->criarComercial();

        $response = $this->withToken($this->token($vendedor))->postJson('/api/fae/v1/roteiros-viagem', [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => now()->toDateString(),
            'crm_rot_periodo_fim' => now()->addDays(1)->toDateString(),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('clientes');
    }

    public function test_lista_mapa_relacoes_retorna_so_clientes_com_coordenada(): void
    {
        $vendedor = $this->criarComercial();
        Cliente::factory()->create(['cli_latitude' => -23.5, 'cli_longitude' => -46.6, 'cli_nome' => 'Com coordenada']);
        Cliente::factory()->create(['cli_latitude' => null, 'cli_longitude' => null, 'cli_nome' => 'Sem coordenada']);

        $response = $this->withToken($this->token($vendedor))->getJson('/api/fae/v1/mapa-relacoes');

        $response->assertOk();
        $nomes = collect($response->json('data'))->pluck('nome')->all();
        $this->assertSame(['Com coordenada'], $nomes);
    }

    /**
     * Regressao: o app reenvia o `prazo_envio` recebido do servidor sem
     * alteracao quando o usuario nao troca a data - se a API devolvesse a
     * data com hora/timezone (bug real: Carbon->jsonSerialize() ignora o
     * cast `date:Y-m-d` do model fora do toArray() padrao do Eloquent), o
     * reenvio quebrava o UPDATE com SQLSTATE[22007] (coluna e' `date`, nao
     * `datetime`). Ver OrcamentosController::formatResumo().
     */
    public function test_prazo_envio_volta_so_com_data_e_atualizar_sem_mudar_nao_quebra(): void
    {
        $vendedor = $this->criarComercial();
        $cliente = Cliente::factory()->create();
        $orcamento = Orcamento::create([
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_prazo_envio' => '2026-09-17',
            'orc_ativo' => 1,
            'orc_criado_em' => now(),
        ]);

        $mostrar = $this->withToken($this->token($vendedor))->getJson("/api/fae/v1/orcamentos/{$orcamento->orc_id}");
        $mostrar->assertOk()->assertJsonPath('data.prazo_envio', '2026-09-17');

        $atualizar = $this->withToken($this->token($vendedor))->putJson("/api/fae/v1/orcamentos/{$orcamento->orc_id}", [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'orc_prazo_envio' => $mostrar->json('data.prazo_envio'),
        ]);

        $atualizar->assertOk()->assertJsonPath('data.prazo_envio', '2026-09-17');
    }

    /**
     * Mesma regressao do teste acima, para RoteiroViagem (periodo_inicio/fim).
     * Ver RoteirosViagemController::formatResumo().
     */
    public function test_periodo_roteiro_volta_so_com_data_e_atualizar_sem_mudar_nao_quebra(): void
    {
        $vendedor = $this->criarComercial();
        $cliente = Cliente::factory()->create();
        $roteiro = $this->withToken($this->token($vendedor))->postJson('/api/fae/v1/roteiros-viagem', [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => '2026-09-15',
            'crm_rot_periodo_fim' => '2026-09-18',
            'crm_rot_link_mapa' => 'https://maps.google.com/?q=1,1',
            'clientes' => [$cliente->cli_id],
        ])->json('data');

        $mostrar = $this->withToken($this->token($vendedor))->getJson("/api/fae/v1/roteiros-viagem/{$roteiro['id']}");
        $mostrar->assertOk()
            ->assertJsonPath('data.periodo_inicio', '2026-09-15')
            ->assertJsonPath('data.periodo_fim', '2026-09-18');

        $atualizar = $this->withToken($this->token($vendedor))->putJson("/api/fae/v1/roteiros-viagem/{$roteiro['id']}", [
            'crm_rot_vendedor_id' => $vendedor->user_id,
            'crm_rot_periodo_inicio' => $mostrar->json('data.periodo_inicio'),
            'crm_rot_periodo_fim' => $mostrar->json('data.periodo_fim'),
            'crm_rot_link_mapa' => $mostrar->json('data.link_mapa'),
            'clientes' => [$cliente->cli_id],
        ]);

        $atualizar->assertOk()
            ->assertJsonPath('data.periodo_inicio', '2026-09-15')
            ->assertJsonPath('data.periodo_fim', '2026-09-18');
    }

    public function test_resposta_de_multipla_escolha_volta_como_array_decodificado(): void
    {
        $vendedor = $this->criarComercial();
        $cliente = Cliente::factory()->create();
        $pergunta = ConfigPergunta::create(['cfg_perg_texto' => 'Quais equipamentos?', 'cfg_perg_tipo' => TipoPergunta::MultiplaEscolha->value]);
        $op1 = $pergunta->opcoes()->create(['cfg_perg_op_texto' => 'Bomba']);
        $op2 = $pergunta->opcoes()->create(['cfg_perg_op_texto' => 'Filtro']);

        $criar = $this->withToken($this->token($vendedor))->postJson('/api/fae/v1/orcamentos', [
            'orc_cliente_id' => $cliente->cli_id,
            'orc_vendedor_id' => $vendedor->user_id,
            'respostas' => [$pergunta->cfg_perg_id => [$op1->cfg_perg_op_id, $op2->cfg_perg_op_id]],
        ]);
        $criar->assertCreated();
        $orcamentoId = $criar->json('data.id');

        $show = $this->withToken($this->token($vendedor))->getJson("/api/fae/v1/orcamentos/{$orcamentoId}");

        $show->assertOk();
        $valor = $show->json("data.respostas.{$pergunta->cfg_perg_id}");
        $this->assertIsArray($valor);
        $this->assertEqualsCanonicalizing([(string) $op1->cfg_perg_op_id, (string) $op2->cfg_perg_op_id], array_map('strval', $valor));
    }

    public function test_catalogo_tipos_orcamento_inclui_perguntas_do_modelo(): void
    {
        $vendedor = $this->criarComercial();
        $pergunta = ConfigPergunta::create(['cfg_perg_texto' => 'Volume?', 'cfg_perg_tipo' => TipoPergunta::TextoLivre->value]);
        $modelo = ConfigModelo::create(['cfg_mod_nome' => 'Orcamento Padrao', 'cfg_mod_setor' => SetorModelo::Comercial->value]);
        $modelo->perguntas()->sync([$pergunta->cfg_perg_id]);
        CrmTipoOrcamento::create(['crm_tp_orc_nome' => 'ETE Teste', 'crm_tp_orc_config_modelo_id' => $modelo->cfg_mod_id, 'crm_tp_orc_ativo' => 1]);

        $response = $this->withToken($this->token($vendedor))->getJson('/api/fae/v1/catalogos/tipos-orcamento');

        $response->assertOk();
        $tipos = collect($response->json('data'));
        $item = $tipos->firstWhere('nome', 'ETE Teste');
        $this->assertNotNull($item, 'tipo "ETE Teste" nao encontrado na resposta');
        $this->assertCount(1, $item['perguntas']);
        $this->assertSame($pergunta->cfg_perg_id, $item['perguntas'][0]['id']);
    }
}