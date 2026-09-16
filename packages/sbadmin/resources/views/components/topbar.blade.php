@props([
    'title' => null,
    'user' => null,
])
@php
    $authUser = $user ?? (auth()->check() ? auth()->user() : null);
    $userName = is_array($authUser) ? ($authUser['name'] ?? null) : ($authUser->name ?? null);
    $userEmail = is_array($authUser) ? ($authUser['email'] ?? null) : ($authUser->email ?? null);
    $userName = $userName ?: 'Visitante';
@endphp
<header class="sbadmin-topbar">
    <div class="sbadmin-topbar-start">
        <button
            type="button"
            class="sbadmin-icon-btn d-lg-none"
            @click="mobileOpen = true"
            aria-label="Abrir menu de navegacao"
            aria-controls="sbadmin-sidebar"
            :aria-expanded="mobileOpen.toString()"
        >
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        @if($title)
            <h1 class="sbadmin-page-title">{{ $title }}</h1>
        @endif
    </div>

    <div class="sbadmin-topbar-end">
        {{ $slot }}

        {{-- Pedido do cliente (2026-09-16): Sistema de Notificacoes (alerta de
             recontato de cliente + alerta de comentario de orcamento, ver
             App\Models\Notificacao). Estado (notificacoes/notificacoesNaoLidas)
             e as funcoes de carregar/marcar como lida vivem no componente raiz
             sbAdmin() (resources/js/sbadmin/app.js), carregado uma vez por
             pagina. --}}
        <div class="sbadmin-dropdown" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
            <button
                type="button"
                class="sbadmin-icon-btn"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-haspopup="true"
                aria-label="Ver notificacoes"
            >
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="sbadmin-notif-dot" x-show="notificacoesNaoLidas > 0" x-cloak aria-hidden="true"></span>
            </button>
            <div
                class="sbadmin-dropdown-menu sbadmin-dropdown-menu-end"
                x-show="open"
                x-transition
                x-cloak
                role="menu"
                aria-label="Notificacoes"
            >
                <div class="sbadmin-dropdown-header">Notificações</div>
                <div class="sbadmin-dropdown-empty" x-show="!notificacoes.length">Nenhuma notificação por aqui ainda.</div>
                <template x-for="notif in notificacoes" :key="notif.id">
                    <a
                        :href="notif.link || '#'"
                        class="sbadmin-notif-item"
                        :class="{ 'sbadmin-notif-item-unread': !notif.lida }"
                        @click="marcarNotificacaoLida(notif)"
                        role="menuitem"
                    >
                        <span class="sbadmin-notif-item-titulo" x-text="notif.titulo"></span>
                        <span class="sbadmin-notif-item-mensagem" x-text="notif.mensagem"></span>
                    </a>
                </template>
            </div>
        </div>

        <div class="sbadmin-dropdown" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
            <button
                type="button"
                class="sbadmin-user-btn"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-haspopup="true"
                aria-label="Menu do usuario"
            >
                <span class="sbadmin-user-avatar" aria-hidden="true">{{ str($userName)->substr(0, 1)->upper() }}</span>
                <span class="sbadmin-user-name d-none d-md-inline">{{ $userName }}</span>
                <i class="bi bi-chevron-down sbadmin-user-caret" aria-hidden="true"></i>
            </button>
            <div
                class="sbadmin-dropdown-menu sbadmin-dropdown-menu-end"
                x-show="open"
                x-transition
                x-cloak
                role="menu"
                aria-label="Menu do usuario"
            >
                @if($userEmail)
                    <div class="sbadmin-dropdown-header">{{ $userEmail }}</div>
                @endif
                {{ $userMenu ?? '' }}
            </div>
        </div>
    </div>
</header>
