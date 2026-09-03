<?php

namespace Database\Factories;

use App\Models\ModeloRelatorio;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModeloRelatorioFactory extends Factory
{
    protected $model = ModeloRelatorio::class;

    public function definition(): array
    {
        return [
            'mod_rel_tp_data'                => 1,
            'mod_rel_descricao'              => fake()->words(3, true),
            'mod_rel_ativo'                  => true,
            'mod_rel_descricao_secao'        => true,
            'mod_rel_servicos_prestados'     => true,
            'mod_rel_pecas_substituidas'     => true,
            'mod_rel_informacoes_adicionais' => true,
            'mod_rel_horarios'               => true,
            'mod_rel_cond_clima'             => true,
            'mod_rel_ocorrencia'             => true,
        ];
    }
}
