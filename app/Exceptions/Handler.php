<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Contrato fae/v1 §2.3 (auditoria 27/11/2026, S-05): o 401 da API diz
        // se o token venceu ("token_expirado") ou não vale ("nao_autenticado").
        // Só nas rotas da API; o web continua redirecionando para o login.
        $this->renderable(function (AuthenticationException $e, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $request->attributes->get('token_expirado')
                ? response()->json(['message' => 'Sessão do app expirada. Entre novamente.', 'codigo' => 'token_expirado'], 401)
                : response()->json(['message' => 'Unauthenticated.', 'codigo' => 'nao_autenticado'], 401);
        });
    }
}
