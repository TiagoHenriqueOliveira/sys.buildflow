@props([
    'type' => 'info',
    'dismissible' => true,
    'icon' => true,
])
@php
    $map = [
        'success' => ['class' => 'sbadmin-alert-success', 'icon' => 'bi-check-circle-fill'],
        'error' => ['class' => 'sbadmin-alert-error', 'icon' => 'bi-x-circle-fill'],
        'warning' => ['class' => 'sbadmin-alert-warning', 'icon' => 'bi-exclamation-triangle-fill'],
        'info' => ['class' => 'sbadmin-alert-info', 'icon' => 'bi-info-circle-fill'],
    ];
    $config = $map[$type] ?? $map['info'];
@endphp
<div
    {{ $attributes->class(['sbadmin-alert', $config['class'], 'sbadmin-alert-dismissible' => $dismissible]) }}
    role="alert"
    @if($dismissible) x-data="{ show: true }" x-show="show" x-transition @endif
>
    @if($icon)
        <i class="bi {{ $config['icon'] }} sbadmin-alert-icon" aria-hidden="true"></i>
    @endif

    <div class="sbadmin-alert-content">{{ $slot }}</div>

    @if($dismissible)
        <button type="button" class="sbadmin-alert-close" @click="show = false" aria-label="Fechar alerta">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    @endif
</div>
