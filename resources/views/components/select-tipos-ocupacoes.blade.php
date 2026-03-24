@props([
    'tipos' => collect(),
    'selected' => '',
    'id' => 'ocup_tp_ocupacao_id',
    'name' => 'ocup_tp_ocupacao_id',
    ])

<div class="form-group row">
    <label for="{{ $id }}" class="col-sm-3 col-form-label font-weight-bold">
        Tipo de Ocupação:
    </label>
    <div class="col-sm-9">
        <select class="form-control" id="{{ $id }}" name="{{ $name }}">
            <option value="">Selecione...</option>
            @foreach($tipos as $t)
            <option value="{{ $t->tp_ocup_id }}"
                {{ (string)$selected === (string)$t->tp_ocup_id ? 'selected' : '' }}>
                {{ $t->tp_ocup_descricao }}
            </option>
            @endforeach
        </select>
    </div>
</div>