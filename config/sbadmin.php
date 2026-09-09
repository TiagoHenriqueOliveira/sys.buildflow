<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identidade visual
    |--------------------------------------------------------------------------
    */
    'brand' => [
        'name' => 'Buildflow',
        'logo' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu da sidebar
    |--------------------------------------------------------------------------
    |
    | Cada item aceita: label, icon (classe bootstrap-icons), route (nome de
    | rota nomeada) ou url (link direto), badge (string ou ['text'=>, 'class'=>
    | 'bg-*']) e children (submenu - profundidade ilimitada, renderizado de
    | forma recursiva). Rotas inexistentes sao ignoradas silenciosamente
    | (Route::has). Com a sidebar recolhida (collapsed), o submenu de 1o
    | nivel vira um flyout; niveis mais profundos continuam em accordion.
    |
    | Publique este arquivo (php artisan vendor:publish --tag=sbadmin-config)
    | para sobrescrever com o menu real da sua aplicacao.
    |
    | Este array serve apenas de fallback estatico (usado se algum ponto do
    | app renderizar <x-sbadmin::sidebar> sem vir do layout do projeto). O
    | menu real, com os itens visiveis apenas para administradores
    | (Clientes e Configuracoes, equivalente ao antigo `@if(user_nivel_acesso
    | === 0)`), e montado dinamicamente em
    | resources/views/components/layout.blade.php e passado via prop
    | `:menu` — ver comentario la para o motivo dessa escolha.
    |
    */
    'menu' => [
        [
            'label' => 'Dashboard',
            'icon' => 'bi-speedometer2',
            'route' => 'dashboard',
        ],
        [
            'label' => 'Atendimentos',
            'icon' => 'bi-headset',
            'route' => 'atendimentos.index',
        ],
        [
            'label' => 'Relatórios',
            'icon' => 'bi-bar-chart-line',
            'route' => 'atendimentos-relatorios.index',
        ],
    ],

];
