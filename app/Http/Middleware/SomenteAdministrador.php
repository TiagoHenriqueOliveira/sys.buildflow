<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SomenteAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::user()->user_nivel_acesso !== 0) {
            abort(403);
        }

        return $next($request);
    }
}
