<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Auditoria 27/11/2026 (S-21): a assinatura chega como "data:image/png;
 * base64,...". Sem limite, uma string enorme (ou uma imagem de dimensões
 * gigantes) esgotava a memória no imagecreatefromstring. Agora: no máximo
 * 1 MB de texto, PNG/JPEG válido e até 2000 × 2000 pixels — conferido com
 * getimagesizefromstring, que só lê o cabeçalho da imagem.
 */
class AssinaturaBase64 implements ValidationRule
{
    public const MAX_CARACTERES = 1_048_576;

    public const MAX_PIXELS_LADO = 2000;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('A assinatura enviada é inválida.');

            return;
        }

        if (strlen($value) > self::MAX_CARACTERES) {
            $fail('A assinatura é grande demais (máximo de 1 MB).');

            return;
        }

        if (! preg_match('#^data:image/(png|jpeg|jpg);base64,([A-Za-z0-9+/=\s]+)$#', $value, $m)) {
            $fail('Formato de assinatura inválido (esperado PNG ou JPEG em base64).');

            return;
        }

        $binario = base64_decode($m[2], true);
        $info = $binario === false ? false : @getimagesizefromstring($binario);

        if ($info === false || ! in_array($info[2] ?? null, [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            $fail('Não foi possível ler a imagem da assinatura.');

            return;
        }

        if ($info[0] > self::MAX_PIXELS_LADO || $info[1] > self::MAX_PIXELS_LADO) {
            $fail('A imagem da assinatura é grande demais (máximo de 2000 × 2000 pixels).');
        }
    }
}
