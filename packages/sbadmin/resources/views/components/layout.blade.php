@props([
    'title' => null,
    'menu' => null,
    'user' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="sbAdmin()" x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' - ' : '' }}{{ config('sbadmin.brand.name', config('app.name')) }}</title>

    {{-- Define data-theme/data-bs-theme o mais cedo possivel para evitar flash de tema errado.
         data-bs-theme ativa o color mode nativo do Bootstrap 5.3 (forms, tabelas, dropdowns,
         botoes outline, etc.), para que todo o Bootstrap - nao so os componentes sbadmin -
         se adapte corretamente ao tema escuro. --}}
    <script>
        (function () {
            var stored = localStorage.getItem('sbadmin-theme');
            var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    {{ $head ?? '' }}

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="sbadmin-body">
    <div class="sbadmin-wrapper" :class="{ 'sidebar-collapsed': collapsed }">
        <x-sbadmin::sidebar :menu="$menu" />

        <div class="sbadmin-backdrop" x-show="mobileOpen" x-transition.opacity x-cloak @click="mobileOpen = false" aria-hidden="true"></div>

        <div class="sbadmin-content">
            <x-sbadmin::topbar :title="$title" :user="$user">
                {{ $topbarActions ?? '' }}
                <x-slot:userMenu>{{ $userMenu ?? '' }}</x-slot:userMenu>
            </x-sbadmin::topbar>

            <main class="sbadmin-main" id="main-content">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-sbadmin::confirm-modal />

    @stack('scripts')
</body>
</html>
