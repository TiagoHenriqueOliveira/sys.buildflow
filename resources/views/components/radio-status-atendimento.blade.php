<div class="form-group row">
    <label class="col-sm-3 col-form-label font-weight-bold">Status:</label>
    <div class="col-sm-9 pt-2">
        @foreach([
        0 => 'Não iniciada',
        1 => 'Paralisada',
        2 => 'Em andamento',
        3 => 'Concluída'
        ] as $val => $label)
        <div class="custom-control custom-radio custom-control-inline">
            <input type="radio"
                id="aten_status_{{ $val }}"
                name="aten_status"
                value="{{ $val }}"
                class="custom-control-input"
                {{ $val === 0 ? 'checked' : '' }}>
            <label class="custom-control-label" for="aten_status_{{ $val }}">
                {{ $label }}
            </label>
        </div>
        @endforeach
    </div>
</div>