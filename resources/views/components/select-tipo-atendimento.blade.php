@props([
    'tipos',
    'selected' => ''
])

<div class="form-group row">
    <label for="aten_tp_atendimento_id" class="col-sm-3 col-form-label font-weight-bold">Setor:</label>

    <div class="col-sm-9">
        <select class="form-control" name="aten_tp_atendimento_id" id="aten_tp_atendimento_id">
            <option value="" disabled hidden {{ $selected === '' ? 'selected' : '' }}>Selecione...</option>

            @foreach($tipos as $t)
            <option value="{{ $t->tp_aten_id }}"
                {{ (string)$selected === (string)$t->tp_aten_id ? 'selected' : '' }}>
                {{ $t->tp_aten_descricao }}
            </option>
            @endforeach
        </select>
    </div>
</div>