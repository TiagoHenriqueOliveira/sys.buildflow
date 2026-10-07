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
            // cli_cnpj é varchar(14) e os dados reais são só dígitos, sem
            // pontuação (confirmado consultando o banco) — a máscara
            // '##.###.###/####-##' gera 18 caracteres e estourava a coluna.
            'cli_cnpj'     => fake()->numerify('##############'),
            'cli_cidade'   => fake()->city(),
            'cli_uf'       => fake()->stateAbbr(),
            // cli_telefone é varchar(11) (DDD + número, sem pontuação) —
            // fake()->phoneNumber() gera formato americano com traços/DDI,
            // sempre maior que 11 caracteres.
            'cli_telefone' => fake()->numerify('###########'),
            'cli_email'    => fake()->companyEmail(),
            'cli_ativo'    => true,
        ];
    }

    public function inativo(): static
    {
        return $this->state(['cli_ativo' => false]);
    }
}
