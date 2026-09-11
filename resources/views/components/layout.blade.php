@props([
    'title' => null,
])
@php
    // Menu da sidebar montado aqui (em vez de ficar 100% estático em
    // config/sbadmin.php) porque dois itens — "Clientes" e todo o submenu
    // "Configurações" — só aparecem para administradores
    // (Auth::user()->user_nivel_acesso === 0), igual ao @if condicional que
    // existia no template legado. O componente <x-sbadmin::sidebar> não tem
    // nenhum hook de visibilidade/permissão por item (ver
    // packages/sbadmin/resources/views/components/sidebar-item.blade.php);
    // o próprio README do pacote documenta a alternativa suportada — passar
    // um menu diferente por página via prop `:menu` em <x-sbadmin::layout>
    // — que é exatamente o que fazemos aqui, computando o array condicional
    // em PHP puro antes de renderizar o layout do pacote. Isso evita
    // qualquer lógica condicional dentro do config/sbadmin.php (que é um
    // array estático) ou dentro dos componentes do pacote (que devem
    // continuar genéricos/reutilizáveis por outros projetos).
    $usuario = Auth::user();
    $isAdmin = $usuario && (int) $usuario->user_nivel_acesso === \App\Enums\NivelAcesso::Administrador->value;
    $isComercial = $usuario && (int) $usuario->user_nivel_acesso === \App\Enums\NivelAcesso::Comercial->value;

    $menu = [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'dashboard'],
        ['label' => 'Atendimentos', 'icon' => 'bi-headset', 'route' => 'atendimentos.index'],
    ];

    // Clientes/Orçamentos: Administrador (gestão completa) e Comercial
    // (BF02/CRM — dono do relacionamento comercial) têm acesso; Técnico não.
    if ($isAdmin || $isComercial) {
        $menu[] = ['label' => 'Clientes', 'icon' => 'bi-person-vcard', 'route' => 'clientes.index'];
        $menu[] = ['label' => 'Orçamentos', 'icon' => 'bi-cash-coin', 'route' => 'orcamentos.index'];
        $menu[] = ['label' => 'Roteiro de Viagem', 'icon' => 'bi-signpost-2', 'route' => 'roteiros-viagem.index'];
        $menu[] = ['label' => 'Mapa de Relações', 'icon' => 'bi-geo-alt', 'route' => 'mapa-relacoes.index'];
        $menu[] = ['label' => 'Indicadores Comerciais', 'icon' => 'bi-graph-up', 'route' => 'indicadores-comerciais.index'];
    }

    $menu[] = ['label' => 'Relatórios', 'icon' => 'bi-bar-chart-line', 'route' => 'atendimentos-relatorios.index'];
    $menu[] = ['label' => 'Mapa de Demandas', 'icon' => 'bi-geo', 'route' => 'mapa-demandas.index'];

    if ($isAdmin) {
        $menu[] = [
            'label' => 'Configurações',
            'icon' => 'bi-gear',
            'children' => [
                ['label' => 'Tipos de Orçamento (CRM)', 'icon' => 'bi-tags', 'route' => 'crm.tipos-orcamento.index'],
                [
                    'label' => 'Configurador de Relatórios',
                    'icon' => 'bi-sliders',
                    'children' => [
                        ['label' => 'Perguntas', 'icon' => 'bi-question-circle', 'route' => 'configurador.perguntas.index'],
                        ['label' => 'Modelos', 'icon' => 'bi-diagram-3', 'route' => 'configurador.modelos.index'],
                    ],
                ],
                ['label' => 'Naturezas de Atendimentos', 'icon' => 'bi-tags', 'route' => 'naturezas-dos-atendimentos.index'],
                ['label' => 'Classificações de Cliente', 'icon' => 'bi-tag', 'route' => 'classificacoes-cliente.index'],
                ['label' => 'Ocorrências', 'icon' => 'bi-exclamation-triangle', 'route' => 'ocorrencias.index'],
                ['label' => 'Usuários', 'icon' => 'bi-people', 'route' => 'usuarios.index'],
                ['label' => 'Logs de Auditoria', 'icon' => 'bi-clock-history', 'route' => 'logs-auditoria.index'],
            ],
        ];
    }
@endphp
<x-sbadmin::layout
    :title="$title ?? null"
    :menu="$menu"
    :user="$usuario ? ['name' => $usuario->user_nome, 'email' => $usuario->user_email] : null"
>
    {{-- Menu do usuário (topbar): substitui o antigo modal Bootstrap 4
         ("Sair do sistema" / data-toggle="modal") — <x-sbadmin::topbar> já
         expõe o slot `userMenu` justamente pra isso, então usamos ele em vez
         de reimplementar dropdown/modal na mão. A confirmação em si usa o
         <x-sbadmin::confirm-modal /> já montado uma única vez no layout do
         pacote, disparado pelo helper window.confirmar() registrado em
         resources/js/confirm-modal.js (ver Alpine.store('confirmacao')). --}}
    <x-slot:userMenu>
        <a
            href="#"
            class="sbadmin-dropdown-item"
            @click.prevent="confirmar('Deseja encerrar sua sessão?', { rotulo: 'Sair', onConfirm: () => document.getElementById('sbadmin-logout-form').submit() })"
        >
            <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sair
        </a>
        <form id="sbadmin-logout-form" method="POST" action="{{ route('logout') }}" class="d-none">
            @csrf
        </form>
    </x-slot:userMenu>

    {{ $slot }}

    {{-- Slot dedicado `footer` (fora de <main>, ver components/layout.blade.php
         do pacote) - e como o rodape gruda no fim do viewport em paginas
         curtas sem virar position:fixed. --}}
    <x-slot:footer>
        <footer class="sbadmin-app-footer text-center small text-body-secondary mt-5 mb-3">
            {{ config('sbadmin.brand.name') }} &copy; {{ date('Y') }} &mdash; v{{ config('app.version') }}
        </footer>
    </x-slot:footer>
</x-sbadmin::layout>
