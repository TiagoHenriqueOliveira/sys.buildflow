<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Item 2.6 do plano de correções: a API legada (Api\RelatoriosController)
// reimplementava a gravação de assinatura com uma cópia própria que nem
// gravava nome/CPF — unificada com App\Services\RelatorioMclService
// (mesma usada pela API Mcl), decisão do usuário de também passar a gravar
// nome/CPF na legada.
class AssinaturaUnificadaTest extends TestCase
{
    use RefreshDatabase;

    // PNG 2x2 branco, válido — gerado via GD (imagecreatetruecolor + imagepng)
    // pra garantir que imagecreatefromstring() no serviço não rejeite o fixture.
    private const PNG_BASE64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAFklEQVQImWP8//8/AwMDEwMDAwMDAwAkBgMBmjCi+wAAAABJRU5ErkJggg==';

    public function test_api_legada_grava_nome_e_cpf_da_assinatura(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create();
        $token = $tecnico->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson("/api/v1/relatorios/{$relatorio->aten_rel_id}/assinaturas", [
            'status'                      => 1,
            'assinatura_cliente'          => self::PNG_BASE64,
            'assinatura_cliente_nome'     => 'Maria da Silva',
            'assinatura_cliente_cpf'      => '12345678900',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('atendimentos_relatorios_assinaturas', [
            'aten_rel_ass_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_ass_tipo'         => 'cliente',
            'aten_rel_ass_nome'         => 'Maria da Silva',
            'aten_rel_ass_cpf'          => '12345678900',
        ]);
    }

    public function test_api_legada_atualiza_assinatura_existente_do_mesmo_tipo(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create();
        $token = $tecnico->createToken('test')->plainTextToken;

        // Primeira assinatura.
        $this->withToken($token)->postJson("/api/v1/relatorios/{$relatorio->aten_rel_id}/assinaturas", [
            'status'             => 1,
            'assinatura_cliente' => self::PNG_BASE64,
            'assinatura_cliente_nome' => 'Nome Antigo',
            'assinatura_cliente_cpf'  => '11111111111',
        ])->assertOk();

        // Reassina — deve ATUALIZAR o registro existente, não duplicar.
        $this->withToken($token)->postJson("/api/v1/relatorios/{$relatorio->aten_rel_id}/assinaturas", [
            'status'             => 1,
            'assinatura_cliente' => self::PNG_BASE64,
            'assinatura_cliente_nome' => 'Nome Novo',
            'assinatura_cliente_cpf'  => '22222222222',
        ])->assertOk();

        $this->assertDatabaseCount('atendimentos_relatorios_assinaturas', 1);
        $this->assertDatabaseHas('atendimentos_relatorios_assinaturas', [
            'aten_rel_ass_relatorio_id' => $relatorio->aten_rel_id,
            'aten_rel_ass_nome'         => 'Nome Novo',
            'aten_rel_ass_cpf'          => '22222222222',
        ]);
    }
}
