<?php

namespace Database\Factories;

use App\Models\ModeloRelatorio;
use App\Models\NaturezaAtendimento;
use App\Models\TipoAtendimento;
use Illuminate\Database\Eloquent\Factories\Factory;

class NaturezaAtendimentoFactory extends Factory
{
    protected $model = NaturezaAtendimento::class;

    public function definition(): array
    {
        return [
            'nat_aten_mod_relatorio_id'  => ModeloRelatorio::factory(),
            'nat_aten_tp_atendimento_id' => TipoAtendimento::factory(),
            'nat_aten_descricao'         => fake()->words(3, true),
            'nat_aten_ativo'             => true,
        ];
    }

    public function inativa(): static
    {
        return $this->state(['nat_aten_ativo' => false]);
    }
}
