<?php

namespace Tests\Feature;

use App\Enums\NivelAcesso;
use App\Models\PerfilPermissao;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Perfis de Acesso (pedido do cliente 2026-11-16): acessos por tela
 * configuráveis por perfil, Administrador e usuário protegido com acesso
 * total automático, página personalizada de acesso negado.
 *
 * A suíte parte do PermissoesPadraoSeeder (ver Tests\TestCase); aqui cada
 * teste ajusta o perfil que precisa com PerfilPermissao::sincronizar.
 */
class PerfisAcessoFaeTest extends TestCase
{
    use RefreshDatabase;

    private function protegido(NivelAcesso $nivel = NivelAcesso::Administrador): Usuario
    {
        return Usuario::factory()->create([
            'user_nivel_acesso' => $nivel->value,
            'user_protegido' => true,
            'user_email' => '3iodigital26@gmail.com',
        ]);
    }

    // ─── Página de acesso negado ─────────────────────────────────────────

    public function test_sem_permissao_mostra_pagina_personalizada_com_redirecionamento(): void
    {
        PerfilPermissao::sincronizar(NivelAcesso::Tecnico->value, []);
        $tecnico = Usuario::factory()->tecnico()->create();

        $this->actingAs($tecnico)->get(route('usuarios.index'))
            ->assertForbidden()
            ->assertSee('Você não tem acesso a esta página')
            ->assertSee('Redirecionando para o Dashboard em', false)
            ->assertSee(route('dashboard'), false);
    }

    public function test_requisicao_json_sem_permissao_continua_recebendo_json(): void
    {
        $tecnico = Usuario::factory()->tecnico()->create();

        $this->actingAs($tecnico)->getJson(route('usuarios.index'))
            ->assertForbidden()
            ->assertJsonStructure(['message']);
    }

    // ─── Permissões por tela ────────────────────────────────────────────

    public function test_permissao_ver_libera_listagem_mas_nao_gravacao(): void
    {
        PerfilPermissao::sincronizar(NivelAcesso::Tecnico->value, ['segmentos.ver']);
        $tecnico = Usuario::factory()->tecnico()->create();

        $this->actingAs($tecnico)->get(route('segmentos.index'))
            ->assertOk()
            ->assertDontSee('Cadastrar');

        $this->actingAs($tecnico)->post(route('segmentos.store'), ['seg_descricao' => 'Laticínio'])
            ->assertForbidden();
    }

    public function test_permissao_editar_implica_ver(): void
    {
        PerfilPermissao::sincronizar(NivelAcesso::Tecnico->value, ['segmentos.editar']);

        $this->assertEqualsCanonicalizing(
            ['segmentos.editar', 'segmentos.ver'],
            PerfilPermissao::chavesDoNivel(NivelAcesso::Tecnico->value)
        );
    }

