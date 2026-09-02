<?php

namespace App\Console\Commands;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Console\Command;

/**
 * Diagnostica por que a fonte Poppins pode não estar sendo aplicada nos
 * PDFs em produção: confere existência/legibilidade dos arquivos-fonte,
 * testa escrita real em storage/fonts (mais confiável que is_writable(),
 * que não detecta restrições como open_basedir), tira um "antes/depois"
 * do conteúdo da pasta pra provar se o registro realmente grava arquivo
 * novo (em vez de só não lançar exceção), e confere se o dompdf realmente
 * reconhece a fonte registrada (getFont), que é o que a geração de PDF de
 * verdade usa internamente.
 */
class DiagnosticarFontes extends Command
{
    protected $signature = 'fontes:diagnosticar';

    protected $description = 'Diagnostica problemas de carregamento da fonte Poppins no dompdf (arquivos, permissões, registro real)';

    public function handle(): int
    {
        $this->info('=== Versão do dompdf ===');
        $versaoDompdf = \Composer\InstalledVersions::isInstalled('dompdf/dompdf')
            ? \Composer\InstalledVersions::getPrettyVersion('dompdf/dompdf')
            : 'desconhecida';
        $this->line('  dompdf/dompdf: ' . $versaoDompdf);

        $this->newLine();
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
        $this->info('=== storage/fonts (cache do dompdf) — detalhado ===');
        $dir = storage_path('fonts');
        $this->line('  caminho: ' . $dir);
        $this->line('  existe: ' . (is_dir($dir) ? 'SIM' : 'NAO'));
        $this->line('  is_writable(): ' . (is_writable($dir) ? 'SIM' : 'NAO'));
        $this->listarConteudo($dir);

        $this->newLine();
        $this->info('=== Teste real de escrita em storage/fonts ===');
        $testeArquivo = $dir . DIRECTORY_SEPARATOR . 'teste_escrita_' . time() . '.tmp';
        $resultado = @file_put_contents($testeArquivo, 'teste');
        if ($resultado === false) {
            $erro = error_get_last();
            $this->error('  FALHOU ao escrever arquivo de teste. Erro PHP: ' . ($erro['message'] ?? 'desconhecido'));
        } else {
            $this->info('  Escrita OK (' . $resultado . ' bytes).');
            @unlink($testeArquivo);
        }

        $this->newLine();
        $this->info('=== open_basedir / restrições do PHP ===');
        $this->line('  open_basedir: ' . (ini_get('open_basedir') ?: '(nao definido)'));
        $this->line('  disable_functions: ' . (ini_get('disable_functions') ?: '(nenhuma)'));

        $this->newLine();
        $this->info('=== Registro real da fonte no dompdf (antes/depois) ===');
        $antes = $this->snapshot($dir);

        try {
            $options = new Options();
            $options->setFontDir($dir);
            $options->setFontCache($dir);
            $options->setIsRemoteEnabled(true);
            $dompdf = new Dompdf($options);
            $fontMetrics = $dompdf->getFontMetrics();
            $fontMetrics->registerFont(['family' => 'Poppins', 'style' => 'normal', 'weight' => 'normal'], $regular);
            $fontMetrics->registerFont(['family' => 'Poppins', 'style' => 'normal', 'weight' => 'bold'], $bold);
            $this->info('  registerFont() concluido sem excecao.');

            $depois = $this->snapshot($dir);
            $novos = array_diff($depois, $antes);
            if (empty($novos)) {
                $this->error('  NENHUM arquivo novo foi criado — registro nao persistiu nada de fato.');
            } else {
                $this->info('  Arquivo(s) novo(s) criado(s): ' . implode(', ', $novos));
            }

            $this->newLine();
            $fonteNormal = $fontMetrics->getFont('Poppins', 'normal');
            $fonteBold = $fontMetrics->getFont('Poppins', 'bold');
            $this->line('  getFont("Poppins","normal") retorna: ' . ($fonteNormal ?: '(NULO — dompdf não reconhece a fonte registrada)'));
            $this->line('  getFont("Poppins","bold") retorna: ' . ($fonteBold ?: '(NULO — dompdf não reconhece a fonte registrada)'));
        } catch (\Throwable $e) {
            $this->error('  EXCECAO ao registrar fonte: ' . get_class($e) . ': ' . $e->getMessage());
            $this->line('  Arquivo: ' . $e->getFile() . ':' . $e->getLine());
        }

        return 0;
    }

    /** @return string[] nomes dos itens na pasta */
    private function snapshot(string $dir): array
    {
        $itens = @scandir($dir) ?: [];

        return array_values(array_diff($itens, ['.', '..']));
    }

    private function listarConteudo(string $dir): void
    {
        $itens = @scandir($dir) ?: [];
        $itens = array_values(array_diff($itens, ['.', '..']));
        if (empty($itens)) {
            $this->line('  conteudo: (vazio)');

            return;
        }
        foreach ($itens as $item) {
            $caminho = $dir . DIRECTORY_SEPARATOR . $item;
            $tipo = is_dir($caminho) ? 'DIRETORIO' : 'arquivo';
            $tamanho = is_file($caminho) ? filesize($caminho) . ' bytes' : '';
            $perm = substr(sprintf('%o', @fileperms($caminho)), -4);
            $this->line("  - {$item} [{$tipo}] {$tamanho} perm={$perm}");
            if (is_dir($caminho)) {
                $sub = @scandir($caminho) ?: [];
                $sub = array_values(array_diff($sub, ['.', '..']));
                foreach ($sub as $s) {
                    $this->line("      └─ {$s}");
                }
            }
        }
    }
}
