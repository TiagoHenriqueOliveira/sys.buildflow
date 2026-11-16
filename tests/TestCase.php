<?php

namespace Tests;

use App\Models\PerfilPermissao;
use Database\Seeders\PermissoesPadraoSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Perfis de Acesso (2026-11-16): em produção os perfis nascem em branco,
     * mas a suíte parte do acesso que cada perfil tinha antes (regras fixas)
     * — assim os testes existentes continuam descrevendo o mesmo
     * comportamento. Com RefreshDatabase, o seeder roda junto do
     * migrate:fresh (uma vez) e cada teste roda em transação por cima.
     */
    protected $seed = true;

    protected $seeder = PermissoesPadraoSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Memo estático por "request" — entre testes do mesmo processo
        // pode ter ficado o estado de uma transação já desfeita.
        PerfilPermissao::limparCache();
    }
}
