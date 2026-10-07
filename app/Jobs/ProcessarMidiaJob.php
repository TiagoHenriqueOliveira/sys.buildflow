<?php

namespace App\Jobs;

use App\Services\MediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessarMidiaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de tentativas em caso de falha.
     */
    public int $tries = 3;

    /**
     * Timeout em segundos por tentativa.
     */
    public int $timeout = 120;

    public function __construct(
        private readonly string $tipo,       // 'imagem' | 'video'
        private readonly string $origem,     // caminho absoluto do arquivo original
        private readonly string $destino,    // caminho absoluto do thumbnail
        private readonly int    $maxWidth = 400,
    ) {}

    public function handle(MediaService $media): void
    {
        match ($this->tipo) {
            'imagem' => $media->createImageThumbnail($this->origem, $this->destino, $this->maxWidth),
            'video'  => $media->createVideoThumbnail($this->origem, $this->destino),
            default  => null,
        };
    }

    /**
     * Logar falhas sem derrubar a aplicação.
     */
    public function failed(\Throwable $exception): void
    {
        report($exception);
    }
}
