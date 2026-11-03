<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Pedido do cliente (2026-11-03): o campo de endereço tem que receber o
 * endereço digitado por extenso — estavam colando o link do Google Maps
 * ali, e o link não serve no relatório/PDF (não diz rua nem número). O link
 * do mapa tem campo próprio no cadastro do cliente (cli_link_mapa).
 */
class EnderecoSemLink implements ValidationRule
{
    private const PADROES = [
        '~https?://~i',
        '~\bwww\.~i',
        '~maps\.app\.goo\.gl~i',
        '~goo\.gl/maps~i',
        '~google\.[a-z.]+/maps~i',
        '~maps\.google\.~i',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        foreach (self::PADROES as $padrao) {
            if (preg_match($padrao, $value)) {
                $fail('Informe o endereço por extenso; links do Google Maps não são aceitos neste campo.');

                return;
            }
        }
    }
}
