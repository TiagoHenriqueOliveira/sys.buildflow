<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Revisão de segurança de MINOR (CLAUDE.md) — fim da Fase 0 da auditoria
 * (27-28/11/2026): toda rota fora de auth/sanctum é intencional e está nesta
 * lista. Rota nova sem autenticação faz este teste falhar até ser revisada.
 */
class SegurancaRotasFaeTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLICAS = [
        '/',                       // tela de login (guest)
        '_ignition/execute-solution', // ferramenta de depuração: 404 sem APP_DEBUG
        '_ignition/health-check',
        '_ignition/update-config',
        'api/fae/v1/app/versao',   // o app confere a versão antes do login
        'api/fae/v1/login',        // login do app (limitador + bloqueio por conta)
        'api/fae/v1/login/mfa',    // segundo passo do login do app (desafio de uso único)
        'apk/{arquivo}',           // instalador do app
        'login',                   // login web (guest + limitador)
        'login/codigo',            // 2º passo do MFA (guest + limitador)
        'login/esquecer',          // "esquecer este navegador" (guest)
        'login/redefinir-senha',   // "Esqueci minha senha" com código do admin + MFA (guest + limitador)
        'sanctum/csrf-cookie',
    ];

    public function test_rotas_sem_autenticacao_sao_so_as_previstas(): void
    {
        $publicas = collect(Route::getRoutes()->getRoutes())
            ->reject(fn ($rota) => collect($rota->gatherMiddleware())
                ->contains(fn ($m) => is_string($m) && ($m === 'auth' || str_starts_with($m, 'auth:'))))
            ->map(fn ($rota) => $rota->uri())
            ->unique()->sort()->values()->all();

        $esperadas = collect(self::PUBLICAS)->sort()->values()->all();

        $this->assertSame($esperadas, $publicas);
    }

    /** Auditoria S-19: manutenção que altera arquivos não roda mais por GET. */
    public function test_rotas_de_manutencao_por_get_nao_existem(): void
    {
        $admin = \App\Models\Usuario::factory()->administrador()->create();

        foreach (['/admin/codigo/remover-bom?fix=1', '/admin/midia/remover-bom?fix=1', '/admin/midia/diagnosticar?fix=1'] as $uri) {
            $this->actingAs($admin)->get($uri)->assertNotFound();
        }
        $this->assertFalse(Route::has('admin.codigo.remover-bom'));
    }

    public function test_ferramenta_de_depuracao_fica_desligada_sem_app_debug(): void
    {
        config(['app.debug' => false]);

        $this->get('/_ignition/health-check')->assertNotFound();
    }
}
