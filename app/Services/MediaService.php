<?php

namespace App\Services;

use App\Enums\AssinaturaTipo;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioAssinatura;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    /**
     * Salva a imagem de assinatura (base64) no storage e persiste o registro.
     * Retorna a URL pública da assinatura salva.
     */
    public function saveSignatureImage(AtendimentoRelatorio $relatorio, string $base64, string|AssinaturaTipo $tipo): string
    {
        $tipoEnum  = $tipo instanceof AssinaturaTipo ? $tipo : AssinaturaTipo::from($tipo);
        $tipoValue = $tipoEnum->value;

        if (!preg_match('#^data:image\/(png|jpeg|jpg);base64,(.*)$#', $base64, $matches)) {
            throw new \RuntimeException('Formato de assinatura inválido.');
        }

        $data = base64_decode($matches[2]);
        $path = "atendimentos_relatorios/{$relatorio->aten_rel_id}/assinaturas/{$tipoValue}.png";

        $image = @imagecreatefromstring($data);
        if ($image === false) {
            throw new \RuntimeException('Não foi possível processar a imagem da assinatura.');
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        $bg    = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($bg, 255, 255, 255);
        imagefilledrectangle($bg, 0, 0, $width, $height, $white);

        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagealphablending($bg, true);
        imagesavealpha($bg, false);

        imagecopy($bg, $image, 0, 0, 0, 0, $width, $height);

        $dir = dirname(storage_path('app/public/' . $path));
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $saved = imagepng($bg, storage_path('app/public/' . $path));
        imagedestroy($image);
        imagedestroy($bg);

        if (!$saved) {
            throw new \RuntimeException("Não foi possível gravar a assinatura em disco ({$dir}). Verifique as permissões de escrita.");
        }

        $existing = AtendimentoRelatorioAssinatura::where('aten_rel_ass_relatorio_id', $relatorio->aten_rel_id)
            ->where('aten_rel_ass_tipo', $tipoEnum)
            ->first();

        if ($existing) {
            $existing->update(['aten_rel_ass_path' => $path]);
        } else {
            AtendimentoRelatorioAssinatura::create([
                'aten_rel_ass_relatorio_id' => $relatorio->aten_rel_id,
                'aten_rel_ass_path'         => $path,
                'aten_rel_ass_tipo'         => $tipoEnum,
            ]);
        }

        return asset('midia/' . $path);
    }

    /**
     * Gera thumbnail de imagem redimensionada.
     * Retorna true em caso de sucesso, false em caso de falha.
     */
    public function createImageThumbnail(string $src, string $dest, int $maxWidth = 400): bool
    {
        try {
            if (!file_exists($src)) {
                return false;
            }

            $info = getimagesize($src);
            if (!$info) {
                return false;
            }

            [$width, $height] = [$info[0], $info[1]];
            $ratio     = $height ? ($width / $height) : 1;
            $newWidth  = $maxWidth;
            $newHeight = (int) ($newWidth / $ratio);

            $img = match ($info['mime']) {
                'image/jpeg' => imagecreatefromjpeg($src),
                'image/png'  => imagecreatefrompng($src),
                'image/gif'  => imagecreatefromgif($src),
                default      => null,
            };

            if (!$img) {
                return false;
            }

            $thumb = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($thumb, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            $dir = dirname($dest);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $saved = match ($info['mime']) {
                'image/jpeg' => imagejpeg($thumb, $dest, 85),
                'image/png'  => imagepng($thumb, $dest),
                'image/gif'  => imagegif($thumb, $dest),
                default      => false,
            };

            imagedestroy($img);
            imagedestroy($thumb);

            return (bool) $saved;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    /**
     * Extrai thumbnail de vídeo usando ffmpeg (se disponível).
     * Retorna true em caso de sucesso, false caso contrário.
     */
    public function createVideoThumbnail(string $videoPath, string $dest): bool
    {
        try {
            if (!file_exists($videoPath)) {
                return false;
            }

            if (!function_exists('shell_exec')) {
                return false;
            }

            $ffmpegCheck = trim(@shell_exec('ffmpeg -version 2>&1'));
            if (!$ffmpegCheck) {
                return false;
            }

            $dir = dirname($dest);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $cmd = sprintf(
                'ffmpeg -y -i %s -ss 00:00:01 -vframes 1 %s 2>&1',
                escapeshellarg($videoPath),
                escapeshellarg($dest)
            );

            @shell_exec($cmd);

            return file_exists($dest);
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }
}
