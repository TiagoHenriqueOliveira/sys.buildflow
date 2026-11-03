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
 * Pedido do cliente (2026-11-03): técnico deixa o relatório Preenchendo,
 * Revisar ou "Liberado para aprovação" (exige a assinatura do técnico); só
 * Assistência/Administrador aprova, e só a partir de Liberado. Ao liberar,
 * os usuários de perfil Assistência são notificados.
 */
class AtendimentoRelatorioAprovacaoFaeTest extends TestCase
{
    use RefreshDatabase;

    private function criarRelatorio(Usuario $tecnico, AtendimentoRelatorioStatus $status = AtendimentoRelatorioStatus::Preenchendo): AtendimentoRelatorio
    {
        // A tela e o PDF exigem um modelo (abort 500 sem nenhum).
        $modelo = ConfigModelo::create([
            'cfg_mod_nome' => 'Manutenção Preventiva ETE',
            'cfg_mod_setor' => SetorModelo::Assistencia->value,
            'cfg_mod_ativo' => 1,
        ]);
        $natureza = NaturezaAtendimento::factory()->create([
            'nat_aten_mod_relatorio_id' => null,
            'nat_aten_config_modelo_id' => $modelo->cfg_mod_id,
        ]);
        $atendimento = Atendimento::factory()->create([
            'aten_natureza_id' => $natureza->nat_aten_id,
            'aten_usuario_id' => $tecnico->user_id,
        ]);

        return AtendimentoRelatorio::create([
            'aten_rel_atendimento_id' => $atendimento->aten_id,
            'aten_rel_modelo_relatorio_id' => null,
            'aten_rel_config_modelo_id' => $modelo->cfg_mod_id,
            'aten_rel_data' => now()->toDateString(),
            'aten_rel_status' => $status->value,
        ]);
    }

    private function assinarComoTecnico(AtendimentoRelatorio $relatorio): void
    {
        AtendimentoRelatorioAssinatura::create([
            'aten_rel_ass_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_ass_path' => "atendimentos_relatorios/{$relatorio->aten_rel_id}/assinaturas/responsavel.png",
            'aten_rel_ass_tipo' => 'responsavel',
            'aten_rel_ass_assinado_em' => now(),
        ]);
    }

    private function mudarStatusWeb(Usuario $usuario, AtendimentoRelatorio $relatorio, AtendimentoRelatorioStatus $status, array $extra = [])
    {
        return $this->actingAs($usuario)->postJson(
            route('atendimentos-relatorios.update-assinaturas', $relatorio->aten_rel_id),
            ['aten_rel_status' => $status->value] + $extra
        );
    }

    // ─── Web ─────────────────────────────────────────────────────────────

    public function test_tecnico_nao_pode_aprovar_relatorio(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico, AtendimentoRelatorioStatus::Liberado);
        $this->assinarComoTecnico($relatorio);

        $this->mudarStatusWeb($tecnico, $relatorio, AtendimentoRelatorioStatus::Aprovado)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Somente usuários de perfil Assistência podem aprovar relatórios.');

