<?php

namespace Database\Factories;

use App\Models\Atendimento;
use App\Models\Cliente;
use App\Models\NaturezaAtendimento;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class AtendimentoFactory extends Factory
{
    protected $model = Atendimento::class;

    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-6 months', 'now');
        $fim    = fake()->dateTimeBetween($inicio, '+6 months');

        return [
            'aten_natureza_id' => NaturezaAtendimento::factory(),
            'aten_cliente_id'  => Cliente::factory(),
            'aten_usuario_id'  => Usuario::factory()->tecnico(),
            'aten_status'      => 2,
            'aten_nr_proposta' => fake()->optional()->numerify('PROP-####'),
            'aten_responsavel' => fake()->optional()->name(),
            'aten_endereco'    => fake()->optional()->address(),
            'aten_dt_inicio'   => $inicio->format('Y-m-d'),
            'aten_dt_fim'      => $fim->format('Y-m-d'),
        ];
    }

    public function naoIniciada(): static
    {
        return $this->state(['aten_status' => 0]);
    }

    public function emAndamento(): static
    {
        return $this->state(['aten_status' => 2]);
    }

    public function concluida(): static
    {
        return $this->state(['aten_status' => 3]);
    }
}
