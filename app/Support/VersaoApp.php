<?php

namespace App\Support;

/**
 * Versão do app no formato do pubspec.yaml ("1.4.0+9") — contrato fae/v1 §3.
 * Compara número a número (maior.menor.correção) e, empatando, pelo build.
 */
final class VersaoApp
{
    /** @return array{0:int,1:int,2:int,3:int}|null */
    public static function partes(?string $versao): ?array
    {
        if (! is_string($versao) || ! preg_match('/^\s*(\d+)\.(\d+)\.(\d+)(?:\+(\d+))?\s*$/', $versao, $m)) {
            return null;
        }

        return [(int) $m[1], (int) $m[2], (int) $m[3], (int) ($m[4] ?? 0)];
    }

    /** true quando $versao é menor que $minima (as duas válidas). */
    public static function abaixoDe(string $versao, string $minima): bool
    {
        $v = self::partes($versao);
        $min = self::partes($minima);

        return $v !== null && $min !== null && $v < $min;
    }

    /** SHA-256 do APK publicado (storage/app/apks), para o app conferir o download. */
    public static function sha256Apk(): ?string
    {
        $url = (string) config('mobile_apk.url_apk');
        $arquivo = basename((string) parse_url($url, PHP_URL_PATH));
        $caminho = storage_path('app/apks/' . $arquivo);

        if ($arquivo === '' || ! str_ends_with(strtolower($arquivo), '.apk') || ! is_file($caminho)) {
            return null;
        }

        return cache()->remember('apk.sha256.' . $arquivo . '.' . filemtime($caminho) . '.' . filesize($caminho), now()->addDay(),
            fn () => hash_file('sha256', $caminho));
    }
}
