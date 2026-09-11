<?php

namespace App\Repositories;

use App\Models\ConfigPergunta;
use App\Repositories\Contracts\CrudRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ConfiguradorPerguntaRepository implements CrudRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return ConfigPergunta::orderBy('cfg_perg_id', 'desc')->get();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $pergunta = ConfigPergunta::create([
                'cfg_perg_texto' => $data['cfg_perg_texto'],
                'cfg_perg_tipo' => $data['cfg_perg_tipo'],
                'cfg_perg_permite_anexo' => $data['cfg_perg_permite_anexo'] ?? false,
                'cfg_perg_repetivel' => $data['cfg_perg_repetivel'] ?? false,
                'cfg_perg_ativo' => 1,
            ]);

            $this->sincronizarOpcoes($pergunta, $data['opcoes'] ?? []);

            return $pergunta;
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $pergunta = ConfigPergunta::findOrFail($id);

            $pergunta->update([
                'cfg_perg_texto' => $data['cfg_perg_texto'],
                'cfg_perg_tipo' => $data['cfg_perg_tipo'],
                'cfg_perg_permite_anexo' => $data['cfg_perg_permite_anexo'] ?? false,
                'cfg_perg_repetivel' => $data['cfg_perg_repetivel'] ?? false,
                'cfg_perg_ativo' => $data['cfg_perg_ativo'] ?? $pergunta->cfg_perg_ativo,
            ]);

            $this->sincronizarOpcoes($pergunta, $data['opcoes'] ?? []);

            return $pergunta;
        });
    }

    private function sincronizarOpcoes(ConfigPergunta $pergunta, array $opcoes): void
    {
        $pergunta->opcoes()->delete();

        $linhas = collect($opcoes)
            ->filter(fn ($o) => filled($o['texto'] ?? null))
            ->map(fn ($o) => [
                'cfg_perg_op_pergunta_id' => $pergunta->cfg_perg_id,
                'cfg_perg_op_texto' => $o['texto'],
            ])
            ->all();

        if ($linhas !== []) {
            $pergunta->opcoes()->insert($linhas);
        }
    }
}