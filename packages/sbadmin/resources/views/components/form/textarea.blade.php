@props([
    'name',
    'label' => null,
    'value' => null,
    'help' => null,
    'required' => false,
    'rows' => 4,
])
@php
    $errorBag = $errors ?? app('view')->shared('errors');
    $hasError = $errorBag && $errorBag->has($name);
    $id = $attributes->get('id', 'sbadmin-field-'.str_replace(['[', ']', '.'], '-', $name));
@endphp
<div class="sbadmin-form-group">
    @if($label)
        <label for="{{ $id }}" class="sbadmin-form-label">
            {{ $label }}
            @if($required)<span class="sbadmin-required" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if($required) required aria-required="true" @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['id'])->class([
            'form-control',
            'sbadmin-form-control',
            'is-invalid' => $hasError,
        ]) }}
    >{{ old($name, $value) }}</textarea>

    @if($hasError)
        <div class="invalid-feedback d-block" id="{{ $id }}-error">{{ $errorBag->first($name) }}</div>
    @elseif($help)
        <div class="sbadmin-form-help">{{ $help }}</div>
    @endif
</div>
