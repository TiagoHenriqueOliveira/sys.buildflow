<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    /**
     * Serve arquivos do disco 'public' (storage/app/public, fora de public/ —
     * ver config/filesystems.php) via PHP, com autenticação (ver middleware
     * da rota em routes/web.php). Ficando fora de public/, o Apache nunca
     * resolve essas requisições para um arquivo real — esta é a única forma
     * de acessar esses arquivos.
     */
    public function show(string $path): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
