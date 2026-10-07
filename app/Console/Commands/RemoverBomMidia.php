<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Encontra arquivos em storage/app/public com um BOM UTF-8 (EF BB BF) colado
 * no início — corrompe imagens/PDFs/vídeos, já que o BOM não faz parte do
 * formato binário real do arquivo. Sem --fix, só relata. Com --fix, remove
 * os 3 bytes do início e regrava o arquivo.
 */
class RemoverBomMidia extends Command
{
    protected $signature = 'midia:remover-bom {--fix : Remove o BOM do início dos arquivos afetados}';

    protected $description = 'Verifica arquivos de mídia com BOM UTF-8 indevido no início e opcionalmente corrige';

    private const BOM = "\xEF\xBB\xBF";

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $disk = Storage::disk('public');

        $arquivos = $disk->allFiles();
        $totalRuim = 0;
        $totalCorrigido = 0;

        foreach ($arquivos as $path) {
            $handle = @fopen($disk->path($path), 'rb');
            if (!$handle) {
                continue;
            }
            $inicio = fread($handle, 3);
            fclose($handle);

            if ($inicio !== self::BOM) {
                continue;
            }

            $totalRuim++;
            $full = $disk->path($path);
            $tamanho = filesize($full);
            $this->warn("BOM encontrado: {$path} ({$tamanho} bytes)");

            if ($fix) {
                $conteudo = file_get_contents($full);
                $semBom = substr($conteudo, 3);
                file_put_contents($full, $semBom);
                $totalCorrigido++;
                $this->line('  → BOM removido.');
            }
        }

        $this->newLine();
        $this->info("Total de arquivos verificados: " . count($arquivos));
        $this->info("Total com BOM indevido: {$totalRuim}");
        if ($fix) {
            $this->info("Total corrigido: {$totalCorrigido}");
        }

        return 0;
    }
}
