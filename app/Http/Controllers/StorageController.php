<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    /**
     * Serve arquivos do disco 'public' (public/storage, ver
     * config/filesystems.php) via PHP, com autenticação (ver middleware da
     * rota em routes/web.php). O acesso estático direto pelo Apache a
     * public/storage é bloqueado por um .htaccess (Require all denied) —
     * esta é a única forma de acessar esses arquivos.
     */
    public function show(string $path): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
