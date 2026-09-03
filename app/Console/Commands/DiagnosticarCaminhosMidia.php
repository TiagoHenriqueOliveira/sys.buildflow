<?php

namespace App\Console\Commands;

use App\Models\AtendimentoAnexo;
use App\Models\AtendimentoRelatorioAnexo;
use App\Models\AtendimentoRelatorioAssinatura;
use App\Models\AtendimentoRelatorioFoto;
use App\Models\AtendimentoRelatorioVideo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Encontra fotos/vídeos/anexos/assinaturas cujo caminho salvo no banco tem
 * o prefixo "app/public/" indevido (produz URLs quebradas do tipo
 * /storage/app/public/... em vez de /storage/...). Sem --fix, só relata.
 * Com --fix, corrige no banco apenas quando o arquivo realmente existe no
 * caminho corrigido — nunca inventa dado, nunca apaga nada.
 */
class DiagnosticarCaminhosMidia extends Command
{
    protected $signature = 'midia:diagnosticar {--fix : Corrige o caminho no banco quando o arquivo existir no caminho correto}';

    protected $description = 'Verifica registros de mídia com caminho salvo incorretamente (prefixo "app/public/" indevido) e opcionalmente corrige';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');

        $alvos = [
            ['modelo' => AtendimentoAnexo::class, 'coluna' => 'aten_anexo_path', 'label' => 'Anexo de atendimento'],
            ['modelo' => AtendimentoRelatorioAnexo::class, 'coluna' => 'aten_rel_anexo_path', 'label' => 'Arquivo de relatório'],
            ['modelo' => AtendimentoRelatorioAssinatura::class, 'coluna' => 'aten_rel_ass_path', 'label' => 'Assinatura'],
            ['modelo' => AtendimentoRelatorioFoto::class, 'coluna' => 'aten_rel_foto_path', 'label' => 'Foto de relatório'],
            ['modelo' => AtendimentoRelatorioVideo::class, 'coluna' => 'aten_rel_vid_path', 'label' => 'Vídeo de relatório'],
        ];

        $totalRuim = 0;
        $totalCorrigido = 0;
        $totalOrfao = 0;

        foreach ($alvos as $alvo) {
            /** @var class-string<\Illuminate\Database\Eloquent\Model> $modelo */
            $modelo = $alvo['modelo'];
            $coluna = $alvo['coluna'];
            $label = $alvo['label'];

            $registros = $modelo::query()->where($coluna, 'like', 'app/public/%')->get();

            if ($registros->isEmpty()) {
                $this->info("[{$label}] nenhum registro com caminho incorreto.");
                continue;
            }

            $this->warn("[{$label}] {$registros->count()} registro(s) com prefixo 'app/public/' indevido:");
            foreach ($registros as $r) {
                $totalRuim++;
                $caminhoAtual = $r->{$coluna};
                $caminhoCorrigido = preg_replace('#^app/public/#', '', $caminhoAtual, 1);
                $existeNoCorrigido = Storage::disk('public')->exists($caminhoCorrigido);
                $existeNoAtual = Storage::disk('public')->exists($caminhoAtual);

                $id = $r->getKey();
                $status = $existeNoCorrigido
                    ? '✔ arquivo existe no caminho corrigido'
                    : ($existeNoAtual
                        ? '⚠ arquivo só existe no caminho salvo (incorreto) — investigar'
                        : '✘ arquivo não encontrado em nenhum dos dois — precisa reenviar');
                $this->line("  id={$id} | atual: {$caminhoAtual} | corrigido: {$caminhoCorrigido} | {$status}");

                if (!$existeNoCorrigido && !$existeNoAtual) {
                    $totalOrfao++;
                }

                if ($fix && $existeNoCorrigido) {
                    $r->{$coluna} = $caminhoCorrigido;
                    $r->save();
                    $totalCorrigido++;
                    $this->line('    → corrigido no banco.');
                }
            }
        }

        $this->newLine();
        $this->info("Total com caminho incorreto: {$totalRuim}");
        if ($fix) {
            $this->info("Total corrigido: {$totalCorrigido}");
        }
        if ($totalOrfao > 0) {
            $this->error("Total sem arquivo em lugar nenhum, precisa reenviar: {$totalOrfao}");
        }

        return 0;
    }
}
