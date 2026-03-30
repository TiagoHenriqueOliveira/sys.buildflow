@props(['tipos'])

<div class="form-group row">
    <label class="col-sm-3 col-form-label font-weight-bold">Tipo de Atendimento:</label>
    <div class="col-sm-9">
        <select class="form-control" name="aten_tp_atendimento_id" id="aten_tp_atendimento_id">
            <option value="">Selecione...</option>
            @foreach($tipos as $t)
            <option value="{{ $t->tp_aten_id }}">{{ $t->tp_aten_descricao }}</option>
            @endforeach
        </select>
    </div>
</div>