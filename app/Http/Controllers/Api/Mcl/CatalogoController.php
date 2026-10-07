<?php

namespace App\Http\Controllers\Api\Mcl;

use App\Http\Controllers\Controller;
use App\Models\Ocorrencia;
use Illuminate\Http\JsonResponse;

class CatalogoController extends Controller
{
    public function ocorrencias(): JsonResponse
    {
        $rows = Ocorrencia::orderBy('ocor_descricao')->get()->map(fn($o) => [
            'id'        => $o->ocor_id,
            'descricao' => $o->ocor_descricao,
        ]);

        return response()->json(['data' => $rows]);
    }
}
