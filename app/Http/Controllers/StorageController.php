<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    /**
     * Serve arquivos do disco 'public' (storage/app/public) via PHP, sem depender
     * do link simbólico public/storage -> storage/app/public. Este ambiente de
     * hospedagem não consegue criar/manter esse link sem acesso SSH.
     */
    public function show(string $path): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
