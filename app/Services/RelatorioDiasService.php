<?php

namespace App\Services;

use App\Enums\CondicaoClimatica;
use App\Exceptions\RelatorioDiaLegadoException;
use App\Models\AtendimentoRelatorio;
use App\Models\AtendimentoRelatorioDia;

/**
 * RF012 — horário e clima por dia. Substitui, para relatórios novos, o par
 * horarios()/climas() (uma linha só por relatório) por uma linha por dia em
 * atendimentos_relatorios_dias. Relatórios que já têm dado no formato antigo
 * ficam congelados nesse formato (ver usaHorarioLegado) — nunca convertidos.
 */
class RelatorioDiasService
{
    public function serializar(AtendimentoRelatorioDia $dia): array
    {
        return [
            'id' => $dia->aten_rel_dia_id,
            'data' => $dia->aten_rel_dia_data->format('Y-m-d'),
            'entrada' => $this->formatarHora($dia->aten_rel_dia_hora_entrada),
            'inicio_intervalo' => $this->formatarHora($dia->aten_rel_dia_hora_inicio_intervalo),
            'fim_intervalo' => $this->formatarHora($dia->aten_rel_dia_hora_fim_intervalo),
            'saida' => $this->formatarHora($dia->aten_rel_dia_hora_saida),
            'clima' => [
                'manha' => $this->valorParaClima($dia->aten_rel_dia_clima_manha),
                'tarde' => $this->valorParaClima($dia->aten_rel_dia_clima_tarde),
                'noite' => $this->valorParaClima($dia->aten_rel_dia_clima_noite),
            ],
        ];
    }

    /** Usa a relação `dias` já carregada (eager load), se houver. */
    public function listar(AtendimentoRelatorio $relatorio): array
    {
        $dias = $relatorio->relationLoaded('dias') ? $relatorio->dias : $relatorio->dias()->get();

        return $dias->map(fn (AtendimentoRelatorioDia $dia) => $this->serializar($dia))->values()->all();
    }

    /**
     * True somente quando o relatório não tem nenhum dia novo E tem algum
     * registro no formato antigo (horário ou clima) — contrato 3.1. Usa as
     * relações já carregadas quando houver (show/pdf fazem eager load).
     */
    public function usaHorarioLegado(AtendimentoRelatorio $relatorio): bool
    {
        $temDias = $relatorio->relationLoaded('dias')
            ? $relatorio->dias->isNotEmpty()
            : $relatorio->dias()->exists();
        if ($temDias) {
            return false;
        }

        $temHorario = $relatorio->relationLoaded('horarios')
            ? $relatorio->horarios !== null
            : $relatorio->horarios()->exists();
        $temClima = $relatorio->relationLoaded('climas')
            ? $relatorio->climas->isNotEmpty()
            : $relatorio->climas()->exists();

        return $temHorario || $temClima;
    }

    /**
     * Upsert idempotente pela chave (relatório, data). $dados já deve vir
     * validado (UpsertDiaRequest) — horas em "H:i" ou null, clima em
     * ensolarado|nublado|chuvoso ou null.
     *
     * @throws RelatorioDiaLegadoException se o relatório estiver no formato antigo.
     */
    public function upsert(AtendimentoRelatorio $relatorio, string $data, array $dados): AtendimentoRelatorioDia
    {
        // withoutRelations(): a checagem tem que refletir o banco agora, não
        // uma relação carregada antes (que poderia estar desatualizada).
        if ($this->usaHorarioLegado($relatorio->withoutRelations())) {
            throw new RelatorioDiaLegadoException();
        }

        $valores = [
            'aten_rel_dia_hora_entrada' => $dados['entrada'] ?? null,
            'aten_rel_dia_hora_inicio_intervalo' => $dados['inicio_intervalo'] ?? null,
            'aten_rel_dia_hora_fim_intervalo' => $dados['fim_intervalo'] ?? null,
            'aten_rel_dia_hora_saida' => $dados['saida'] ?? null,
            'aten_rel_dia_clima_manha' => $this->climaParaValor($dados['clima']['manha'] ?? null),
            'aten_rel_dia_clima_tarde' => $this->climaParaValor($dados['clima']['tarde'] ?? null),
            'aten_rel_dia_clima_noite' => $this->climaParaValor($dados['clima']['noite'] ?? null),
        ];

        // Corrida (duas requisições criando o mesmo dia novo ao mesmo tempo) já
        // é tratada pelo próprio Laravel 10.20+: updateOrCreate -> firstOrCreate
        // -> createOrFirst captura a violação do UNIQUE (relatório, data), relê
        // a linha inserida pela outra requisição e aplica o update nela. Não
        // chamar isto dentro de uma transação: a releitura usaria o snapshot
        // antigo (REPEATABLE READ) e não enxergaria a linha da outra requisição.
        return $relatorio->dias()->updateOrCreate(['aten_rel_dia_data' => $data], $valores);
    }

    /** Idempotente: não lança nada se o dia não existir. */
    public function excluir(AtendimentoRelatorio $relatorio, string $data): void
    {
        $relatorio->dias()->where('aten_rel_dia_data', $data)->delete();
    }

    private function formatarHora(?string $hora): ?string
    {
        return $hora ? substr($hora, 0, 5) : null;
    }

    private function valorParaClima(?int $valor): ?string
    {
        return $valor === null ? null : CondicaoClimatica::from($valor)->label();
    }

    private function climaParaValor(?string $label): ?int
    {
        return $label === null ? null : CondicaoClimatica::fromLabel($label)->value;
    }
}
