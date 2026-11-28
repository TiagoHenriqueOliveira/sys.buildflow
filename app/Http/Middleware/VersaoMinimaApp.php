<?php

namespace App\Http\Middleware;

use App\Support\VersaoApp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrato fae/v1 §3 (auditoria S-14): o app envia "X-App-Version:
 * 1.4.0+9" em toda requisição. Com a atualização obrigatória ligada
 * (MOBILE_APK_OBRIGATORIA=true), versão abaixo de MOBILE_APK_VERSAO_MINIMA
 * recebe 426 "versao_minima". Sem o cabeçalho (app antigo), a requisição
 * passa enquanto MOBILE_EXIGIR_CABECALHO_VERSAO=false. GET /app/versao
 * nunca é barrado: é por ele que o app descobre que precisa atualizar.
 */
class VersaoMinimaApp
{
    public function handle(Request $request, Closure $next): Response
    {
        $minima = (string) config('mobile_apk.versao_minima');

        if ($request->routeIs('fae.app.versao') || ! config('mobile_apk.obrigatoria') || VersaoApp::partes($minima) === null) {
            return $next($request);
        }

        $enviada = $request->header('X-App-Version');
        $valida = VersaoApp::partes($enviada) !== null;

        $barrar = $valida
            ? VersaoApp::abaixoDe((string) $enviada, $minima)
            : (bool) config('mobile_apk.exigir_cabecalho_versao');

        if (! $barrar) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Atualize o aplicativo para continuar.',
            'codigo' => 'versao_minima',
            'versao_minima' => $minima,
            'url_apk' => config('mobile_apk.url_apk'),
            'sha256_apk' => VersaoApp::sha256Apk(),
        ], 426);
    }
}
