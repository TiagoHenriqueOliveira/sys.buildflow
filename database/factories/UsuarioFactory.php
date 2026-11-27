<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'user_nivel_acesso' => 1,
            'user_nome'         => fake()->name(),
            'user_email'        => fake()->unique()->safeEmail(),
            'user_senha'        => Hash::make('password'),
            'user_ativo'        => true,
            // MFA obrigatório no web (2026-11-27): por padrão o usuário de
            // teste já tem o autenticador cadastrado; use ->semMfa() para o
            // primeiro acesso.
            'user_mfa_secret'        => self::SEGREDO_MFA,
            'user_mfa_confirmado_em' => now(),
        ];
    }

    /** Segredo TOTP fixo dos testes (base32). */
    public const SEGREDO_MFA = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    public function semMfa(): static
    {
        return $this->state(['user_mfa_secret' => null, 'user_mfa_confirmado_em' => null]);
    }

    public function administrador(): static
    {
        return $this->state(['user_nivel_acesso' => 0]);
    }

    public function tecnico(): static
    {
        return $this->state(['user_nivel_acesso' => 1]);
    }

    public function comercial(): static
    {
        return $this->state(['user_nivel_acesso' => 2]);
    }

    public function assistencia(): static
    {
        return $this->state(['user_nivel_acesso' => 3]);
    }

    public function vendedor(): static
    {
        return $this->state(['user_nivel_acesso' => 4]);
    }

    public function inativo(): static
    {
        return $this->state(['user_ativo' => false]);
    }
}
