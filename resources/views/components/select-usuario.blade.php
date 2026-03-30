@props([
    'usuarios' => collect(),
    'selected' => ''
])

<div class="form-group row">
    <label for="aten_usuario_id" class="col-sm-3 col-form-label font-weight-bold">Usuário:</label>

    <div class="col-sm-9">
        <select class="form-control" name="aten_usuario_id" id="aten_usuario_id">
            <option value="" disabled hidden
                {{ $selected === '' ? 'selected' : '' }}>
                Selecione...
            </option>

            @foreach($usuarios as $u)
            <option value="{{ $u->user_id }}"
                {{ (string)$selected === (string)$u->user_id ? 'selected' : '' }}>
                {{ $u->user_nome }}
            </option>
            @endforeach
        </select>
    </div>
</div>