        $this->assertSame(AtendimentoRelatorioStatus::Liberado->value, $relatorio->fresh()->aten_rel_status);
    }

    public function test_assistencia_aprova_relatorio_liberado_de_outro_tecnico(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $assistencia = Usuario::factory()->assistencia()->create();
        $relatorio = $this->criarRelatorio($tecnico, AtendimentoRelatorioStatus::Liberado);

        $this->mudarStatusWeb($assistencia, $relatorio, AtendimentoRelatorioStatus::Aprovado, [
            'observacao_supervisor' => 'Conferido.',
        ])->assertOk();

        $relatorio->refresh();
        $this->assertSame(AtendimentoRelatorioStatus::Aprovado->value, $relatorio->aten_rel_status);
        $this->assertSame($assistencia->user_id, $relatorio->aten_rel_aprovado_por);
        $this->assertSame('Conferido.', $relatorio->aten_rel_observacao_supervisor);
    }

    public function test_aprovacao_exige_status_liberado(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $assistencia = Usuario::factory()->assistencia()->create();
        $relatorio = $this->criarRelatorio($tecnico, AtendimentoRelatorioStatus::Preenchendo);
        $this->assinarComoTecnico($relatorio);

        $this->mudarStatusWeb($assistencia, $relatorio, AtendimentoRelatorioStatus::Aprovado)
            ->assertStatus(422);

        $this->assertSame(AtendimentoRelatorioStatus::Preenchendo->value, $relatorio->fresh()->aten_rel_status);
    }

    public function test_liberar_para_aprovacao_exige_assinatura_do_tecnico(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico);

        $this->mudarStatusWeb($tecnico, $relatorio, AtendimentoRelatorioStatus::Liberado)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Para liberar para aprovação, registre antes a assinatura do técnico.');

        $this->assertSame(AtendimentoRelatorioStatus::Preenchendo->value, $relatorio->fresh()->aten_rel_status);
    }

    public function test_liberar_com_assinatura_notifica_usuarios_assistencia_ativos(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $assistencia = Usuario::factory()->assistencia()->create();
        $assistenciaInativa = Usuario::factory()->assistencia()->inativo()->create();
        $outroTecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico);
        $this->assinarComoTecnico($relatorio);

        $this->mudarStatusWeb($tecnico, $relatorio, AtendimentoRelatorioStatus::Liberado)->assertOk();

        $this->assertSame(AtendimentoRelatorioStatus::Liberado->value, $relatorio->fresh()->aten_rel_status);
        $this->assertDatabaseHas('notificacoes', [
            'notif_usuario_id' => $assistencia->user_id,
            'notif_tipo' => 'relatorio_liberado',
            'notif_link' => route('atendimentos-relatorios.show', $relatorio->aten_rel_id),
        ]);
        $this->assertDatabaseMissing('notificacoes', ['notif_usuario_id' => $assistenciaInativa->user_id]);
        $this->assertDatabaseMissing('notificacoes', ['notif_usuario_id' => $outroTecnico->user_id]);
    }

    public function test_reenviar_mesmo_status_nao_notifica_de_novo(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        Usuario::factory()->assistencia()->create();
        $relatorio = $this->criarRelatorio($tecnico, AtendimentoRelatorioStatus::Liberado);
        $this->assinarComoTecnico($relatorio);

        $this->mudarStatusWeb($tecnico, $relatorio, AtendimentoRelatorioStatus::Liberado)->assertOk();

        $this->assertDatabaseCount('notificacoes', 0);
    }

    public function test_tecnico_nao_altera_observacao_do_supervisor(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico, AtendimentoRelatorioStatus::Revisar);
        $relatorio->update(['aten_rel_observacao_supervisor' => 'Refazer a foto da bomba.']);

        $this->mudarStatusWeb($tecnico, $relatorio, AtendimentoRelatorioStatus::Preenchendo, [
            'observacao_supervisor' => 'Apagado pelo técnico',
        ])->assertOk();

        $this->assertSame('Refazer a foto da bomba.', $relatorio->fresh()->aten_rel_observacao_supervisor);
    }

    public function test_tela_mostra_botao_aprovado_so_para_quem_pode_aprovar(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $assistencia = Usuario::factory()->assistencia()->create();
        $relatorio = $this->criarRelatorio($tecnico);

        $this->actingAs($tecnico)->get(route('atendimentos-relatorios.show', $relatorio->aten_rel_id))
            ->assertOk()
            ->assertSee('Liberado para aprovação')
            ->assertDontSee('id="aten_rel_status_2"', false);

        $this->actingAs($assistencia)->get(route('atendimentos-relatorios.show', $relatorio->aten_rel_id))
            ->assertOk()
            ->assertSee('id="aten_rel_status_2"', false);
    }

    // ─── API mobile (fae/v1) ─────────────────────────────────────────────

    public function test_api_tecnico_aprovando_recebe_422_sem_rebaixamento_silencioso(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = $this->criarRelatorio($tecnico, AtendimentoRelatorioStatus::Liberado);
        $token = $tecnico->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/status", ['status' => AtendimentoRelatorioStatus::Aprovado->value])
            ->assertStatus(422)
            ->assertJsonPath('status', AtendimentoRelatorioStatus::Liberado->value);

        $this->assertSame(AtendimentoRelatorioStatus::Liberado->value, $relatorio->fresh()->aten_rel_status);
    }

    public function test_api_tecnico_libera_e_assistencia_aprova(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $assistencia = Usuario::factory()->assistencia()->create();
        $relatorio = $this->criarRelatorio($tecnico);
        $this->assinarComoTecnico($relatorio);

        $this->withToken($tecnico->createToken('test')->plainTextToken)
            ->putJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/status", ['status' => AtendimentoRelatorioStatus::Liberado->value])
            ->assertOk()
            ->assertJsonPath('status', AtendimentoRelatorioStatus::Liberado->value);

        $this->assertDatabaseHas('notificacoes', [
            'notif_usuario_id' => $assistencia->user_id,
            'notif_tipo' => 'relatorio_liberado',
        ]);

        $this->app['auth']->forgetGuards();

        $this->withToken($assistencia->createToken('test')->plainTextToken)
            ->putJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}/status", ['status' => AtendimentoRelatorioStatus::Aprovado->value])
            ->assertOk()
            ->assertJsonPath('status', AtendimentoRelatorioStatus::Aprovado->value);

        $relatorio->refresh();
        $this->assertSame(AtendimentoRelatorioStatus::Aprovado->value, $relatorio->aten_rel_status);
        $this->assertSame($assistencia->user_id, $relatorio->aten_rel_aprovado_por);
    }

    public function test_api_detalhe_informa_se_usuario_pode_aprovar(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $assistencia = Usuario::factory()->assistencia()->create();
        $relatorio = $this->criarRelatorio($tecnico);

        $this->withToken($tecnico->createToken('test')->plainTextToken)
            ->getJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}")
            ->assertOk()
            ->assertJsonPath('data.pode_aprovar', false);

        $this->app['auth']->forgetGuards();

        $this->withToken($assistencia->createToken('test')->plainTextToken)
            ->getJson("/api/fae/v1/relatorios/{$relatorio->aten_rel_id}")
            ->assertOk()
            ->assertJsonPath('data.pode_aprovar', true);
    }
}
