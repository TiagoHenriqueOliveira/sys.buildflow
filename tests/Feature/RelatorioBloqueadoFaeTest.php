<?php

namespace Tests\Feature;

use App\Enums\AtendimentoRelatorioStatus;
use App\Enums\SetorModelo;
use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioAssinatura;
use App\Models\ConfigModelo;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoria 27/11/2026, S-02 — decisões do usuário (D-03):
 * - relatório Aprovado é somente leitura no servidor (web e api/fae/v1): 423;
 * - depois de Aprovado continuam permitidos a assinatura do cliente que ainda
 *   falta (uma vez; a segunda → 409), o envio (compartilhamento) e a
 *   observação interna;
 * - com a assinatura do cliente, ninguém devolve o relatório para outro status;
 * - antes da aprovação, o fluxo de campo continua como era (o cliente assina
 *   e pode refazer).
 */
class RelatorioBloqueadoFaeTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAFklEQVQImWP8//8/AwMDEwMDAwMDAwAkBgMBmjCi+wAAAABJRU5ErkJggg==';

    private Usuario $tecnico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tecnico = Usuario::factory()->tecnico()->create();
    }

    private function relatorio(AtendimentoRelatorioStatus $status): AtendimentoRelatorio
    {
        $modelo = ConfigModelo::create([
            'cfg_mod_nome' => 'Modelo', 'cfg_mod_setor' => SetorModelo::Assistencia->value, 'cfg_mod_ativo' => 1,
        ]);
        $natureza = NaturezaAtendimento::factory()->create(['nat_aten_mod_relatorio_id' => null, 'nat_aten_config_modelo_id' => $modelo->cfg_mod_id]);
        $atendimento = Atendimento::factory()->create(['aten_natureza_id' => $natureza->nat_aten_id, 'aten_usuario_id' => $this->tecnico->user_id]);

        return AtendimentoRelatorio::create([
            'aten_rel_atendimento_id' => $atendimento->aten_id,
            'aten_rel_config_modelo_id' => $modelo->cfg_mod_id,
            'aten_rel_data' => '2026-11-20',
            'aten_rel_status' => $status->value,
        ]);
    }

    private function token(Usuario $usuario): string
    {
        $this->app['auth']->forgetGuards();

        return $usuario->createToken('test')->plainTextToken;
    }

    private function assinarCliente(AtendimentoRelatorio $relatorio): void
    {
        AtendimentoRelatorioAssinatura::create([
            'aten_rel_ass_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_ass_path' => 'x/cliente.png',
            'aten_rel_ass_tipo' => 'cliente',
            'aten_rel_ass_nome' => 'Cliente',
            'aten_rel_ass_cpf' => '12345678900',
        ]);
    }

    public function test_toda_rota_de_escrita_da_api_devolve_423_no_relatorio_aprovado(): void
    {
        $id = $this->relatorio(AtendimentoRelatorioStatus::Aprovado)->aten_rel_id;
        $token = $this->token($this->tecnico);

        $rotas = [
            ['post', "respostas"], ['delete', "respostas/1"], ['delete', "respostas-fotos/1"],
            ['put', "informacoes-adicionais"], ['put', "horarios"], ['put', "clima"],
            ['post', "servicos"], ['delete', "servicos/1"], ['post', "pecas"], ['delete', "pecas/1"],
            ['post', "descricao-itens"], ['delete', "descricao-itens/1"],
            ['post', "ocorrencias"], ['delete', "ocorrencias/1"], ['post', "anexos"], ['delete', "anexos/foto/1"],
        ];

        foreach ($rotas as [$metodo, $caminho]) {
            $this->withToken($token)->json($metodo, "/api/fae/v1/relatorios/{$id}/{$caminho}", [])
                ->assertStatus(423)
                ->assertJsonPath('codigo', 'relatorio_bloqueado')
                ->assertJsonPath('status_relatorio', AtendimentoRelatorioStatus::Aprovado->value);
        }
    }

    public function test_toda_rota_de_escrita_da_web_devolve_423_no_relatorio_aprovado(): void
    {
        $id = $this->relatorio(AtendimentoRelatorioStatus::Aprovado)->aten_rel_id;
        $base = "/atendimentos-relatorios/{$id}";

        $rotas = [
            ['put', $base], ['post', "$base/dados"], ['post', "$base/horarios"], ['post', "$base/clima"],
            ['post', "$base/ocorrencias"], ['delete', "$base/ocorrencias/1"], ['post', "$base/servicos"], ['delete', "$base/servicos/1"],
            ['post', "$base/pecas"], ['delete', "$base/pecas/1"], ['post', "$base/descricao-itens"], ['delete', "$base/descricao-itens/1"],
            ['post', "$base/upload-anexos"], ['delete', "$base/anexos/foto/1"], ['post', "$base/respostas"],
            ['delete', "$base/respostas/1"], ['delete', "$base/respostas-fotos/1"],
            ['post', "$base/texto/aten_rel_informacoes_adicionais"],
        ];

        foreach ($rotas as [$metodo, $uri]) {
            $this->actingAs($this->tecnico)->json($metodo, $uri, ['valor' => 'x'])
                ->assertStatus(423)->assertJsonPath('codigo', 'relatorio_bloqueado');
        }
    }

    public function test_depois_de_aprovado_observacao_interna_e_envio_continuam_permitidos(): void
    {
        $relatorio = $this->relatorio(AtendimentoRelatorioStatus::Aprovado);
        $id = $relatorio->aten_rel_id;

        $this->actingAs($this->tecnico)->postJson("/atendimentos-relatorios/{$id}/texto/aten_rel_observacao_interna", ['valor' => 'Nota web'])->assertOk();
        $this->withToken($this->token($this->tecnico))->putJson("/api/fae/v1/relatorios/{$id}/observacao-interna", ['valor' => 'Nota app'])->assertOk();
        $this->assertSame('Nota app', $relatorio->fresh()->aten_rel_observacao_interna);

        $this->withToken($this->token($this->tecnico))->postJson("/api/fae/v1/relatorios/{$id}/compartilhamentos", ['canal' => 'whatsapp'])->assertCreated();
    }

    public function test_assinatura_do_cliente_que_falta_entra_uma_vez_depois_de_aprovado(): void
    {
        $id = $this->relatorio(AtendimentoRelatorioStatus::Aprovado)->aten_rel_id;
        $token = $this->token($this->tecnico);
        $corpo = ['cliente' => self::PNG, 'cliente_nome' => 'Maria da Silva', 'cliente_cpf' => '12345678900'];

        $this->withToken($token)->postJson("/api/fae/v1/relatorios/{$id}/assinaturas", $corpo)->assertOk();
        $this->assertDatabaseHas('atendimentos_relatorios_assinaturas', [
            'aten_rel_ass_relatorio_id' => $id, 'aten_rel_ass_tipo' => 'cliente',
            'aten_rel_ass_nome' => 'Maria da Silva', 'aten_rel_ass_cpf' => '12345678900',
        ]);

        $this->withToken($token)->postJson("/api/fae/v1/relatorios/{$id}/assinaturas", $corpo)
            ->assertStatus(409)->assertJsonPath('codigo', 'assinatura_ja_registrada');

        // Pela web também.
        $this->actingAs($this->tecnico)->postJson("/atendimentos-relatorios/{$id}/assinaturas", [
            'aten_rel_status' => AtendimentoRelatorioStatus::Aprovado->value,
            'assinatura_cliente' => self::PNG, 'assinatura_cliente_nome' => 'Outro', 'assinatura_cliente_cpf' => '1',
        ])->assertStatus(409);
    }

    public function test_assinatura_do_tecnico_depois_de_aprovado_e_bloqueada(): void
    {
        $id = $this->relatorio(AtendimentoRelatorioStatus::Aprovado)->aten_rel_id;

        $this->withToken($this->token($this->tecnico))->postJson("/api/fae/v1/relatorios/{$id}/assinaturas", ['tecnico' => self::PNG])
            ->assertStatus(423)->assertJsonPath('codigo', 'relatorio_bloqueado');
        $this->actingAs($this->tecnico)->postJson("/atendimentos-relatorios/{$id}/assinaturas", [
            'aten_rel_status' => AtendimentoRelatorioStatus::Aprovado->value, 'assinatura_responsavel' => self::PNG,
        ])->assertStatus(423);
    }

    public function test_antes_da_aprovacao_o_cliente_assina_e_pode_refazer_sem_perder_o_arquivo_anterior(): void
    {
        $id = $this->relatorio(AtendimentoRelatorioStatus::Preenchendo)->aten_rel_id;
        $token = $this->token($this->tecnico);

        $this->withToken($token)->postJson("/api/fae/v1/relatorios/{$id}/assinaturas", ['cliente' => self::PNG, 'cliente_nome' => 'Nome Antigo', 'cliente_cpf' => '111'])->assertOk();
        $primeiro = AtendimentoRelatorioAssinatura::where('aten_rel_ass_relatorio_id', $id)->value('aten_rel_ass_path');
        $this->travel(2)->seconds();
        $this->withToken($token)->postJson("/api/fae/v1/relatorios/{$id}/assinaturas", ['cliente' => self::PNG, 'cliente_nome' => 'Nome Novo', 'cliente_cpf' => '222'])->assertOk();

        $this->assertDatabaseCount('atendimentos_relatorios_assinaturas', 1);
        $atual = AtendimentoRelatorioAssinatura::where('aten_rel_ass_relatorio_id', $id)->first();
        $this->assertSame('Nome Novo', $atual->aten_rel_ass_nome);
        $this->assertNotSame($primeiro, $atual->aten_rel_ass_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($primeiro);
        $this->assertDatabaseHas('logs_auditoria', ['log_aud_modulo' => 'Relatorios', 'log_aud_acao' => 'ASSINATURA', 'log_aud_registro_id' => $id]);
    }

    public function test_assistencia_devolve_aprovado_sem_assinatura_do_cliente_e_a_edicao_volta(): void
    {
        $relatorio = $this->relatorio(AtendimentoRelatorioStatus::Aprovado);
        $id = $relatorio->aten_rel_id;
        $assistencia = Usuario::factory()->assistencia()->create();

        $this->withToken($this->token($assistencia))->putJson("/api/fae/v1/relatorios/{$id}/status", ['status' => AtendimentoRelatorioStatus::Revisar->value])->assertOk();

        $this->withToken($this->token($this->tecnico))
            ->putJson("/api/fae/v1/relatorios/{$id}/informacoes-adicionais", ['informacoes_adicionais' => 'Corrigido'])
            ->assertOk();
    }

    public function test_aprovado_e_assinado_pelo_cliente_nao_volta_de_status(): void
    {
        $relatorio = $this->relatorio(AtendimentoRelatorioStatus::Aprovado);
        $this->assinarCliente($relatorio);
        $id = $relatorio->aten_rel_id;
        $assistencia = Usuario::factory()->assistencia()->create();

        $this->withToken($this->token($assistencia))->putJson("/api/fae/v1/relatorios/{$id}/status", ['status' => AtendimentoRelatorioStatus::Revisar->value])
            ->assertStatus(423)->assertJsonPath('codigo', 'relatorio_bloqueado');

        $this->actingAs($assistencia)->postJson("/atendimentos-relatorios/{$id}/assinaturas", [
            'aten_rel_status' => AtendimentoRelatorioStatus::Revisar->value,
        ])->assertStatus(423);

        $this->assertSame(AtendimentoRelatorioStatus::Aprovado->value, (int) $relatorio->fresh()->aten_rel_status);
    }

    public function test_quem_nao_acessa_o_relatorio_recebe_403_e_nao_423(): void
    {
        $id = $this->relatorio(AtendimentoRelatorioStatus::Aprovado)->aten_rel_id;
        $outro = Usuario::factory()->tecnico()->create();

        $this->withToken($this->token($outro))->putJson("/api/fae/v1/relatorios/{$id}/informacoes-adicionais", ['informacoes_adicionais' => 'x'])->assertForbidden();
    }

    public function test_relatorio_nao_aprovado_continua_editavel(): void
    {
        $id = $this->relatorio(AtendimentoRelatorioStatus::Liberado)->aten_rel_id;

        $this->withToken($this->token($this->tecnico))
            ->putJson("/api/fae/v1/relatorios/{$id}/informacoes-adicionais", ['informacoes_adicionais' => 'Ok'])
            ->assertOk();
    }
}
