<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Item 2.4 do plano de correções: move a validação inline de 6 endpoints da
// API Mcl para Form Requests dedicados (refatoração pura). Confirma que o
// 422 em payload inválido continua acontecendo exatamente como antes.
class AtendimentoRelatorioMclValidacaoTest extends TestCase
{
    use RefreshDatabase;

    private function relatorioDoTecnico(): array
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $relatorio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]), 'atendimento')
            ->create();
        $token = $tecnico->createToken('test')->plainTextToken;

        return [$tecnico, $relatorio, $token];
    }

    public function test_update_horarios_rejeita_formato_invalido(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)
            ->putJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}/horarios", ['entrada' => '25:99'])
            ->assertStatus(422);
    }

    public function test_update_clima_rejeita_valor_fora_do_enum(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)
            ->putJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}/clima", ['manha' => 'furacao'])
            ->assertStatus(422);
    }

    public function test_store_descricao_item_rejeita_sem_texto(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)
            ->postJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}/descricao-itens", [])
            ->assertStatus(422);
    }

    public function test_upload_anexos_rejeita_extensao_nao_permitida(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();
        \Illuminate\Http\UploadedFile::fake();

        $this->withToken($token)
            ->postJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}/anexos", [
                'fotos' => [\Illuminate\Http\UploadedFile::fake()->create('malware.exe', 10)],
            ])
            ->assertStatus(422);
    }

    public function test_atendimento_update_status_rejeita_valor_fora_do_enum(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();
        $atendimento = Atendimento::factory()->create(['aten_usuario_id' => $tecnico->user_id]);
        $token = $tecnico->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->putJson("/api/mcl/v1/atendimentos/{$atendimento->aten_id}/status", ['status' => 99])
            ->assertStatus(422);
    }

    public function test_update_informacoes_adicionais_aceita_payload_valido(): void
    {
        [, $relatorio, $token] = $this->relatorioDoTecnico();

        $this->withToken($token)
            ->putJson("/api/mcl/v1/relatorios/{$relatorio->aten_rel_id}/informacoes-adicionais", [
                'informacoes_adicionais' => 'Peça trocada conforme solicitado.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('atendimentos_relatorios', [
            'aten_rel_id'                     => $relatorio->aten_rel_id,
            'aten_rel_informacoes_adicionais' => 'Peça trocada conforme solicitado.',
        ]);
    }
}
