@props([
    'title' => '',
    'value' => '',
    'icon' => 'bi-graph-up',
    'change' => null,
    'changeLabel' => null,
])
@php
    $isPositive = is_numeric($change) ? $change >= 0 : null;
@endphp
<div {{ $attributes->class(['sbadmin-kpi-card']) }}>
    <div class="sbadmin-kpi-icon">
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
    </div>
    <div class="sbadmin-kpi-body">
        <p class="sbadmin-kpi-title">{{ $title }}</p>
        <p class="sbadmin-kpi-value">{{ $value }}</p>
        @if(! is_null($change) && is_numeric($change))
            <p class="sbadmin-kpi-change @if($isPositive) is-positive @else is-negative @endif">
                <i class="bi {{ $isPositive ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}" aria-hidden="true"></i>
                <span>{{ number_format(abs($change), 1, ',', '.') }}%</span>
                @if($changeLabel)
                    <span class="sbadmin-kpi-change-label">{{ $changeLabel }}</span>
                @endif
            </p>
        @endif
    </div>
</div>
