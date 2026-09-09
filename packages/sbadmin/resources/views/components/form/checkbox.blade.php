@props([
    'name',
    'label' => null,
    'value' => '1',
    'checked' => false,
    'help' => null,
])
@php
    $errorBag = $errors ?? app('view')->shared('errors');
    $hasError = $errorBag && $errorBag->has($name);
    $id = $attributes->get('id', 'sbadmin-field-'.str_replace(['[', ']', '.'], '-', $name));
    $isChecked = (bool) old($name, $checked);
@endphp
<div class="sbadmin-form-check form-check">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ $value }}"
        @checked($isChecked)
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
