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
    <title>{{ $title ? $title.' - ' : '' }}{{ config('sbadmin.brand.name') }}</title>

    {{-- Define data-theme/data-bs-theme o mais cedo possivel para evitar flash de tema errado.
         data-bs-theme ativa o color mode nativo do Bootstrap 5.3 (forms, tabelas, dropdowns,
         botoes outline, etc.), para que todo o Bootstrap - nao so os componentes sbadmin -
         se adapte corretamente ao tema escuro. Unico tema suportado e o escuro (sem alternancia -
         ver decisao do produto), entao aqui so fixamos o atributo, sem ler localStorage/preferencia
         do sistema. --}}
    <script>
        (function () {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        })();
    </script>

    {{ $head ?? '' }}

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="sbadmin-body">
    {{-- O rail de icones (desktop) NAO depende mais de uma classe adicionada
         via Alpine (:class="{ 'sidebar-collapsed': ... }") - isso causava um
         flash visivel a cada navegacao (a sidebar nascia larga/com rotulos,
         no CSS padrao pre-Alpine, e so encolhia pro rail quando o JS
         terminava de inicializar, lido como um efeito de "abrir e fechar" em
         todo clique do menu). Agora o rail e puro CSS via media query
         (min-width: lg em _layout.scss), ja correto desde o primeiro paint;
         o mobile continua em largura total com rotulos (off-canvas) porque a
         media query so atinge breakpoints desktop, sem precisar de nenhuma
         logica condicional aqui. --}}
    <div class="sbadmin-wrapper">
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

            {{-- Fora do <main>, irmao dele dentro de .sbadmin-content (que ja e
                 flex-column com o <main> em flex:1) - e assim que o footer do
                 projeto (passado via slot `footer`) gruda no rodape do viewport
                 em paginas curtas e continua fluindo normalmente apos o
                 conteudo em paginas longas, sem position:fixed. --}}
            {{ $footer ?? '' }}
        </div>
    </div>

    <x-sbadmin::confirm-modal />

    @stack('scripts')
</body>
</html>
