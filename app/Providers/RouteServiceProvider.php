<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // Usuários autenticados (app mobile) recebem limite maior para suportar
            // rajadas de sincronização offline e navegação rápida entre telas.
            // Auditoria S-23 (28/11/2026): este limitador roda antes do
            // auth:sanctum da rota, então o usuário vem do token (guard
            // sanctum) — antes caía sempre no ramo por IP, e técnicos na
            // mesma rede dividiam o limite. A chave é o user_id (a antiga
            // usava ->id, que não existe neste model).
            $usuario = $request->bearerToken() ? $request->user('sanctum') : null;

            if ($usuario) {
                return Limit::perMinute(300)->by('usuario:' . $usuario->user_id);
            }

            return Limit::perMinute(60)->by('ip:' . $request->ip());
        });

        // Limite dedicado e mais rígido para login — antes compartilhava o
        // limite genérico de 60/min/IP com toda rota pública, sem nenhuma
        // proteção específica contra força bruta de senha.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip() . '|' . $request->input('email'));
        });
    }
}
