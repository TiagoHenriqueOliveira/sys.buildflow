@props(['usuarios'])

<div class="form-group row">
    <label class="col-sm-3 col-form-label font-weight-bold">Usuário:</label>
    <div class="col-sm-9">
        <select class="form-control" name="aten_usuario_id" id="aten_usuario_id">
            <option value="">Selecione...</option>
            @foreach($usuarios as $u)
            <option value="{{ $u->user_id }}">{{ $u->user_nome }}</option>
            @endforeach
        </select>
    </div>
</div>