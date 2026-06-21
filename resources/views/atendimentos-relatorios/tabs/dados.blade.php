<div class="tab-pane fade show active" id="tab-dados" role="tabpanel">
    <form id="form_relatorio_dados" data-action="{{ route('atendimentos-relatorios.update-dados', $atendimentoRelatorio->aten_rel_id) }}">
        @csrf
        {{-- LINHA: Data | Dia da Semana | Prazo | Decorrido | À Vencer --}}
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="font-weight-bold">Data</label>
                <input type="date" name="aten_rel_data" class="form-control"
                    value="{{ $atendimentoRelatorio->aten_rel_data->format('Y-m-d') }}"
                    max="{{ now()->format('Y-m-d') }}">
            </div>

            <div class="col-md-3">
                <label class="font-weight-bold">Dia da Semana</label>
                <span class="readonly-field dia-semana form-control-plaintext">
                    {{ getFormatDiaSemana($atendimentoRelatorio->aten_rel_data) }}
                </span>
            </div>

            <div class="col-md-2">
                <label class="font-weight-bold">Prazo (dias)</label>
                <span class="readonly-field prazo-total form-control-plaintext text-indigo">
                    {{ $prazoTotal }} dias
                </span>
            </div>

            <div class="col-md-2">
                <label class="font-weight-bold">Prazo Decorrido</label>
                <span class="readonly-field prazo-decorrido form-control-plaintext text-warning">
                    {{ $prazoDecorrido }} dias
                </span>
            </div>

            <div class="col-md-2">
                <label class="font-weight-bold">Prazo à Vencer</label>
                <span class="readonly-field prazo-vencer form-control-plaintext text-success">
                    {{ $prazoAVencer }} dias
                </span>
            </div>
        </div>

        {{-- LINHA: Cliente | Responsável | Nº Proposta --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="font-weight-bold">Cliente</label>
                <span class="readonly-field form-control-plaintext">
                    {{ $atendimentoRelatorio->atendimento->cliente->cli_nome }}
                </span>
            </div>

            <div class="col-md-4">
                <label class="font-weight-bold">Responsável</label>
                <span class="readonly-field form-control-plaintext">
                    {{ $atendimentoRelatorio->atendimento->aten_responsavel }}
                </span>
            </div>

            <div class="col-md-2">
                <label class="font-weight-bold">Nº Proposta</label>
                <span class="readonly-field form-control-plaintext">
                    {{ $atendimentoRelatorio->atendimento->aten_nr_proposta }}
                </span>
            </div>
        </div>

        {{-- LINHA: Endereço --}}
        <div class="row mb-3">
            <div class="col-md-12">
                <label class="font-weight-bold">Endereço</label>
                <span class="readonly-field form-control-plaintext">
                    {{ $atendimentoRelatorio->atendimento->aten_endereco }}
                </span>
            </div>
        </div>
    </form>
</div>