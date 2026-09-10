@props([
    'name',
    'label' => null,
    'offLabel' => null,
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
    // Rotulo dinamico (ex.: "Ativo"/"Inativo") so faz sentido combinado com
    // `switch` — um checkbox quadrado comum mantem o rotulo estatico de
    // sempre, mesmo que offLabel seja passado por engano.
    $isDynamicLabel = $switch && $offLabel !== null;
@endphp
{{-- `switch` liga o visual de toggle pill (form-switch) do Bootstrap 5 em
     vez do checkbox quadrado padrao, mantendo o mesmo <input type="checkbox">
     por baixo (JS que le/seta `.checked` via id continua funcionando sem
     nenhuma mudanca). Usado nos campos Ativo/Inativo dos modais.

     `offLabel`: quando informado (junto com `switch`), o rotulo passa a
     refletir o estado atual do proprio input em tempo real via Alpine
     (x-text), alternando entre `label` (marcado) e `offLabel`
     (desmarcado) a cada clique — sem isso, o rotulo ficava sempre fixo no
     texto passado (ex.: sempre "Ativo"), mesmo com o registro inativo. --}}
<div
    class="sbadmin-form-check form-check @if($switch) form-switch @endif"
    @if($isDynamicLabel) x-data="{ sbadminChecked: {{ $isChecked ? 'true' : 'false' }} }" @endif
>
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ $value }}"
        @checked($isChecked)
        @if($switch) role="switch" @endif
        @if($isDynamicLabel) @change="sbadminChecked = $event.target.checked" @endif
        @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['id'])->class([
            'form-check-input',
            'is-invalid' => $hasError,
        ]) }}
    >
    @if($isDynamicLabel)
        <label for="{{ $id }}" class="form-check-label sbadmin-form-label" x-text="sbadminChecked ? {{ Js::from($label) }} : {{ Js::from($offLabel) }}"></label>
    @elseif($label)
        <label for="{{ $id }}" class="form-check-label sbadmin-form-label">{{ $label }}</label>
    @endif

    @if($hasError)
        <div class="invalid-feedback d-block" id="{{ $id }}-error">{{ $errorBag->first($name) }}</div>
    @elseif($help)
        <div class="sbadmin-form-help">{{ $help }}</div>
    @endif
</div>
