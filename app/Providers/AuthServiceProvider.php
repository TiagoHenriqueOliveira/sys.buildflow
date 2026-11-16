<?php

namespace App\Providers;

use App\Models\Atendimento;
use App\Models\Usuario;
use App\Policies\AtendimentoPolicy;
use App\Support\Permissoes;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Atendimento::class => AtendimentoPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Permissões por tela do cadastro "Perfis de Acesso" (2026-11-16):
        // habilita `can:orcamentos.ver` nas rotas e @can no Blade. Só
        // responde pelas chaves do catálogo — qualquer outra ability (ex.:
        // a policy 'acessar' de Atendimento) segue o fluxo normal (null).
        Gate::before(function (Usuario $usuario, string $ability) {
            return Permissoes::existe($ability) ? $usuario->temPermissao($ability) : null;
        });
    }
}
