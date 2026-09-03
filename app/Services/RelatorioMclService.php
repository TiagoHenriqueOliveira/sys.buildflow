<?php

namespace App\Services;

use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioAssinatura;
use Illuminate\Support\Facades\Storage;

/**
 * Lógica de mídia específica da API Mcl (assinaturas e nomes de arquivo de
 * upload) — antes vivia inline em Api\Mcl\RelatoriosController (841 linhas,
 * sem nenhuma camada de serviço). Mantida separada de MediaService (usado
 * pelo fluxo web) porque MediaService::saveSignatureImage() não grava
 * `aten_rel_ass_assinado_em`; unificar os dois exigiria decidir se o fluxo
 * web passa a gravar esse campo também, o que é uma mudança de comportamento
 * fora do escopo desta refatoração.
 */
class RelatorioMclService
{
    public function saveSignature(AtendimentoRelatorio $relatorio, string $base64, string $tipo, ?string $nome = null, ?string $cpf = null): string
    {
        if (! preg_match('#^data:image\/(png|jpeg|jpg);base64,(.*)$#', $base64, $m)) {
            throw new \RuntimeException('Formato de assinatura inválido.');
        }

        $data = base64_decode($m[2]);
        $path = "atendimentos_relatorios/{$relatorio->aten_rel_id}/assinaturas/{$tipo}.png";
        $dir  = dirname(storage_path('app/public/' . $path));

        if (! is_dir($dir)) mkdir($dir, 0755, true);

        $image = @imagecreatefromstring($data);
        if ($image === false) throw new \RuntimeException('Imagem inválida.');

        $bg    = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($bg, 255, 255, 255);
        imagefilledrectangle($bg, 0, 0, imagesx($image), imagesy($image), $white);
        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        $saved = imagepng($bg, storage_path('app/public/' . $path));
        imagedestroy($image);
        imagedestroy($bg);

        if (! $saved) {
            throw new \RuntimeException("Não foi possível gravar a assinatura em disco ({$dir}). Verifique as permissões de escrita.");
        }

        $existing = AtendimentoRelatorioAssinatura::where('aten_rel_ass_relatorio_id', $relatorio->aten_rel_id)
            ->where('aten_rel_ass_tipo', $tipo)
            ->first();

        $now = now()->format('Y-m-d H:i:s');
        if ($existing) {
            $existing->update([
                'aten_rel_ass_path'        => $path,
                'aten_rel_ass_nome'        => $nome,
                'aten_rel_ass_cpf'         => $cpf,
                'aten_rel_ass_assinado_em' => $now,
            ]);
        } else {
            AtendimentoRelatorioAssinatura::create([
                'aten_rel_ass_relatorio_id' => $relatorio->aten_rel_id,
                'aten_rel_ass_path'         => $path,
                'aten_rel_ass_tipo'         => $tipo,
                'aten_rel_ass_nome'         => $nome,
                'aten_rel_ass_cpf'          => $cpf,
                'aten_rel_ass_assinado_em'  => $now,
            ]);
        }

        return asset('midia/' . $path);
    }

    public function safeFilename(string $originalName, string $dir): string
    {
        $ext  = pathinfo($originalName, PATHINFO_EXTENSION);
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        // Mantém nome original mas remove caracteres perigosos — inclusive
        // pontos, para um nome tipo "shell.php.jpg" não sobreviver intacto
        // até a extensão real.
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $base);
        $safe = trim($safe, '_') ?: 'arquivo';
        $name = $safe . '.' . $ext;
        // Evita conflitos adicionando sufixo numérico
        $counter = 0;
        while (Storage::disk('public')->exists("$dir/$name")) {
            $counter++;
            $name = "{$safe}_{$counter}.{$ext}";
        }
        return $name;
    }
}
