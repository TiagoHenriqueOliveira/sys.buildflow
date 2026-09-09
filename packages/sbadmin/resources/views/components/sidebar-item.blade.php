@props([
    'item',
    'depth' => 0,
    'index' => 0,
])
@php
    $hasChildren = ! empty($item['children']);

    $resolveUrl = function (array $i) {
        if (! empty($i['route']) && \Illuminate\Support\Facades\Route::has($i['route'])) {
            return route($i['route']);
        }

        return $i['url'] ?? '#';
    };

    $isActive = function (array $i) {
        return ! empty($i['route'])
            && \Illuminate\Support\Facades\Route::has($i['route'])
            && request()->routeIs($i['route']);
    };

    $active = ! $hasChildren && $isActive($item);

    $hasActiveDescendant = false;
    if ($hasChildren) {
        $flatten = function (array $items) use (&$flatten) {
            $all = [];
            foreach ($items as $child) {
                $all[] = $child;
                $all = array_merge($all, $flatten($child['children'] ?? []));
            }

            return $all;
        };
        $hasActiveDescendant = collect($flatten($item['children']))->contains(fn ($c) => $isActive($c));
    }

    $uid = 'sbadmin-submenu-'.$depth.'-'.$index.'-'.substr(md5(($item['label'] ?? '').'|'.$depth.'|'.$index), 0, 6);
    $paddingLeft = 0.75 + ($depth * 0.75);
    $badge = $item['badge'] ?? null;
@endphp
<li class="sbadmin-nav-item">
    @if($hasChildren)
        {{-- Com a sidebar expandida, o submenu abre como accordion inline
             (x-collapse), crescendo verticalmente dentro da propria sidebar.
             Com a sidebar recolhida (collapsed), TODO submenu - em qualquer
             profundidade - abre como um painel flutuante (flyout) posicionado
             via position:fixed a direita do item clicado (calculado a partir
             do seu getBoundingClientRect, escapando do overflow do <aside>).
             Como cada nivel abre seu proprio flyout a direita do anterior, os
             paineis se encadeiam horizontalmente conforme o usuario navega
             por niveis mais profundos - igual a um menu em cascata.

             O flyout so pode iniciar aberto quando alguem efetivamente clica
             no botao (que calcula flyoutTop/flyoutLeft na hora). Se "open"
             comecasse true so por causa de hasActiveDescendant (ex.: usuario
             carrega uma pagina dentro de "Configuracoes" com a sidebar ja
             recolhida de uma visita anterior), o flyout apareceria fixado em
             top:0/left:0 (os valores padrao, nunca recalculados) cobrindo a
             tela, sem nenhum clique do usuario. Por isso comeca fechado
             sempre que estiver no modo flyout (collapsed && isDesktop); no
             accordion inline (sidebar expandida) isso nao acontece porque ele
             cresce dentro da propria sidebar, entao mantem o auto-abrir. --}}
        <div
            x-data="{ open: (collapsed && isDesktop) ? false : {{ $hasActiveDescendant ? 'true' : 'false' }}, flyoutTop: '0px', flyoutLeft: '0px' }"
            class="sbadmin-nav-group"
            @click.outside="open = false"
        >
            <button
                type="button"
                class="sbadmin-nav-link sbadmin-nav-toggle @if($depth === 0) sbadmin-nav-link--top @endif @if($hasActiveDescendant) is-active-parent @endif"
                style="padding-left: {{ $paddingLeft }}rem"
                @if($depth === 0) data-tooltip="{{ $item['label'] }}" @endif
                @click="
                    open = !open;
                    if (collapsed && isDesktop && open) {
                        const r = $el.getBoundingClientRect();
                        flyoutTop = r.top + 'px';
                        flyoutLeft = (r.right + 8) + 'px';
                    }
                "
                :aria-expanded="open.toString()"
                aria-controls="{{ $uid }}"
            >
                <i class="bi {{ $item['icon'] ?? 'bi-dot' }} sbadmin-nav-icon" aria-hidden="true"></i>
                <span class="sbadmin-nav-label @if($depth === 0) sbadmin-hide-collapsed @endif">{{ $item['label'] }}</span>
                @if($badge)
                    <span class="badge {{ is_array($badge) ? ($badge['class'] ?? 'bg-primary') : 'bg-primary' }} rounded-pill sbadmin-nav-badge @if($depth === 0) sbadmin-hide-collapsed @endif">
                        {{ is_array($badge) ? ($badge['text'] ?? '') : $badge }}
                    </span>
                @endif
                {{-- bi-chevron-right: aponta pra direita quando fechado ou em modo
                     flyout (indica "abre um painel a direita"); gira 90deg pra
                     baixo apenas quando aberto E a sidebar esta expandida
                     (indicando o accordion inline crescendo abaixo). --}}
                <i
                    class="bi bi-chevron-right sbadmin-nav-chevron @if($depth === 0) sbadmin-hide-collapsed @endif"
                    :class="{ 'is-rotated': open && !collapsed }"
                    aria-hidden="true"
                ></i>
            </button>

            <ul
                class="sbadmin-submenu"
                id="{{ $uid }}"
                x-show="open"
                x-collapse
                x-cloak
                :class="{ 'sbadmin-submenu--flyout': collapsed && isDesktop }"
                :style="(collapsed && isDesktop) ? { position: 'fixed', top: flyoutTop, left: flyoutLeft } : {}"
            >
                @foreach($item['children'] as $i => $child)
                    <x-sbadmin::sidebar-item :item="$child" :depth="$depth + 1" :index="$i" />
                @endforeach
            </ul>
        </div>
    @else
        <a
            href="{{ $resolveUrl($item) }}"
            class="sbadmin-nav-link @if($depth === 0) sbadmin-nav-link--top @endif @if($active) is-active @endif"
            style="padding-left: {{ $paddingLeft }}rem"
            @if($depth === 0) data-tooltip="{{ $item['label'] }}" @endif
            @if($active) aria-current="page" @endif
        >
            <i class="bi {{ $item['icon'] ?? 'bi-dot' }} sbadmin-nav-icon" aria-hidden="true"></i>
            <span class="sbadmin-nav-label @if($depth === 0) sbadmin-hide-collapsed @endif">{{ $item['label'] }}</span>
            @if($badge)
                <span class="badge {{ is_array($badge) ? ($badge['class'] ?? 'bg-primary') : 'bg-primary' }} rounded-pill sbadmin-nav-badge @if($depth === 0) sbadmin-hide-collapsed @endif">
                    {{ is_array($badge) ? ($badge['text'] ?? '') : $badge }}
                </span>
            @endif
        </a>
    @endif
</li>
