<?php

namespace App\Http\Controllers;

use App\Enums\NivelAcesso;
use App\Models\PerfilPermissao;
use App\Services\AuditService;
use App\Support\Permissoes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Perfis de Acesso (pedido do cliente 2026-11-16): define, por perfil
 * (Técnico, Comercial, Assistência, Vendedor), quais telas cada um vê e
 * edita, e quem "Atua como vendedor". Administrador não aparece: tem acesso
 * total automático. A tela em si é só de Administrador (middleware
 * `admin`), senão quem a acessasse poderia se dar mais acesso.
 */
class PerfisAcessoController extends Controller
{
    public function index(): View
    {
        return view('perfis-acesso.index', [
            'perfis' => self::perfisConfiguraveis(),
            'telas' => Permissoes::telas(),
            'concedidas' => PerfilPermissao::porNivel(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'permissoes' => ['nullable', 'array'],
            'permissoes.*' => ['array'],
            'permissoes.*.*' => ['string'],
        ]);

        $enviadas = $request->input('permissoes', []);
        $antes = PerfilPermissao::porNivel();

        foreach (self::perfisConfiguraveis() as $perfil) {
            PerfilPermissao::sincronizar($perfil->value, (array) ($enviadas[$perfil->value] ?? []));
        }

        AuditService::log('PerfisAcesso', 'ATUALIZAR', 0, $antes, PerfilPermissao::porNivel());

        return redirect()
            ->route('perfis-acesso.index')
            ->with('success', 'Perfis de acesso atualizados.');
    }

    /** @return NivelAcesso[] */
    private static function perfisConfiguraveis(): array
    {
        return array_values(array_filter(
            NivelAcesso::cases(),
            fn (NivelAcesso $n) => $n !== NivelAcesso::Administrador
        ));
    }
}
