<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Login com e-mail que não existe (ou de usuário inativo) confere a senha
 * contra um hash fictício, com o mesmo custo de um login de verdade: o tempo
 * de resposta não revela quais e-mails têm conta (2026-11-27, igual ao
 * promaq-sys). O hash fica no cache (um por algoritmo).
 */
final class SenhaFicticia
{
    public static function conferir(string $senha): void
    {
        $hash = Cache::rememberForever(
            'login.hash_ficticio.' . config('hashing.driver'),
            fn () => Hash::make(Str::random(40)),
        );

        Hash::check($senha, $hash);
    }
}
