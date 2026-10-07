<?php

namespace App\Http\Controllers\Api\Mcl;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AppController extends Controller
{
    /**
     * Versão mais recente do app móvel — rota pública (sem token), para que
     * até uma instalação muito desatualizada ou com sessão expirada consiga
     * saber que precisa atualizar.
     *
     * GET /api/mcl/v1/app/versao
     */
    public function versao(): JsonResponse
    {
        return response()->json([
            'versao_minima' => config('mobile_apk.versao_minima'),
            'obrigatoria'   => (bool) config('mobile_apk.obrigatoria'),
            'url_apk'       => config('mobile_apk.url_apk'),
        ]);
    }
}
