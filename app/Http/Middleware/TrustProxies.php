<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * Auditoria S-23 (28/11/2026): lista configurável por TRUSTED_PROXIES
     * (config/proxies.php) — "cloudflare" usa as faixas da Cloudflare; vazio
     * mantém o comportamento de antes (nenhum proxy confiável).
     */
    protected function proxies()
    {
        $valor = trim((string) config('proxies.confiaveis'));

        if ($valor === '') {
            return null;
        }

        if ($valor === '*') {
            return '*';
        }

        $lista = [];
        foreach (array_filter(array_map('trim', explode(',', $valor))) as $item) {
            $lista = array_merge($lista, $item === 'cloudflare' ? (array) config('proxies.cloudflare') : [$item]);
        }

        return $lista;
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
