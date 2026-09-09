@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'help' => null,
    'required' => false,
    'placeholder' => null,
])
@php
    $errorBag = $errors ?? app('view')->shared('errors');
    $hasError = $errorBag && $errorBag->has($name);
    $id = $attributes->get('id', 'sbadmin-field-'.str_replace(['[', ']', '.'], '-', $name));
    $selected = old($name, $value);
@endphp
<div class="sbadmin-form-group">
    @if($label)
        <label for="{{ $id }}" class="sbadmin-form-label">
            {{ $label }}
            @if($required)<span class="sbadmin-required" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if($required) required aria-required="true" @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['id'])->class([
            'form-select',
            'sbadmin-form-control',
            'is-invalid' => $hasError,
        ]) }}
    >
        @if($placeholder)
            <option value="" {{ empty($selected) ? 'selected' : '' }} disabled hidden>{{ $placeholder }}</option>
        @endif

        @if(count($options))
            @foreach($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected((string) $selected === (string) $optValue)>{{ $optLabel }}</option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>

    @if($hasError)
        <div class="invalid-feedback d-block" id="{{ $id }}-error">{{ $errorBag->first($name) }}</div>
    @elseif($help)
        <div class="sbadmin-form-help">{{ $help }}</div>
    @endif
</div>
