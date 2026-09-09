@props([
    'type' => 'neutral',
    'pill' => true,
])
@php
    $map = [
        'success' => 'sbadmin-badge-success',
        'error' => 'sbadmin-badge-error',
        'warning' => 'sbadmin-badge-warning',
        'info' => 'sbadmin-badge-info',
        'neutral' => 'sbadmin-badge-neutral',
    ];
    $class = $map[$type] ?? $map['neutral'];
@endphp
<span {{ $attributes->class(['sbadmin-badge', $class, 'sbadmin-badge-pill' => $pill]) }}>
    {{ $slot }}
</span>
