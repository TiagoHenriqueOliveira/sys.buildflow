<?php

namespace Database\Factories;

use App\Models\ClassificacaoCliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassificacaoClienteFactory extends Factory
{
    protected $model = ClassificacaoCliente::class;

    public function definition(): array
    {
        return [
            'cla_cli_nome' => fake()->unique()->word(),
            'cla_cli_ativo' => true,
        ];
    }
}