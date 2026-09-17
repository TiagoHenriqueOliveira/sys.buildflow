<?php

namespace Tests\Feature\Api;

use App\Models\Notificacao;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pedido do cliente (2026-09-17) - Sistema de Notificacoes tambem
 * disponivel pro app mobile (mesma fonte de dado do sino do Web).
 */
class NotificacoesApiFaeTest extends TestCase
{
    use RefreshDatabase;

    private function token(Usuario $usuario): string
    {
        return $usuario->createToken('test')->plainTextToken;
    }

    public function test_lista_notificacoes_do_proprio_usuario(): void
    {
        $usuario = Usuario::factory()->comercial()->create();
        $outro = Usuario::factory()->comercial()->create();
        Notificacao::notificar($usuario->user_id, 'teste', 'Título', 'Mensagem 1');
        Notificacao::notificar($outro->user_id, 'teste', 'Título', 'Mensagem de outro usuário');

        $response = $this->withToken($this->token($usuario))->getJson('/api/fae/v1/notificacoes');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['mensagem' => 'Mensagem 1']);
        $response->assertJsonPath('nao_lidas', 1);
    }

    public function test_marca_notificacao_como_lida_e_como_nao_lida(): void
    {
        $usuario = Usuario::factory()->comercial()->create();
        $notificacao = Notificacao::notificar($usuario->user_id, 'teste', 'Título', 'Mensagem');

        $this->withToken($this->token($usuario))
            ->postJson("/api/fae/v1/notificacoes/{$notificacao->notif_id}/marcar-lida")
            ->assertOk();
        $this->assertDatabaseHas('notificacoes', ['notif_id' => $notificacao->notif_id, 'notif_lida' => 1]);

        $this->withToken($this->token($usuario))
            ->postJson("/api/fae/v1/notificacoes/{$notificacao->notif_id}/marcar-nao-lida")
            ->assertOk();
        $this->assertDatabaseHas('notificacoes', ['notif_id' => $notificacao->notif_id, 'notif_lida' => 0]);
    }

    public function test_nao_marca_notificacao_de_outro_usuario(): void
    {
        $dono = Usuario::factory()->comercial()->create();
        $outro = Usuario::factory()->comercial()->create();
        $notificacao = Notificacao::notificar($dono->user_id, 'teste', 'Título', 'Mensagem');

        $this->withToken($this->token($outro))
            ->postJson("/api/fae/v1/notificacoes/{$notificacao->notif_id}/marcar-lida")
            ->assertOk();

        $this->assertDatabaseHas('notificacoes', ['notif_id' => $notificacao->notif_id, 'notif_lida' => 0]);
    }
}