    public function test_menu_mostra_so_as_telas_permitidas(): void
    {
        PerfilPermissao::sincronizar(NivelAcesso::Tecnico->value, ['atendimentos.ver']);
        $tecnico = Usuario::factory()->tecnico()->create();

        $this->actingAs($tecnico)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('atendimentos.index'), false)
            ->assertDontSee(route('orcamentos.index'), false)
            ->assertDontSee('Configurações');
    }

    public function test_administrador_tem_acesso_total_sem_nenhuma_permissao_cadastrada(): void
    {
        PerfilPermissao::query()->delete();
        PerfilPermissao::limparCache();
        $admin = Usuario::factory()->administrador()->create();

        foreach (['usuarios.index', 'segmentos.index', 'orcamentos.index', 'atendimentos.index', 'perfis-acesso.index'] as $rota) {
            $this->actingAs($admin)->get(route($rota))->assertOk();
        }
    }

    // ─── Usuário protegido ──────────────────────────────────────────────

    public function test_usuario_protegido_tem_acesso_total_mesmo_com_nivel_nao_admin(): void
    {
        $protegido = $this->protegido(NivelAcesso::Tecnico);

        $this->assertTrue($protegido->temAcessoTotal());
        $this->actingAs($protegido)->get(route('perfis-acesso.index'))->assertOk();
        $this->actingAs($protegido)->get(route('orcamentos.index'))->assertOk();
    }

    public function test_usuario_protegido_nao_aparece_na_listagem_nem_para_ele_mesmo(): void
    {
        $protegido = $this->protegido();
        $admin = Usuario::factory()->administrador()->create();

        $this->actingAs($admin)->get(route('usuarios.index'))
            ->assertOk()
            ->assertDontSee('3iodigital26@gmail.com');

        // Logado como ele, o e-mail aparece no menu do usuário (topbar) —
        // o que não pode é aparecer como linha da listagem.
        $this->actingAs($protegido)->get(route('usuarios.index'))
            ->assertOk()
            ->assertDontSee('<td>3iodigital26@gmail.com</td>', false)
            ->assertDontSee('data-email="3iodigital26@gmail.com"', false);
    }

    public function test_ninguem_alem_dele_edita_o_usuario_protegido(): void
    {
        $protegido = $this->protegido();
        $admin = Usuario::factory()->administrador()->create();

        $this->actingAs($admin)->put(route('usuarios.update', $protegido->user_id), [
            'user_nivel_acesso' => NivelAcesso::Tecnico->value,
            'user_nome' => 'Invasor',
            'user_email' => '3iodigital26@gmail.com',
        ])->assertForbidden();

        $this->assertNotSame('Invasor', $protegido->fresh()->user_nome);
    }

    public function test_usuario_protegido_se_edita_por_minha_conta_sem_mudar_perfil(): void
    {
        $protegido = $this->protegido();

        $this->actingAs($protegido)->put(route('minha-conta.update'), [
            'user_nome' => '3io Digital',
            'user_email' => '3iodigital26@gmail.com',
            'user_nivel_acesso' => NivelAcesso::Tecnico->value, // ignorado
        ])->assertRedirect(route('minha-conta.edit'));

        $protegido->refresh();
        $this->assertSame('3io Digital', $protegido->user_nome);
        $this->assertSame(NivelAcesso::Administrador->value, $protegido->user_nivel_acesso);
    }

    public function test_usuario_protegido_continua_admin_e_ativo_mesmo_se_tentar_se_rebaixar(): void
    {
        $protegido = $this->protegido();

        $this->actingAs($protegido)->put(route('usuarios.update', $protegido->user_id), [
            'user_nivel_acesso' => NivelAcesso::Tecnico->value,
            'user_nome' => '3io Digital',
            'user_email' => '3iodigital26@gmail.com',
            'user_ativo' => 0,
        ])->assertRedirect();

        $protegido->refresh();
        $this->assertSame(NivelAcesso::Administrador->value, $protegido->user_nivel_acesso);
        $this->assertTrue($protegido->user_ativo);
    }

    // ─── Escalada de privilégio ─────────────────────────────────────────

    public function test_quem_edita_usuarios_sem_ser_admin_nao_promove_a_administrador(): void
    {
        PerfilPermissao::sincronizar(NivelAcesso::Assistencia->value, ['usuarios.editar']);
        $assistencia = Usuario::factory()->assistencia()->create();
        $admin = Usuario::factory()->administrador()->create();

        $this->actingAs($assistencia)->post(route('usuarios.store'), [
            'user_nivel_acesso' => NivelAcesso::Administrador->value,
            'user_nome' => 'Novo Admin',
            'user_email' => 'novo.admin@exemplo.com',
            'user_senha' => 'segredo123',
            'user_senha_confirmation' => 'segredo123',
        ])->assertSessionHasErrors('user_nivel_acesso');

        $this->actingAs($assistencia)->put(route('usuarios.update', $admin->user_id), [
            'user_nivel_acesso' => NivelAcesso::Tecnico->value,
            'user_nome' => $admin->user_nome,
            'user_email' => $admin->user_email,
        ])->assertForbidden();

        // Cadastrar um técnico continua permitido.
        $this->actingAs($assistencia)->post(route('usuarios.store'), [
            'user_nivel_acesso' => NivelAcesso::Tecnico->value,
            'user_nome' => 'Novo Técnico',
            'user_email' => 'novo.tecnico@exemplo.com',
            'user_senha' => 'segredo123',
            'user_senha_confirmation' => 'segredo123',
        ])->assertSessionHasNoErrors();
    }

    // ─── Tela Perfis de Acesso ──────────────────────────────────────────

    public function test_so_administrador_abre_e_salva_perfis_de_acesso(): void
    {
        $comercial = Usuario::factory()->comercial()->create();
        $this->actingAs($comercial)->get(route('perfis-acesso.index'))->assertForbidden();
        $this->actingAs($comercial)->put(route('perfis-acesso.update'), [])->assertForbidden();

        $admin = Usuario::factory()->administrador()->create();
        $this->actingAs($admin)->get(route('perfis-acesso.index'))
            ->assertOk()
            ->assertSee('Atua como vendedor');

        $this->actingAs($admin)->put(route('perfis-acesso.update'), [
            'permissoes' => [
                NivelAcesso::Tecnico->value => ['orcamentos.editar', 'chave.inexistente'],
                NivelAcesso::Vendedor->value => ['vendedor'],
            ],
        ])->assertRedirect(route('perfis-acesso.index'));

        PerfilPermissao::limparCache();
        $this->assertEqualsCanonicalizing(['orcamentos.editar', 'orcamentos.ver'], PerfilPermissao::chavesDoNivel(NivelAcesso::Tecnico->value));
        $this->assertSame(['vendedor'], PerfilPermissao::chavesDoNivel(NivelAcesso::Vendedor->value));
        // Perfil não enviado no formulário = tudo desmarcado.
        $this->assertSame([], PerfilPermissao::chavesDoNivel(NivelAcesso::Comercial->value));
    }

    public function test_combos_de_vendedor_seguem_o_papel_atua_como_vendedor(): void
    {
        PerfilPermissao::sincronizar(NivelAcesso::Comercial->value, ['orcamentos.ver']);
        PerfilPermissao::sincronizar(NivelAcesso::Vendedor->value, ['vendedor']);
        $comercial = Usuario::factory()->comercial()->create();
        $vendedor = Usuario::factory()->vendedor()->create();
        Usuario::factory()->administrador()->create();

        $ids = Usuario::vendedores()->pluck('user_id')->all();

        $this->assertSame([$vendedor->user_id], $ids);
        $this->assertNotContains($comercial->user_id, $ids);
    }

    // ─── API mobile ─────────────────────────────────────────────────────

    public function test_api_me_devolve_permissoes_e_bloqueia_endpoint_sem_permissao(): void
    {
        PerfilPermissao::sincronizar(NivelAcesso::Tecnico->value, ['atendimentos.ver', 'relatorios.ver']);
        $tecnico = Usuario::factory()->tecnico()->create();
        $token = $tecnico->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/fae/v1/me')
            ->assertOk()
            ->assertJsonPath('acesso_total', false)
            ->assertJsonPath('permissoes', ['atendimentos.ver', 'relatorios.ver']);

        $this->withToken($token)->getJson('/api/fae/v1/orcamentos')->assertForbidden();
        $this->withToken($token)->getJson('/api/fae/v1/atendimentos')->assertOk();
    }

    public function test_api_me_de_administrador_traz_todas_as_permissoes(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->withToken($admin->createToken('test')->plainTextToken)->getJson('/api/fae/v1/me')
            ->assertOk()
            ->assertJsonPath('acesso_total', true)
            ->assertJsonCount(count(\App\Support\Permissoes::chavesDeTela()), 'permissoes');
    }
}
