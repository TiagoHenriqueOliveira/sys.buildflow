@props([
    'naturezas' => collect(),
    'selected' => '',
    'id' => 'aten_nat_atendimento_id',
    'name' => 'aten_nat_atendimento_id',
])

<div class="form-group row">
    <label for="{{ $id }}" class="col-sm-3 col-form-label font-weight-bold">Natureza:</label>

    <div class="col-sm-9">
        <select class="form-control" id="{{ $id }}" name="{{ $name }}" required>
            <option value="" disabled hidden {{ $selected === '' ? 'selected' : '' }}>Selecione...</option>

            @foreach($naturezas as $n)
            <option value="{{ $n->nat_aten_id }}"
                data-tp-aten-id="{{ $n->nat_aten_tp_atendimento_id }}"
                {{ (string)$selected === (string)$n->nat_aten_id ? 'selected' : '' }}>
                {{ $n->nat_aten_descricao }}
            </option>
            @endforeach
        </select>
    </div>
</div>