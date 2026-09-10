@props([
    'name',
    'label' => null,
    'value' => '1',
    'checked' => false,
    'help' => null,
    'switch' => false,
])
@php
    $errorBag = $errors ?? app('view')->shared('errors');
    $hasError = $errorBag && $errorBag->has($name);
    $id = $attributes->get('id', 'sbadmin-field-'.str_replace(['[', ']', '.'], '-', $name));
    $isChecked = (bool) old($name, $checked);
@endphp
{{-- `switch` liga o visual de toggle pill (form-switch) do Bootstrap 5 em
     vez do checkbox quadrado padrao, mantendo o mesmo <input type="checkbox">
     por baixo (JS que le/seta `.checked` via id continua funcionando sem
     nenhuma mudanca). Usado nos campos Ativo/Ativo-Desativado dos modais. --}}
<div class="sbadmin-form-check form-check @if($switch) form-switch @endif">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ $value }}"
        @checked($isChecked)
        @if($switch) role="switch" @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['id'])->class([
            'form-check-input',
            'is-invalid' => $hasError,
        ]) }}
    >
    @if($label)
        <label for="{{ $id }}" class="form-check-label sbadmin-form-label">{{ $label }}</label>
    @endif

    @if($hasError)
        <div class="invalid-feedback d-block" id="{{ $id }}-error">{{ $errorBag->first($name) }}</div>
    @elseif($help)
        <div class="sbadmin-form-help">{{ $help }}</div>
    @endif
</div>
