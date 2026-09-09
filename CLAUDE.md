# FAÉ Bioenergia — Buildflow (Atendimentos e Relatórios) + CRM Comercial

## Contexto do projeto

Esta é a pasta/branch (`feature/fae`) dedicada ao cliente **FAÉ Bioenergia**, dentro do mesmo repositório multi-cliente do Buildflow (`sys.buildflow`, remoto `https://github.com/TiagoHenriqueOliveira/sys.buildflow.git`). Cada cliente tem sua própria branch de longa duração e seu próprio banco de dados — nunca aplicar uma migration desta branch em outro banco, nem supor que o schema de outro cliente (ex.: MCL Vale) é igual ao daqui.

Esta pasta é um **git worktree** apontando para `feature/fae`, que foi forkada de `feature/mcl` (o cliente mais maduro hoje) e ainda está idêntica a ela — nenhum commit específico da FAÉ até este setup inicial. A pasta irmã `C:\Apache24\htdocs\sys.buildflow` é o worktree principal do mesmo repositório, sempre em `feature/mcl` — **nunca fazer checkout de outra branch nela nem aqui**; se precisar trabalhar em outro cliente, é outra pasta/worktree.

Este projeto adiciona o módulo **CRM Comercial** ao Buildflow já existente (que hoje só cobre atendimentos/relatórios de assistência técnica), unificando cadastro de clientes, um configurador de perguntas/modelos reutilizável (usado tanto em relatórios de atendimento quanto em orçamentos comerciais), e as demais funções descritas na especificação técnica do cliente.

**Referência funcional completa:** `Especificações Técnicas v2.pdf` (3io Digital, Revisão 2, 08/09/2026), em `C:\Users\tiago\OneDrive\3io Digital - Projetos\Buildflow - FAE\`. Organizado em três grupos: Núcleo compartilhado (NC), Buildflow (BF) e CRM Comercial (CRM), mais Requisitos Não Funcionais (RNF). **Os nomes de tabela/coluna citados na especificação são propostas de análise, não o schema real** — sempre validar contra as migrations existentes antes de implementar algo novo.

## Stack

- **PHP 8.4** (mesma instalação do Apache — já disponível na máquina, sem necessidade de trocar o módulo do Apache)
- **Laravel 13.x** — upgrade feito a partir do Laravel 10 original herdado do `feature/mcl` (pedido explícito do cliente/usuário: rodar na última versão do Laravel nesta branch, mesmo divergindo da `feature/mcl`, que continua em Laravel 10). Ao portar código do MCL pra cá, checar breaking changes de config/métodos depreciados entre as versões antes de colar direto — **não** adotar o novo esqueleto de app (bootstrap/app.php único sem Kernel.php); a estrutura antiga (`app/Http/Kernel.php`, `app/Exceptions/Handler.php`) foi mantida no upgrade.
- MySQL, banco `buildflow_fae` (local), banco de teste `buildflow_test` (compartilhado com o setup padrão do projeto — ver `.env.testing`)
- Frontend: Blade + Vite + jQuery removido nesta branch — ver seção "Template visual" abaixo
- App mobile: Flutter, projeto irmão em `C:\Users\tiago\StudioProjects\app_buildflow_fae` (ver o `CLAUDE.md` de lá)

## Ambiente local (Apache)

- Vhost por domínio: `http://sys-buildflow-fae.local` (porta 80) — `DocumentRoot` em `public/`, configurado em `C:\Apache24\conf\extra\httpd-vhosts.conf`
- Vhost por IP (celular físico / testes sem DNS local): porta **8083**
- `.env` local aponta `APP_URL=https://sys-buildflow-fae.local` e `DB_DATABASE=buildflow_fae` — **não copiar `.env` do `sys.buildflow` sem ajustar esses dois campos** (senão aponta pro banco/domínio do MCL)
- `MOBILE_APK_URL` usa `192.168.1.12:8083` (a porta de IP desta branch, diferente da 8080 do MCL) e `MOBILE_APK_OBRIGATORIA=false` até existir um `.apk` da FAÉ publicado de fato em `storage/app/apks/` — só virar `true` depois de rodar `tool/build_publish_apk.ps1` (ou equivalente) no app.buildflow, senão o app entra num loop de "atualização obrigatória" sem ter o arquivo pra baixar

## Padrão multi-cliente (replicar trocando `Mcl` → `Fae`)

O `feature/mcl` já estabeleceu o padrão de como isolar cada cliente dentro do mesmo código-base:
- Controllers/Requests namespaced: `App\Http\Controllers\Api\Fae\*`, `App\Http\Requests\Fae\*`
- Rotas prefixadas em `routes/api.php`: `Route::prefix('fae/v1')->group(...)`
- Migrations versionadas por branch, convenção padrão Laravel (`database/migrations/YYYY_MM_DD_HHMMSS_*.php`), sem subpasta por cliente — cada branch só acrescenta as suas
- Docs/testes sufixados: `docs/api-fae.md`, `docs/postman-fae.json`, `tests/Feature/*Fae*Test.php`

## Template visual — pacote `sbadmin/dashboard`

