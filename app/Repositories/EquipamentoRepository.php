<?php

namespace App\Repositories;

use App\Models\Equipamento;
use App\Repositories\Contracts\CrudRepositoryInterface;

class EquipamentoRepository implements CrudRepositoryInterface
{
    public function all()
    {
        return Equipamento::orderBy('equip_descricao')->get();
    }

    public function create(array $data)
    {
        return Equipamento::create([
            'equip_descricao' => $data['equip_descricao'],
            'equip_ativo' => 1,
        ]);
    }

    public function update(int $id, array $data)
    {
        $equip = Equipamento::findOrFail($id);

        $equip->update([
            'equip_descricao' => $data['equip_descricao'],
            'equip_ativo' => $data['equip_ativo'] ?? $equip->equip_ativo,
        ]);

        return $equip;
    }
}
