@props([
    'modelosRelatorios' => collect(),
    'selected' => ''
])

<div class="form-group row">
    <label for="nat_aten_mod_relatorio_id" class="col-sm-3 col-form-label font-weight-bold"></label>

    <div class="col-sm-9">
        <select class="form-control" id="nat_aten_mod_relatorio_id" name="nat_aten_mod_relatorio_id">
            <option value="" disabled hidden
                {{ $selected === '' ? 'selected' : '' }}>
                Selecione...
            </option>

            @foreach($modelosRelatorios as $m)
            <option value="{{ $m->mod_rel_id }}"
                {{ (string)$selected === (string)$m->mod_rel_id ? 'selected' : '' }}>
                {{ $m->mod_rel_descricao }}
            </option>
            @endforeach
        </select>
    </div>
</div>