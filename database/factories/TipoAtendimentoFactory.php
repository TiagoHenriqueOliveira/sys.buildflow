<?php

namespace Database\Factories;

use App\Models\TipoAtendimento;
use Illuminate\Database\Eloquent\Factories\Factory;

class TipoAtendimentoFactory extends Factory
{
    protected $model = TipoAtendimento::class;

    public function definition(): array
    {
        return [
            'tp_aten_descricao' => fake()->words(2, true),
            'tp_aten_ativo'     => true,
        ];
    }
}
