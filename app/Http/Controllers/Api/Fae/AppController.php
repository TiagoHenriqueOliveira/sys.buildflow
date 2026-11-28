<?php

namespace App\Http\Controllers\Api\Fae;

use App\Http\Controllers\Controller;
use App\Support\VersaoApp;
use Illuminate\Http\JsonResponse;

class AppController extends Controller
{
    /**
     * Versão mais recente do app móvel — rota pública (sem token), para que
     * até uma instalação muito desatualizada ou com sessão expirada consiga
     * saber que precisa atualizar.
     *
     * GET /api/fae/v1/app/versao
     */
    public function versao(): JsonResponse
    {
        return response()->json([
            'versao_minima' => config('mobile_apk.versao_minima'),
            'obrigatoria'   => (bool) config('mobile_apk.obrigatoria'),
            'url_apk'       => config('mobile_apk.url_apk'),
            // Contrato §3 (S-14): o app confere o arquivo baixado antes de instalar.
            'sha256_apk'    => VersaoApp::sha256Apk(),
        ]);
    }
}
