# sbadmin/dashboard

Componentes Blade reutilizáveis para dashboards administrativos, com
Bootstrap 5, Alpine.js (sem jQuery) e SCSS com tokens de tema centralizados.
Pense neste pacote como um "design system" de admin que você instala em
qualquer projeto Laravel e usa via `<x-sbadmin::... />`.

Este diretório (`packages/sbadmin`) é auto-contido e não depende de nada
específico do projeto de demonstração na raiz deste repositório — pode ser
copiado ou extraído para um pacote Composer independente.

## Requisitos

- PHP >= 8.1
- Laravel >= 10 (`illuminate/support`)
- Vite + `laravel-vite-plugin` no projeto host
- Node >= 18 (para compilar SCSS/JS)

## Instalação em outro projeto Laravel

### 1. Adicione o pacote

**Opção A — desenvolvimento local / monorepo (path repository):**

Copie a pasta `packages/sbadmin` para o seu projeto e adicione ao
`composer.json` do projeto host:

```json
{
    "repositories": [
        { "type": "path", "url": "packages/sbadmin" }
    ],
    "require": {
        "sbadmin/dashboard": "@dev"
    }
}
```

```bash
composer update sbadmin/dashboard
```

**Opção B — via repositório Git próprio:**

Publique este diretório como um repositório Git separado e aponte um
repositório VCS no `composer.json` do projeto host:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/sua-org/sbadmin-dashboard" }
    ],
    "require": {
        "sbadmin/dashboard": "^1.0"
    }
}
```

O `ServiceProvider` (`SbAdmin\Dashboard\SbAdminServiceProvider`) é
auto-descoberto pelo Laravel — nenhum passo manual de registro é necessário.

### 2. Publique os assets fonte (SCSS/JS)

O Vite do projeto host precisa dos arquivos fonte SCSS/JS em `resources/`
para compilar (não é possível apontar o Vite direto para `vendor/`).
Publique-os:

```bash
php artisan vendor:publish --tag=sbadmin-assets
```

Isso copia:

- `packages/sbadmin/resources/sass/sbadmin/*` → `resources/sass/sbadmin/`
- `packages/sbadmin/resources/js/sbadmin/*` → `resources/js/sbadmin/`

Opcionalmente, publique também a config (para customizar o menu/marca) e as
views (para sobrescrever qualquer componente):

```bash
php artisan vendor:publish --tag=sbadmin-config
php artisan vendor:publish --tag=sbadmin-views
```

### 3. Crie os entrypoints do projeto

`resources/sass/app.scss`:

```scss
@import 'sbadmin/app';

// Seus estilos customizados abaixo
```

`resources/js/app.js`:

```js
import 'bootstrap-icons/font/bootstrap-icons.css';
import Alpine from 'alpinejs';
import { registerSbAdmin } from './sbadmin/app';

registerSbAdmin(Alpine);

window.Alpine = Alpine;
Alpine.start();
```

### 4. Instale as dependências npm

```bash
npm install bootstrap sass-embedded alpinejs @alpinejs/collapse bootstrap-icons --save
```

(`chart.js` e `@fontsource/ubuntu-sans` são opcionais — use-os apenas se sua
página de exemplo precisar de gráficos/fonte auto-hospedada, como na demo
deste repositório.)

### 5. Aponte o Vite para os novos entrypoints

`vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/sass/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

```bash
npm run build
```

### 6. Use o layout numa página

```blade
<x-sbadmin::layout title="Dashboard">
    <div class="row g-4">
        <div class="col-sm-6 col-xl-3">
            <x-sbadmin::kpi-card title="Receita" value="R$ 12.345" icon="bi-cash-stack" :change="4.2" />
        </div>
    </div>
</x-sbadmin::layout>
```

Pronto — sidebar, topbar, dark mode e responsividade já funcionam.

## Configuração do menu

Publique e edite `config/sbadmin.php`:

```php
return [
    'brand' => ['name' => 'Minha Empresa', 'logo' => null],
    'menu' => [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'dashboard'],
        ['label' => 'Relatórios', 'icon' => 'bi-bar-chart-line', 'badge' => ['text' => 'Novo', 'class' => 'bg-primary'], 'children' => [
            ['label' => 'Vendas', 'route' => 'reports.sales'],
            ['label' => 'Exportações', 'icon' => 'bi-file-earmark-arrow-down', 'children' => [
                ['label' => 'PDF', 'url' => '#'],
                ['label' => 'Excel', 'url' => '#'],
            ]],
        ]],
    ],
];
```

- `route`: nome de rota nomeada (ignorado com segurança via `Route::has()`
  se a rota não existir — nunca quebra a aplicação).
- `url`: alternativa a `route` para links diretos/externos.
- `badge`: string ou `['text' => ..., 'class' => 'bg-primary']` — renderiza um
  `<span class="badge rounded-pill">` nativo do Bootstrap ao lado do item.
- `children`: array de itens do próximo nível — **profundidade ilimitada**,
  renderizado recursivamente por `<x-sbadmin::sidebar-item>`. Com a sidebar
  expandida, todo submenu abre como accordion inline (`x-collapse`). Com a
  sidebar **recolhida**, o submenu de 1º nível vira um flyout posicionado ao
  lado do ícone (fecha ao clicar fora); níveis mais profundos dentro do
  flyout continuam como accordion normal.
- Também é possível passar um menu diferente por página:
  `<x-sbadmin::layout :menu="$meuMenu">`.

## Referência de componentes

Todos os componentes usam o namespace `x-sbadmin::`.

| Componente | Props principais |
| --- | --- |
| `<x-sbadmin::layout>` | `title`, `menu`, `user` — slots: padrão (conteúdo), `head`, `topbarActions`, `userMenu` |
| `<x-sbadmin::sidebar>` | `menu` (default: `config('sbadmin.menu')`) |
| `<x-sbadmin::topbar>` | `title`, `user` — slot padrão = botões extras antes do dark mode toggle |
| `<x-sbadmin::kpi-card>` | `title`, `value`, `icon`, `change` (número, +/-), `change-label` |
| `<x-sbadmin::table>` | `headers` (array), `paginator` (paginator do Laravel), `count`, `empty-message`, `striped`, `hover` — slot padrão = `<tr>`s |
| `<x-sbadmin::form.input>` | `name`, `label`, `type`, `value`, `help`, `required` |
| `<x-sbadmin::form.select>` | `name`, `label`, `options` (array `valor => label`), `placeholder`, `value`, `required` |
| `<x-sbadmin::form.textarea>` | `name`, `label`, `value`, `rows`, `help`, `required` |
| `<x-sbadmin::form.checkbox>` | `name`, `label`, `value`, `checked`, `help` |
| `<x-sbadmin::alert>` | `type` (`success`\|`error`\|`warning`\|`info`), `dismissible`, `icon` |
| `<x-sbadmin::badge>` | `type` (`success`\|`error`\|`warning`\|`info`\|`neutral`), `pill` |

Os componentes de formulário lêem automaticamente `old()` e o `$errors` bag
do Laravel — basta usar `name="campo"` igual a um input normal e o estado de
erro/validação é aplicado sozinho.

A tabela aceita diretamente o retorno de `Model::paginate()` — os links de
paginação usam a view `sbadmin::pagination` (Bootstrap 5, sem jQuery).

## Design system / customização visual

Edite `resources/sass/sbadmin/_variables.scss` (publicado no passo 2) para
alterar cores, fontes e espaçamentos — todo o resto do template consome
essas variáveis:

```scss
$sbadmin-color-primary: #1E293B;   // slate escuro - sidebar, headers
$sbadmin-color-secondary: #2563EB; // azul - botões, links, item ativo
$sbadmin-bg-light: #F8FAFC;
$sbadmin-bg-dark: #0F172A;
$sbadmin-color-success: #16A34A;
$sbadmin-color-error: #DC2626;
$sbadmin-color-warning: #D97706;
$sbadmin-color-info: #0891B2;
$sbadmin-font-family: 'Ubuntu Sans', ...;
```

### Dark mode

Único tema suportado (sem alternância/toggle): o atributo `data-theme` na tag
`<html>` (e `data-bs-theme`, pro color mode nativo do Bootstrap) já sai fixo
em `dark` via script inline no `<head>` de `components/layout.blade.php`, sem
ler `localStorage`/`prefers-color-scheme`. As cores do tema escuro ficam em
`_theme.scss` como custom properties CSS (`--sbadmin-bg`, `--sbadmin-surface`,
`--sbadmin-text`, etc.) — se você adicionar componentes próprios, consuma
essas variáveis. As variáveis do tema claro continuam declaradas em
`_theme.scss` (não usadas atualmente, mas inofensivas) caso um projeto que
instale o pacote queira reintroduzir a alternância.

## Acessibilidade

- Sidebar e dropdowns usam `aria-expanded`, `aria-controls`, `aria-haspopup`
  e `aria-current="page"` no item ativo.
- Ícones decorativos usam `aria-hidden="true"`; botões apenas com ícone têm
  `aria-label`.
- Campos de formulário associam `<label for>` ao `id` do input e expõem
  erros via `aria-invalid` + `aria-describedby`.
- O off-canvas mobile fecha com `Esc` (dropdowns) e clique fora
  (`@click.outside`).

## Estrutura de pastas do pacote

```
packages/sbadmin/
├── composer.json
├── config/sbadmin.php
├── src/SbAdminServiceProvider.php
└── resources/
    ├── views/
    │   ├── components/          # x-sbadmin::*
    │   └── pagination.blade.php # sbadmin::pagination
    ├── sass/sbadmin/
    │   ├── _variables.scss      # tokens de design
    │   ├── _theme.scss          # custom properties claro/escuro
    │   ├── _layout.scss         # sidebar, topbar, off-canvas
    │   ├── _components.scss     # cards, kpi, tabela, forms, alerts, badges
    │   └── app.scss             # entrypoint (importa Bootstrap + tudo acima)
    └── js/sbadmin/
        ├── theme.js             # get/set/toggle do dark mode
        ├── sidebar.js           # estado de collapse/off-canvas
        └── app.js               # registra Alpine.data('sbAdmin', ...)
```
