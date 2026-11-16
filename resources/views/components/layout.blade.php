@props([
    'title' => null,
])
@php
    // Menu da sidebar montado aqui (em vez de ficar 100% estático em
    // config/sbadmin.php) porque cada item depende das permissões do
    // usuário. O componente <x-sbadmin::sidebar> não tem hook de
    // visibilidade por item; o README do pacote documenta a alternativa
    // suportada — passar o menu via prop `:menu` em <x-sbadmin::layout> —,
    // então o array é computado aqui em PHP e o pacote continua genérico.
    //
    // Pedido do cliente (2026-11-16): cada item aparece conforme a permissão
    // "<tela>.ver" do cadastro "Perfis de Acesso" (App\Support\Permissoes);
    // Administrador e o usuário protegido veem tudo. Um item sem permissão
    // some; "Configurações" some inteiro se nenhum filho sobrar.
    $usuario = Auth::user();
    $pode = fn (string $chave) => $usuario && $usuario->temPermissao($chave);
    $item = fn (string $chave, array $dados) => $pode($chave) ? [$dados] : [];

    $menu = [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'dashboard'],
        ...$item('atendimentos.ver', ['label' => 'Atendimentos', 'icon' => 'bi-headset', 'route' => 'atendimentos.index']),
        ...$item('clientes.ver', ['label' => 'Clientes', 'icon' => 'bi-person-vcard', 'route' => 'clientes.index']),
        ...$item('orcamentos.ver', ['label' => 'Orçamentos', 'icon' => 'bi-cash-coin', 'route' => 'orcamentos.index']),
        ...$item('roteiros.ver', ['label' => 'Roteiro de Viagem', 'icon' => 'bi-signpost-2', 'route' => 'roteiros-viagem.index']),
        ...$item('mapa_relacoes.ver', ['label' => 'Mapa de Relações', 'icon' => 'bi-geo-alt', 'route' => 'mapa-relacoes.index']),
        ...$item('indicadores.ver', ['label' => 'Indicadores Comerciais', 'icon' => 'bi-graph-up', 'route' => 'indicadores-comerciais.index']),
        ...$item('relatorios.ver', ['label' => 'Relatórios', 'icon' => 'bi-bar-chart-line', 'route' => 'atendimentos-relatorios.index']),
        ...$item('mapa_demandas.ver', ['label' => 'Mapa de Demandas', 'icon' => 'bi-geo', 'route' => 'mapa-demandas.index']),
    ];

    $configurador = [
        ...$item('configurador.ver', ['label' => 'Perguntas', 'icon' => 'bi-question-circle', 'route' => 'configurador.perguntas.index']),
        ...$item('configurador.ver', ['label' => 'Modelos', 'icon' => 'bi-diagram-3', 'route' => 'configurador.modelos.index']),
    ];

    $configuracoes = [
        ...$item('tipos_orcamento.ver', ['label' => 'Tipos de Orçamento (CRM)', 'icon' => 'bi-receipt', 'route' => 'crm.tipos-orcamento.index']),
        ...($configurador ? [['label' => 'Configurador de Relatórios', 'icon' => 'bi-sliders', 'children' => $configurador]] : []),
        ...$item('naturezas.ver', ['label' => 'Naturezas de Atendimentos', 'icon' => 'bi-tools', 'route' => 'naturezas-dos-atendimentos.index']),
        ...$item('classificacoes.ver', ['label' => 'Classificações de Cliente', 'icon' => 'bi-award', 'route' => 'classificacoes-cliente.index']),
        ...$item('segmentos.ver', ['label' => 'Segmentos', 'icon' => 'bi-pie-chart', 'route' => 'segmentos.index']),
        ...$item('ocorrencias.ver', ['label' => 'Ocorrências', 'icon' => 'bi-exclamation-triangle', 'route' => 'ocorrencias.index']),
        ...$item('usuarios.ver', ['label' => 'Usuários', 'icon' => 'bi-people', 'route' => 'usuarios.index']),
        // Fixo (não configurável): só Administrador / usuário protegido.
        ...($usuario && $usuario->temAcessoTotal()
            ? [['label' => 'Perfis de Acesso', 'icon' => 'bi-shield-lock', 'route' => 'perfis-acesso.index']]
            : []),
        // Logs de auditoria: escondido do menu por ora (pedido do cliente
        // 2026-09-17) - rota/tela continuam existindo.
    ];

    if ($configuracoes) {
        $menu[] = ['label' => 'Configurações', 'icon' => 'bi-gear', 'children' => $configuracoes];
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
        <a href="{{ route('minha-conta.edit') }}" class="sbadmin-dropdown-item">
            <i class="bi bi-person-gear me-2" aria-hidden="true"></i>Minha conta
        </a>
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
