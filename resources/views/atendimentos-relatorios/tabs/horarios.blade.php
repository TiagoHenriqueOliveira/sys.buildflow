{{-- HORÁRIOS --}}
<div class="tab-pane fade" id="tab-horarios" role="tabpanel">
    <form id="form_relatorio_horarios"
          data-action="{{ route('atendimentos-relatorios.update-horarios', $atendimentoRelatorio->aten_rel_id) }}">
        @csrf

        <div class="row">
            <div class="col-md-3">
                <label class="font-weight-bold">Entrada</label>
                <input type="time" name="aten_rel_hora_entrada" class="form-control" value="{{ $atendimentoRelatorio->horarios?->aten_rel_hora_entrada ? substr($atendimentoRelatorio->horarios->aten_rel_hora_entrada, 0, 5) : '' }}">
            </div>

            <div class="col-md-3">
                <label class="font-weight-bold">Início Intervalo</label>
                <input type="time" name="aten_rel_hora_inicio_intervalo" class="form-control" value="{{ $atendimentoRelatorio->horarios?->aten_rel_hora_inicio_intervalo ? substr($atendimentoRelatorio->horarios->aten_rel_hora_inicio_intervalo, 0, 5) : '' }}">
            </div>

            <div class="col-md-3">
                <label class="font-weight-bold">Fim Intervalo</label>
                <input type="time" name="aten_rel_hora_fim_intervalo" class="form-control" value="{{ $atendimentoRelatorio->horarios?->aten_rel_hora_fim_intervalo ? substr($atendimentoRelatorio->horarios->aten_rel_hora_fim_intervalo, 0, 5) : '' }}">
            </div>

            <div class="col-md-3">
                <label class="font-weight-bold">Saída</label>
                <input type="time" name="aten_rel_hora_saida" class="form-control" value="{{ $atendimentoRelatorio->horarios?->aten_rel_hora_saida ? substr($atendimentoRelatorio->horarios->aten_rel_hora_saida, 0, 5) : '' }}">
            </div>
        </div>
    </form>
</div>