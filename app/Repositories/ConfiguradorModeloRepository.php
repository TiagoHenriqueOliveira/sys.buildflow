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
                ...$this->flagsDeSecao($data),
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
                ...$this->flagsDeSecao($data),
            ]);

            $this->sincronizarPerguntas($modelo, $data['perguntas']);

            return $modelo;
        });
    }

    /**
     * Checkboxes desmarcados nao vem no payload (padrao HTML de
     * checkbox) - por isso o default explicito 'false', em vez de usar
     * ?? (que so cobre "chave ausente", nao "false vindo do form").
     */
    private function flagsDeSecao(array $data): array
    {
        return [
            'cfg_mod_usa_horarios' => $data['cfg_mod_usa_horarios'] ?? false,
            'cfg_mod_usa_clima' => $data['cfg_mod_usa_clima'] ?? false,
            'cfg_mod_usa_servicos' => $data['cfg_mod_usa_servicos'] ?? false,
            'cfg_mod_usa_pecas' => $data['cfg_mod_usa_pecas'] ?? false,
            'cfg_mod_usa_ocorrencias' => $data['cfg_mod_usa_ocorrencias'] ?? false,
            'cfg_mod_usa_observacoes' => $data['cfg_mod_usa_observacoes'] ?? false,
        ];
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