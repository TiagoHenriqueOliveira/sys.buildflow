@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'help' => null,
    'required' => false,
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

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ old($name, $value) }}"
        @if($required) required aria-required="true" @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['id'])->class([
            'form-control',
            'sbadmin-form-control',
            'is-invalid' => $hasError,
        ]) }}
    >

    @if($hasError)
        <div class="invalid-feedback d-block" id="{{ $id }}-error">{{ $errorBag->first($name) }}</div>
    @elseif($help)
        <div class="sbadmin-form-help">{{ $help }}</div>
    @endif
</div>
