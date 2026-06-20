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
            'mod_rel_descricao'         => fake()->words(3, true),
            'mod_rel_tp_data'           => 1,
            'mod_rel_ativo'             => true,
            'mod_rel_anexo'             => true,
            'mod_rel_atividade'         => true,
            'mod_rel_comentario'        => true,
            'mod_rel_cond_clima'        => true,
            'mod_rel_controle_material' => false,
            'mod_rel_entrega_tecnica'   => false,
            'mod_rel_equipamento'       => true,
            'mod_rel_foto'              => true,
            'mod_rel_horarios'          => true,
            'mod_rel_ocorrencia'        => true,
            'mod_rel_ocupacao'          => true,
            'mod_rel_video'             => false,
        ];
    }
}
