<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppUpdateController extends Controller
{
    /**
     * Serve o instalador (.apk) do app móvel BuildFlow. Rota pública de
     * propósito — diferente das fotos/assinaturas de cliente em /midia, o
     * instalador não é dado sensível, e precisa ser baixável mesmo por uma
     * instalação muito desatualizada ou com sessão/token expirado (é
     * justamente o caso que estamos tentando resolver).
     *
     * GET /apk/{arquivo}
     */
    public function baixar(string $arquivo): BinaryFileResponse|Response
    {
        // Nunca deixa o parâmetro sair da pasta de APKs (ex: "../../.env").
        $nomeSeguro = basename($arquivo);
        $caminho = storage_path('app/apks/' . $nomeSeguro);

        abort_unless(
            str_ends_with(strtolower($nomeSeguro), '.apk') && is_file($caminho),
            404
        );

        return response()->download($caminho, $nomeSeguro, [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}
