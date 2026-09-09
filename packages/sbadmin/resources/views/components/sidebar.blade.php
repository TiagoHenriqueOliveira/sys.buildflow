@props([
    'menu' => null,
])
@php
    $items = $menu ?? config('sbadmin.menu', []);
@endphp
<aside class="sbadmin-sidebar" id="sbadmin-sidebar" aria-label="Menu principal" :class="{ 'is-open': mobileOpen }">
    <div class="sbadmin-sidebar-brand">
        <a href="{{ url('/') }}" class="sbadmin-brand-link">
            @if(config('sbadmin.brand.logo'))
                <img src="{{ config('sbadmin.brand.logo') }}" alt="{{ config('sbadmin.brand.name') }}" class="sbadmin-brand-logo">
            @else
                {{-- Sem logo configurado ainda (cliente vai fornecer a marca da
                     FAE Bioenergia futuramente) - placeholder vazio do mesmo
                     tamanho do logo real, so pra reservar o espaco e o layout
                     nao pular quando a imagem for adicionada. --}}
                <span class="sbadmin-brand-logo-placeholder" aria-hidden="true"></span>
            @endif
            <span class="sbadmin-brand-text">{{ config('sbadmin.brand.name') }}</span>
        </a>
        <button type="button" class="sbadmin-sidebar-close d-lg-none" @click="mobileOpen = false" aria-label="Fechar menu">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="sbadmin-nav">
        <ul class="sbadmin-nav-list">
            @foreach($items as $index => $item)
                <x-sbadmin::sidebar-item :item="$item" :depth="0" :index="$index" />
            @endforeach
        </ul>
    </nav>
</aside>