O `sys.buildflow`/`feature/mcl` usa o template legado **SB Admin 2** (jQuery + DataTables + Bootstrap 4, arquivos estáticos em `public/css`/`public/js`). **Esta branch adota, por pedido do cliente/usuário, um pacote Composer autoral diferente: `sbadmin/dashboard`** (fonte em `C:\Apache24\htdocs\sb-admin\packages\sbadmin`, copiado pra cá em `packages/sbadmin`) — Bootstrap 5 + Alpine.js, **sem jQuery**, com componentes Blade próprios (`<x-sbadmin::layout>`, `<x-sbadmin::table>` com paginação nativa do Laravel em vez de DataTables, `<x-sbadmin::form.*>`, dark mode) e tokens de design centralizados em `resources/sass/sbadmin/_variables.scss` (cores, fonte Poppins, espaçamento). Ver `packages/sbadmin/README.md` pra referência completa de componentes e customização do menu (`config/sbadmin.php`).

**Não copiar telas prontas do `feature/mcl` sem adaptar** — lá usam DataTables + modal Bootstrap 4; aqui o padrão é `<x-sbadmin::table>` (retorno de `Model::paginate()`) + `<x-sbadmin::form.*>`. Funcionalidades de exportação Excel/PDF e busca/ordenação client-side que a DataTables oferecia precisam ser reavaliadas tela a tela (reimplementar no backend ou descartar nesta fase).

## Mapa do escopo (especificação v2)

**Núcleo compartilhado:** NC01 cadastro unificado de clientes (pré-cadastro comercial com aprovação, contatos com tipo Técnico/Comercial e visibilidade restrita), NC02 configurador de perguntas/modelos reutilizável com revisão versionada, NC03 anexo de imagem por pergunta com comentário, NC04 anexos gerais do relatório.

**Buildflow:** BF01 geolocalização (só perfil comercial), BF02 perfil de acesso comercial, BF03 exibição de cliente no atendimento, BF04 vínculo modelo↔tipo de atendimento, BF05 formulário dinâmico mobile, BF06 PDF (só perguntas respondidas), BF07 envio com comprovante hash não falsificável, BF08 mapa de demandas, BF09 checklist de peças levadas/trocadas, BF10 aprovação interna por supervisor (Pendente/Aprovado/Revisar — **sem** status "Reprovado"), BF11 observações internas (não aparecem no PDF assinado), BF12 contador de tempo de atendimento (**pendente de definição do cliente**).

**CRM Comercial:** CRM01 vínculo modelo↔tipo de orçamento (Genérico, Sistema de Deságue de Lodo, ETE Nova Residencial/Industrial, ETE Melhoria Industrial), CRM02 nível/prazo automático do orçamento, CRM03 comentários com alerta opcional, CRM04 indicação conjunta entre vendedores (sem cálculo de comissão), CRM05/06 roteiro de viagem saída/retorno, CRM07 relações de clientes no mapa (com casos de sucesso), CRM08 indicadores comerciais, CRM09 telas comerciais no app mobile.

**Fora de escopo nesta fase:** integração com CRM de terceiros, cálculo/divisão de comissão, tipos de resposta além dos 3 do Configurador (Imagem/Texto+Imagem ficou registrado como evolução futura), exportação de rota/trajeto no mapa, linha do tempo detalhada de orçamento (fica para os indicadores, CRM08, fase futura).

## Pendências aguardando retorno do cliente (prazo 23/09/2026)

**Não implementar suposições sobre estes pontos antes da resposta do cliente:**
1. Segmento do cliente (NC01) — lista pré-cadastrada fixa ou texto livre; se lista, quais opções.
2. Cadastro de cliente (NC01) — lista completa de campos, com base na tela do ERP SINPROD.
3. Classificação do cliente (NC01) — opções da lista (ex.: A/B/C ou Ativo/Prospect/Inativo).
4. Alerta de recontato (NC01) — regra de período de inatividade.
5. Contador de tempo de atendimento (BF12) — se entra nesta fase, quem finaliza a contagem e como tratar assinatura obtida dias depois.
6. Aprovação de relatório (BF10) — histórico completo de mudanças de status ou só o último.
7. Configurador (RNF03) — se o cliente quer priorizar o tipo de resposta "Imagem"/"Texto + Imagem" como evolução futura.
8. Indicadores do CRM (CRM08) — confirmação de que a linha do tempo de orçamento fica pra fase futura.

## Controle de versão e commits

- Repositório **compartilhado** com outros clientes do Buildflow — trabalhar sempre em `feature/fae` nesta pasta; nunca fazer checkout de outra branch aqui.
- Commits seguem o padrão `BF_vMAJOR.MINOR.PATCH`:
  - **MINOR** — ao fechar uma funcionalidade completa (ex.: um item NC/BF/CRM inteiro da especificação)
  - **PATCH** — qualquer outro ajuste (correção, pequeno refino, commit intermediário)
  - **MAJOR** — reservado para breaking change; nunca subir sem confirmação explícita do usuário
- RNF07 (da especificação): toda alteração de schema é uma migration versionada específica desta branch — nunca aplicada num banco compartilhado com outro cliente.

## Testes

- Suíte de testes já existe (herdada do `feature/mcl`), `tests/Feature/*Mcl*Test.php` — baseline pré-upgrade do Laravel: 6 falhas pré-existentes em `AtendimentoRelatorioTest` (não relacionadas ao framework, não corrigir sem pedido explícito), 47 passando. Ao implementar um item NC/BF/CRM novo da FAÉ, criar o teste correspondente (`*Fae*Test.php`) cobrindo os critérios de aceite já listados na especificação.
