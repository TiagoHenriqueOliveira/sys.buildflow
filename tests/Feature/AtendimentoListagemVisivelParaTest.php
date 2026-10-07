<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Item 2.5 do plano de correções: consolida em Atendimento::visivelPara() o
// filtro "técnico só vê o seu, admin vê tudo" que estava reimplementado em
// ~6 lugares. Testa que o comportamento de listagem não mudou depois da
// consolidação — técnico A não vê atendimento/relatório de técnico B, admin
// vê os dois.
class AtendimentoListagemVisivelParaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tecnico_lista_so_os_proprios_atendimentos_api_mcl(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $tecnicoB = Usuario::factory()->tecnico()->create();
        $meu   = Atendimento::factory()->create(['aten_usuario_id' => $tecnicoA->user_id]);
        $alheio = Atendimento::factory()->create(['aten_usuario_id' => $tecnicoB->user_id]);
        $token = $tecnicoA->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/mcl/v1/atendimentos');

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($meu->aten_id));
        $this->assertFalse($ids->contains($alheio->aten_id));
    }

    public function test_admin_lista_todos_os_atendimentos_api_mcl(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $admin    = Usuario::factory()->administrador()->create();
        $atendimento = Atendimento::factory()->create(['aten_usuario_id' => $tecnicoA->user_id]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/mcl/v1/atendimentos');

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($atendimento->aten_id));
    }

    public function test_tecnico_lista_so_os_proprios_atendimentos_api_legada(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $tecnicoB = Usuario::factory()->tecnico()->create();
        $meu    = Atendimento::factory()->create(['aten_usuario_id' => $tecnicoA->user_id]);
        $alheio = Atendimento::factory()->create(['aten_usuario_id' => $tecnicoB->user_id]);
        $token = $tecnicoA->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/atendimentos');

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($meu->aten_id));
        $this->assertFalse($ids->contains($alheio->aten_id));
    }

    public function test_tecnico_lista_so_os_proprios_relatorios_api_legada(): void
    {
        $tecnicoA = Usuario::factory()->tecnico()->create();
        $tecnicoB = Usuario::factory()->tecnico()->create();
        $meu    = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnicoA->user_id]), 'atendimento')
            ->create();
        $alheio = AtendimentoRelatorio::factory()
            ->for(Atendimento::factory()->create(['aten_usuario_id' => $tecnicoB->user_id]), 'atendimento')
            ->create();
        $token = $tecnicoA->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/relatorios');

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($meu->aten_rel_id));
        $this->assertFalse($ids->contains($alheio->aten_rel_id));
    }
}
