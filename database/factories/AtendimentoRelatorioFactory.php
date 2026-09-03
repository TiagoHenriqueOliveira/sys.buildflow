<?php

namespace Database\Factories;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\ModeloRelatorio;
use Illuminate\Database\Eloquent\Factories\Factory;

class AtendimentoRelatorioFactory extends Factory
{
    protected $model = AtendimentoRelatorio::class;

    public function definition(): array
    {
        return [
            'aten_rel_atendimento_id'      => Atendimento::factory(),
            'aten_rel_modelo_relatorio_id' => ModeloRelatorio::factory(),
            'aten_rel_data'                => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'aten_rel_status'              => 0,
        ];
    }

    public function preenchendo(): static
    {
        return $this->state(['aten_rel_status' => 0]);
    }

    public function revisar(): static
    {
        return $this->state(['aten_rel_status' => 1]);
    }

    public function aprovado(): static
    {
        return $this->state(['aten_rel_status' => 2]);
    }
}
