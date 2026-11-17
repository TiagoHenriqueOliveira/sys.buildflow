<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Extrai latitude/longitude de um link do Google Maps.
 *
 * Desde 2026-09-14 (pedido do cliente) a localização do cliente é informada
 * só pelo link do Google Maps colado no cadastro — não há mais campo de
 * latitude/longitude na tela. Os mapas (Mapa de Demandas BF08, Mapa de
 * Relações CRM07 e as rotas equivalentes da API) precisam das coordenadas
 * para posicionar o marcador, então elas passam a ser DERIVADAS do link ao
 * salvar (2026-11-17: antes só o botão "Usar minha localização" gravava
 * coordenadas, e cliente com link colado ficava fora dos mapas).
 *
 * Formatos reconhecidos, em ordem de precisão:
 *  1. `...!3d<lat>!4d<lng>` — pino do lugar no parâmetro `data` (link de
 *     "place"); usa a última ocorrência, que é a do lugar selecionado.
 *  2. `?q=<lat>,<lng>` (também `query`, `ll`, `destination`, `center`).
 *  3. `/@<lat>,<lng>,...` — centro da câmera/visão no momento da cópia.
 *  4. `/maps/search/<lat>,<lng>` e `/maps/place/<lat>,<lng>`.
 * Link curto (maps.app.goo.gl / goo.gl) não tem coordenadas no texto: é
 * expandido seguindo os redirecionamentos (só para hosts do Google, sem
 * baixar a página de destino).
 */
final class CoordenadasMapa
{
    private const NUM = '(-?\d{1,3}(?:\.\d+)?)';

    /** @return array{lat: float, lng: float}|null */
    public static function doLink(?string $link): ?array
    {
        if ($link === null || trim($link) === '') {
            return null;
        }

        $coordenadas = self::extrair($link);
        if ($coordenadas === null && self::ehLinkCurto($link)) {
            $expandido = self::expandir($link);
            $coordenadas = $expandido !== null ? self::extrair($expandido) : null;
        }

        return $coordenadas;
    }

    /** Só lê o texto do link (sem rede). @return array{lat: float, lng: float}|null */
    public static function extrair(string $link): ?array
    {
        $url = rawurldecode(trim($link));
        $n = self::NUM;

        if (preg_match_all("/!3d{$n}!4d{$n}/", $url, $m, PREG_SET_ORDER)) {
            $ultimo = end($m);
            if ($c = self::validar($ultimo[1], $ultimo[2])) {
                return $c;
            }
        }

        $query = parse_url($url, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $params);
            foreach (['q', 'query', 'll', 'destination', 'center'] as $chave) {
                $valor = $params[$chave] ?? null;
                if (is_string($valor) && preg_match("/^\s*{$n}\s*,\s*\+?{$n}\s*$/", $valor, $m)
                    && ($c = self::validar($m[1], $m[2]))) {
                    return $c;
                }
            }
        }

        if (preg_match("/@{$n},{$n}/", $url, $m) && ($c = self::validar($m[1], $m[2]))) {
            return $c;
        }

        if (preg_match("~/(?:search|place)/{$n},\s*\+?{$n}~", $url, $m) && ($c = self::validar($m[1], $m[2]))) {
            return $c;
        }

        return null;
    }

    public static function linkDasCoordenadas(float $lat, float $lng): string
    {
        return 'https://www.google.com/maps?q=' . $lat . ',' . $lng;
    }

    private static function validar(string $lat, string $lng): ?array
    {
        $lat = (float) $lat;
        $lng = (float) $lng;
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0.0 && $lng == 0.0)) {
            return null;
        }

        return ['lat' => round($lat, 7), 'lng' => round($lng, 7)];
    }

    private static function ehLinkCurto(string $link): bool
    {
        $host = strtolower((string) parse_url(trim($link), PHP_URL_HOST));

        return in_array($host, ['maps.app.goo.gl', 'goo.gl'], true);
    }

    /** Só segue redirecionamentos para domínios do Google (evita SSRF). */
    private static function hostPermitido(string $url): bool
    {
        if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            return false;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, ['maps.app.goo.gl', 'goo.gl'], true)
            || preg_match('/(^|\.)google\.com(\.[a-z]{2})?$/', $host) === 1;
    }

    private static function expandir(string $link): ?string
    {
        $atual = trim($link);
        try {
            for ($i = 0; $i < 5; $i++) {
                if (! self::hostPermitido($atual)) {
                    return null;
                }
                $resposta = Http::timeout(5)->withOptions(['allow_redirects' => false])->get($atual);
                $destino = $resposta->header('Location');
                if (! $resposta->redirect() || $destino === '') {
                    return null;
                }
                $atual = $destino;
                if (! self::hostPermitido($atual)) {
                    return null;
                }
                if (self::extrair($atual) !== null) {
                    return $atual;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
