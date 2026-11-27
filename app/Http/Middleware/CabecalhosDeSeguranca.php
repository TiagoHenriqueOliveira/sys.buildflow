<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança das páginas web (2026-11-27, como no promaq-sys).
 *
 * - CSP só em respostas HTML. Diferente do promaq, as telas daqui ainda têm
 *   <script> e eventos inline (18 views), então script-src precisa de
 *   'unsafe-inline' — a CSP ainda limita DE ONDE vem código: só o próprio
 *   site e o cdnjs (Leaflet dos mapas). 'unsafe-eval' é exigido pelo Alpine.
 *   Imagens: o site, data:/blob: (prévia de fotos) e os mapas do
 *   OpenStreetMap.
 * - Permissions-Policy libera câmera e localização só para o próprio site
 *   ("Usar minha localização" e fotos dos relatórios).
 * - HSTS só quando a requisição já é HTTPS.
 * - Com o Vite em modo dev (npm run dev), a CSP fica de fora.
 */
class CabecalhosDeSeguranca
{
    public function __construct(private readonly Vite $vite) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $cabecalhos = $response->headers;

        $cabecalhos->set('X-Content-Type-Options', 'nosniff');
        $cabecalhos->set('X-Frame-Options', 'SAMEORIGIN');
        $cabecalhos->set('Referrer-Policy', 'same-origin');
        $cabecalhos->set('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=(), payment=()');
        $cabecalhos->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $cabecalhos->set('Strict-Transport-Security', 'max-age=31536000');
        }

        $paginaDeErroDebug = config('app.debug') && $response->getStatusCode() >= 500;

        if (! $this->vite->isRunningHot() && ! $paginaDeErroDebug && ! $cabecalhos->has('Content-Security-Policy')
            && str_starts_with((string) $cabecalhos->get('Content-Type'), 'text/html')) {
            $cabecalhos->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com",
                "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com",
                "img-src 'self' data: blob: https://*.tile.openstreetmap.org https://cdnjs.cloudflare.com",
                "font-src 'self' data:",
                "media-src 'self' blob:",
                "connect-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
            ]));
        }

        return $response;
    }
}
