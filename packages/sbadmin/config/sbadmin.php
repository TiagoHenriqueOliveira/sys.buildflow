<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identidade visual
    |--------------------------------------------------------------------------
    */
    'brand' => [
        'name' => env('APP_NAME', 'SB Admin'),
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
    */
    'menu' => [
        [
            'label' => 'Dashboard',
            'icon' => 'bi-speedometer2',
            'route' => 'dashboard',
        ],
        [
            'label' => 'Relatorios',
            'icon' => 'bi-bar-chart-line',
            'badge' => ['text' => 'Novo', 'class' => 'bg-primary'],
            'children' => [
                ['label' => 'Vendas', 'route' => 'reports.sales'],
                ['label' => 'Financeiro', 'route' => 'reports.finance'],
                [
                    'label' => 'Exportacoes',
                    'icon' => 'bi-file-earmark-arrow-down',
                    'children' => [
                        ['label' => 'PDF', 'url' => '#'],
                        ['label' => 'Excel', 'url' => '#'],
                    ],
                ],
            ],
        ],
        [
            'label' => 'Usuarios',
            'icon' => 'bi-people',
            'children' => [
                ['label' => 'Listar', 'route' => 'users.index'],
                ['label' => 'Perfis de acesso', 'route' => 'users.roles'],
            ],
        ],
        [
            'label' => 'Configuracoes',
            'icon' => 'bi-gear',
            'route' => 'settings.index',
        ],
    ],

];
