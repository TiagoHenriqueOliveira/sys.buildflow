<?php

namespace App\Repositories;

use App\Models\CrmTipoOrcamento;
use App\Repositories\Contracts\CrudRepositoryInterface;

class CrmTipoOrcamentoRepository implements CrudRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return CrmTipoOrcamento::orderBy('crm_tp_orc_nome')->get();
    }

    public function create(array $data)
    {
        return CrmTipoOrcamento::create([
            'crm_tp_orc_nome' => $data['crm_tp_orc_nome'],
            'crm_tp_orc_config_modelo_id' => $data['crm_tp_orc_config_modelo_id'] ?? null,
            'crm_tp_orc_ativo' => 1,
        ]);
    }

    public function update(int $id, array $data)
    {
        $tipo = CrmTipoOrcamento::findOrFail($id);

        $tipo->update([
            'crm_tp_orc_nome' => $data['crm_tp_orc_nome'],
            'crm_tp_orc_config_modelo_id' => $data['crm_tp_orc_config_modelo_id'] ?? null,
            'crm_tp_orc_ativo' => $data['crm_tp_orc_ativo'] ?? $tipo->crm_tp_orc_ativo,
        ]);

        return $tipo;
    }
}