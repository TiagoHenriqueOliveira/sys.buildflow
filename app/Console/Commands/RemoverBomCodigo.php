<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Encontra arquivos .php do projeto (app/, routes/, config/) com um BOM UTF-8
 * (EF BB BF) colado no início. Um BOM antes de "<?php" é enviado como saída
 * literal pelo PHP, corrompendo qualquer resposta binária (imagem, PDF) que
 * esse arquivo ajude a gerar — mesmo sem gerar erro de sintaxe. Sem --fix,
 * só relata. Com --fix, remove os 3 bytes do início e regrava o arquivo.
 */
class RemoverBomCodigo extends Command
{
    protected $signature = 'codigo:remover-bom {--fix : Remove o BOM do início dos arquivos afetados}';

    protected $description = 'Verifica arquivos .php do projeto com BOM UTF-8 indevido no início e opcionalmente corrige';

    private const BOM = "\xEF\xBB\xBF";

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');

        $finder = (new Finder())
            ->files()
            ->in([base_path('app'), base_path('routes'), base_path('config'), base_path('database')])
            ->name('*.php');

        $totalVerificado = 0;
        $totalRuim = 0;
        $totalCorrigido = 0;

        foreach ($finder as $file) {
            $totalVerificado++;
            $full = $file->getRealPath();

            $handle = @fopen($full, 'rb');
            if (!$handle) {
                continue;
            }
            $inicio = fread($handle, 3);
            fclose($handle);

            if ($inicio !== self::BOM) {
                continue;
            }

            $totalRuim++;
            $relativo = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $full);
            $this->warn("BOM encontrado: {$relativo}");

            if ($fix) {
                $conteudo = file_get_contents($full);
                file_put_contents($full, substr($conteudo, 3));
                $totalCorrigido++;
                $this->line('  → BOM removido.');
            }
        }

        $this->newLine();
        $this->info("Total de arquivos .php verificados: {$totalVerificado}");
        $this->info("Total com BOM indevido: {$totalRuim}");
        if ($fix) {
            $this->info("Total corrigido: {$totalCorrigido}");
        }

        return 0;
    }
}
