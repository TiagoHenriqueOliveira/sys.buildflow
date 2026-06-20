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
        ];
    }

    public function administrador(): static
    {
        return $this->state(['user_nivel_acesso' => 0]);
    }

    public function tecnico(): static
    {
        return $this->state(['user_nivel_acesso' => 1]);
    }

    public function inativo(): static
    {
        return $this->state(['user_ativo' => false]);
    }
}
