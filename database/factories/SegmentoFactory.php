<?php

namespace Database\Factories;

use App\Models\Segmento;
use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentoFactory extends Factory
{
    protected $model = Segmento::class;

    public function definition(): array
    {
        return [
            'seg_descricao' => fake()->unique()->word(),
            'seg_ativo' => true,
        ];
    }
}