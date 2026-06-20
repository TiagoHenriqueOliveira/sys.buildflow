@props([
    'tipos'    => collect(),
    'selected' => '',
    'id'       => 'nat_aten_tp_atendimento_id',
    'name'     => 'nat_aten_tp_atendimento_id',
])

<div class="form-group row">
    <label for="{{ $id }}" class="col-sm-3 col-form-label font-weight-bold">Setor:</label>

    <div class="col-sm-9">
        <select class="form-control" id="{{ $id }}" name="{{ $name }}" required>
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
