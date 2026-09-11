<?php

namespace App\Repositories;

use App\Models\ConfigModelo;
use App\Repositories\Contracts\CrudRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ConfiguradorModeloRepository implements CrudRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return ConfigModelo::orderBy('cfg_mod_nome')->get();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $modelo = ConfigModelo::create([
                'cfg_mod_nome' => $data['cfg_mod_nome'],
                'cfg_mod_setor' => $data['cfg_mod_setor'],
                'cfg_mod_ativo' => 1,
            ]);

            $this->sincronizarPerguntas($modelo, $data['perguntas']);

            return $modelo;
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $modelo = ConfigModelo::findOrFail($id);

            $modelo->update([
                'cfg_mod_nome' => $data['cfg_mod_nome'],
                'cfg_mod_setor' => $data['cfg_mod_setor'],
                'cfg_mod_ativo' => $data['cfg_mod_ativo'] ?? $modelo->cfg_mod_ativo,
            ]);

            $this->sincronizarPerguntas($modelo, $data['perguntas']);

            return $modelo;
        });
    }

    /**
     * Etapa 1 (telas): sem revisão versionada ainda (isso é Etapa 2) — a
     * edição do modelo simplesmente sobrescreve o vínculo com perguntas,
     * como o próprio cronograma da sessão 04 orienta.
     */
    private function sincronizarPerguntas(ConfigModelo $modelo, array $perguntaIds): void
    {
        $sync = [];
        foreach (array_values($perguntaIds) as $ordem => $perguntaId) {
            $sync[$perguntaId] = ['cfg_mod_perg_ordem' => $ordem];
        }

        $modelo->perguntas()->sync($sync);
    }
}