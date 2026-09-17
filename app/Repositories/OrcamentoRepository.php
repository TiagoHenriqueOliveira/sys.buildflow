<?php

namespace App\Repositories;

use App\Enums\NivelOrcamento;
use App\Models\Orcamento;
use App\Repositories\Contracts\CrudRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrcamentoRepository implements CrudRepositoryInterface
{
    public function all(): \Illuminate\Support\Collection
    {
        return Orcamento::orderByDesc('orc_id')->get();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $orcamento = Orcamento::create([
                ...$this->camposPrincipais($data),
                'orc_ativo' => 1,
                'orc_criado_em' => now(),
            ]);

            $this->sincronizarRespostas($orcamento, $data['respostas'] ?? []);
            $this->sincronizarVendedoresAdicionais($orcamento, $data['vendedores_adicionais'] ?? []);

            return $orcamento;
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $orcamento = Orcamento::findOrFail($id);

            $orcamento->update([
                ...$this->camposPrincipais($data),
                'orc_ativo' => $data['orc_ativo'] ?? $orcamento->orc_ativo,
            ]);

            $this->sincronizarRespostas($orcamento, $data['respostas'] ?? []);
            $this->sincronizarVendedoresAdicionais($orcamento, $data['vendedores_adicionais'] ?? []);

            return $orcamento;
        });
    }

    private function camposPrincipais(array $data): array
    {
        $nivel = $data['orc_nivel'] ?? null;
        $prazo = $data['orc_prazo_envio'] ?? null;

        // CRM02 — se um nível foi escolhido e nenhum prazo foi digitado à
        // mão, sugere um prazo mockado (fórmula real fica pra Etapa 2).
        if ($nivel !== null && $prazo === null) {
            $prazo = now()->addDays(NivelOrcamento::from((int) $nivel)->prazoEmDiasMockado())->toDateString();
        }

        return [
            'orc_cliente_id' => $data['orc_cliente_id'],
            'orc_vendedor_id' => $data['orc_vendedor_id'],
            'orc_tipo_orcamento_id' => $data['orc_tipo_orcamento_id'] ?? null,
            'orc_nivel' => $nivel,
            'orc_prazo_envio' => $prazo,
            'orc_resultado' => $data['orc_resultado'] ?? null,
        ];
    }

    private function sincronizarRespostas(Orcamento $orcamento, array $respostas): void
    {
        $orcamento->respostas()->delete();

        $linhas = collect($respostas)
            ->map(fn ($valor, $perguntaId) => [
                'orc_resp_orcamento_id' => $orcamento->orc_id,
                'orc_resp_pergunta_id' => (int) $perguntaId,
                'orc_resp_valor' => is_array($valor) ? json_encode(array_values($valor)) : $valor,
            ])
            ->filter(fn ($linha) => filled($linha['orc_resp_valor']))
            ->values()
            ->all();

        if ($linhas !== []) {
            $orcamento->respostas()->insert($linhas);
        }
    }

    private function sincronizarVendedoresAdicionais(Orcamento $orcamento, array $usuarioIds): void
    {
        $orcamento->vendedoresAdicionais()->sync(array_map('intval', $usuarioIds));
    }
}