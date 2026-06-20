<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'cli_nome'     => fake()->company(),
            'cli_cnpj'     => fake()->numerify('##.###.###/####-##'),
            'cli_cidade'   => fake()->city(),
            'cli_uf'       => fake()->stateAbbr(),
            'cli_telefone' => fake()->phoneNumber(),
            'cli_email'    => fake()->companyEmail(),
            'cli_ativo'    => true,
        ];
    }

    public function inativo(): static
    {
        return $this->state(['cli_ativo' => false]);
    }
}
