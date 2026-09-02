<?php

namespace App\Console\Commands;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Console\Command;

/**
 * Diagnostica por que a fonte Poppins pode não estar sendo aplicada nos
 * PDFs em produção: confere existência/legibilidade dos arquivos-fonte,
 * testa escrita real em storage/fonts (mais confiável que is_writable(),
 * que não detecta restrições como open_basedir) e tenta registrar a fonte
 * no dompdf de verdade, capturando a exceção real se houver uma.
 */
class DiagnosticarFontes extends Command
{
    protected $signature = 'fontes:diagnosticar';

    protected $description = 'Diagnostica problemas de carregamento da fonte Poppins no dompdf (arquivos, permissões, registro real)';

    public function handle(): int
    {
        $this->info('=== Arquivos-fonte (public/fonts/Poppins) ===');
        $regular = public_path('fonts/Poppins/Poppins-Regular.ttf');
        $bold = public_path('fonts/Poppins/Poppins-Bold.ttf');
        foreach (['Poppins-Regular.ttf' => $regular, 'Poppins-Bold.ttf' => $bold] as $nome => $caminho) {
            $existe = file_exists($caminho);
            $legivel = $existe && is_readable($caminho);
            $tamanho = $existe ? filesize($caminho) : 0;
            $this->line("  {$nome}: existe=" . ($existe ? 'SIM' : 'NAO') . ' legivel=' . ($legivel ? 'SIM' : 'NAO') . " tamanho={$tamanho} bytes");
        }

        $this->newLine();
        $this->info('=== storage/fonts (cache do dompdf) ===');
        $dir = storage_path('fonts');
        $this->line('  caminho: ' . $dir);
        $this->line('  existe: ' . (is_dir($dir) ? 'SIM' : 'NAO'));
        $this->line('  is_writable(): ' . (is_writable($dir) ? 'SIM' : 'NAO'));

        $conteudo = @scandir($dir) ?: [];
        $conteudo = array_values(array_diff($conteudo, ['.', '..']));
        $this->line('  conteudo atual: ' . (empty($conteudo) ? '(vazio)' : implode(', ', $conteudo)));

        $this->newLine();
        $this->info('=== Teste real de escrita em storage/fonts ===');
        $testeArquivo = $dir . DIRECTORY_SEPARATOR . 'teste_escrita_' . time() . '.tmp';
        $resultado = @file_put_contents($testeArquivo, 'teste');
        if ($resultado === false) {
            $erro = error_get_last();
            $this->error('  FALHOU ao escrever arquivo de teste. Erro PHP: ' . ($erro['message'] ?? 'desconhecido'));
        } else {
            $this->info('  Escrita OK (' . $resultado . ' bytes).');
            $lido = @file_get_contents($testeArquivo);
            $this->line('  Leitura de volta: ' . ($lido === 'teste' ? 'OK' : 'FALHOU'));
            @unlink($testeArquivo);
            $this->line('  Arquivo de teste removido.');
        }

        $this->newLine();
        $this->info('=== open_basedir / restrições do PHP ===');
        $openBasedir = ini_get('open_basedir');
        $this->line('  open_basedir: ' . ($openBasedir ?: '(nao definido)'));

        $this->newLine();
        $this->info('=== Registro real da fonte no dompdf ===');
        try {
            $options = new Options();
            $options->setFontDir(storage_path('fonts'));
            $options->setFontCache(storage_path('fonts'));
            $options->setIsRemoteEnabled(true);
            $dompdf = new Dompdf($options);
            $fontMetrics = $dompdf->getFontMetrics();
            $fontMetrics->registerFont(['family' => 'Poppins', 'style' => 'normal', 'weight' => 'normal'], $regular);
            $fontMetrics->registerFont(['family' => 'Poppins', 'style' => 'normal', 'weight' => 'bold'], $bold);
            $this->info('  Registro concluido sem excecao.');

            $conteudoDepois = @scandir($dir) ?: [];
            $conteudoDepois = array_values(array_diff($conteudoDepois, ['.', '..']));
            $this->line('  conteudo apos registro: ' . (empty($conteudoDepois) ? '(vazio, algo falhou silenciosamente)' : implode(', ', $conteudoDepois)));
        } catch (\Throwable $e) {
            $this->error('  EXCECAO ao registrar fonte: ' . get_class($e) . ': ' . $e->getMessage());
            $this->line('  Arquivo: ' . $e->getFile() . ':' . $e->getLine());
        }

        return 0;
    }
}
