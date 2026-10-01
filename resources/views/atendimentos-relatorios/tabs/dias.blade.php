{{-- HORÁRIOS E CLIMA — RF012: um lançamento por dia (data + 4 horas +
     clima manhã/tarde/noite). Relatório que já tem horário/clima no formato
     antigo mostra esse lançamento somente leitura, sem "Adicionar dia". --}}
@php
    $periodosClima = ['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite'];
    $opcoesClima = [
        ''           => ['Não informado', 'fas fa-minus text-muted'],
        'ensolarado' => ['Ensolarado', 'fas fa-sun text-warning'],
        'nublado'    => ['Nublado', 'fas fa-cloud text-secondary'],
        'chuvoso'    => ['Chuvoso', 'fas fa-cloud-rain text-primary'],
    ];
@endphp
<div class="tab-pane fade" id="tab-dias" role="tabpanel">
    <div class="mb-3">
        <button type="button" id="btnAddDia" class="btn btn-primary btn-icon-split" style="display:none;">
            <span class="icon text-white-50">
                <i class="fas fa-plus"></i>
            </span>
            <span class="text">Adicionar dia</span>
        </button>
    </div>

    <div id="diasLegadoAviso" class="alert alert-secondary py-2" style="display:none;">
        Relatório no formato antigo de horários — somente leitura.
    </div>

    <ul id="listaDias" class="list-group"></ul>
    <div id="diasVazio" class="text-muted" style="display:none;">Nenhum dia lançado.</div>
</div>

<div class="modal fade" id="modal_dia" tabindex="-1" role="dialog" aria-labelledby="modal_dia_label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold" id="modal_dia_label">Adicionar dia</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="form_dia" autocomplete="off">
                    <div class="form-group">
                        <label for="dia_data" class="font-weight-bold mb-1">Data</label>
                        <input type="date" class="form-control" id="dia_data" max="{{ now()->format('Y-m-d') }}" required>
                        <small class="text-muted" id="dia_data_ajuda" style="display:none;">
                            A data de um dia lançado não muda — para trocar, exclua e inclua de novo.
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="dia_entrada" class="font-weight-bold mb-1">Entrada</label>
                            <input type="time" class="form-control" id="dia_entrada">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="dia_inicio_intervalo" class="font-weight-bold mb-1">Início Intervalo</label>
                            <input type="time" class="form-control" id="dia_inicio_intervalo">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="dia_fim_intervalo" class="font-weight-bold mb-1">Retorno Intervalo</label>
                            <input type="time" class="form-control" id="dia_fim_intervalo">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="dia_saida" class="font-weight-bold mb-1">Saída</label>
                            <input type="time" class="form-control" id="dia_saida">
                        </div>
                    </div>

                    <div class="row">
                        @foreach($periodosClima as $periodo => $rotuloPeriodo)
                            <div class="col-md-4 mb-2">
                                <div class="card shadow-sm h-100">
                                    <div class="card-header text-center font-weight-bold py-2">{{ $rotuloPeriodo }}</div>
                                    <div class="card-body py-2">
                                        @foreach($opcoesClima as $valor => [$rotulo, $icone])
                                            @php
                                                $idRadio = 'dia_clima_' . $periodo . '_' . ($valor ?: 'nao');
                                                // Mesmo rótulo da aba antiga: "ensolarado" à noite é "Céu limpo".
                                                if ($periodo === 'noite' && $valor === 'ensolarado') {
                                                    [$rotulo, $icone] = ['Céu limpo', 'fas fa-moon text-dark'];
                                                }
                                            @endphp
                                            <div class="custom-control custom-radio mb-1">
                                                <input type="radio" id="{{ $idRadio }}" name="dia_clima_{{ $periodo }}" class="custom-control-input" value="{{ $valor }}">
                                                <label class="custom-control-label" for="{{ $idRadio }}">
                                                    <i class="{{ $icone }} mr-1"></i> {{ $rotulo }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <x-modal-footer />
                </form>
            </div>
        </div>
    </div>
</div>
