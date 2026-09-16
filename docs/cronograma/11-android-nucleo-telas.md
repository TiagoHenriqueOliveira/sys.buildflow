# Sessão Android — Bloco 1 (Núcleo) · Etapa 1: Telas + Endpoints

Ver [00-indice.md](00-indice.md). Pré-requisito: [10-android-preparacao.md](10-android-preparacao.md).

## Escopo: NC01 (Cliente) + BF01/02/03 (mobile) — **com os endpoints REST novos**

Repositórios envolvidos: `app_buildflow_fae` (telas) **e** `sys-buildflow-fae` (endpoints `fae/v1`).

## Estado atual (ponto de partida)

- `go_router` (`lib/app/router.dart`) só tem rotas de login/home/relatórios. Nenhuma rota de Cliente existe.
- `routes/api.php` (grupo `fae/v1`, `sys-buildflow-fae`) não tem nenhuma rota de cliente — só o fluxo fixo de atendimento/relatório herdado do MCL.
- Referência de UX pronta e aprovada pelo cliente: `resources/views/clientes/form.blade.php` (`sys-buildflow-fae`) — usar como espelho funcional (não visual) de campos e regras.

## Parte A — Endpoints novos em `sys-buildflow-fae` (`routes/api.php`, grupo `fae/v1`)

Criar um `Api\Fae\ClientesController` (mesmo padrão dos outros controllers `Api\Fae\*` já existentes) com:

- `GET /fae/v1/clientes` — lista paginada, com busca por nome (mesmo filtro do `ClientesController::index()` web).
- `GET /fae/v1/clientes/{id}` — detalhe, incluindo `contatos`, `equipamentos` e `localizacoes` (relações já existentes no model `Cliente`).
- `POST /fae/v1/clientes` — criação. **Reaproveitar `App\Http\Requests\ClienteRequest` e `App\Repositories\ClienteRepository::create()`** (não duplicar validação/regra — são os mesmos usados pelo `ClientesController` web). Campos: `cli_nome`, `cli_cnpj` (só dígitos), `cli_cidade`, `cli_uf`, `cli_segmento`, `cli_telefone`, `cli_email`, `cli_classificacao_id`, `cli_caso_sucesso`(+`_descricao`), arrays `contatos[]` (`nome`,`cargo`,`telefone`,`email`,`tipo`: 0=Técnico/1=Comercial), `equipamentos[]` (`descricao`), `localizacoes[]` (`descricao`,`link_mapa`,opcionalmente `latitude`/`longitude`), `cli_latitude`/`cli_longitude`/`cli_link_mapa` (localização principal).
- `PUT /fae/v1/clientes/{id}` — edição, mesmo shape, reaproveitando `ClienteRepository::update()`.
- `GET /fae/v1/clientes/autocomplete?term=` — mesmo propósito do `ClientesController::autoComplete()` web (usado hoje no cadastro de Atendimento; o app mobile também precisa disso pra vincular cliente a um atendimento).

Middleware: `auth:sanctum`, restringir create/update a quem tem `nivel_acesso` compatível com o middleware `comercial` do web (ver `app/Http/Middleware` — replicar a mesma checagem, não inventar uma nova regra). Leitura (`index`/`show`/`autocomplete`) fica liberada pra qualquer usuário autenticado, igual ao padrão web (técnico também precisa achar cliente pra abrir atendimento).

**Ao terminar a Parte A:** rodar a revisão de segurança do `CLAUDE.md` (rotas sem `auth`/`sanctum`) e criar `tests/Feature/Api/ClientesApiFaeTest.php` cobrindo list/show/store/update/autocomplete — mesmo padrão dos testes Feature já existentes no projeto.

## Parte B — Telas no `app_buildflow_fae`

- **Listagem de clientes** (nova rota `/clientes` no `go_router`) — busca por nome, navega pro detalhe/edição.
- **Formulário de cliente** (criar/editar), espelhando as 4 abas do Web:
  - **Dados**: nome, CNPJ (com máscara), cidade, UF, segmento, telefone, e-mail, classificação.
  - **Contatos**: lista repetível (nome, cargo, telefone, e-mail, tipo Técnico/Comercial).
  - **Geolocalização**: botão "Usar minha localização" (`geolocator`, grava lat/lng ocultos) + campo de link do Google Maps colado + botão abrir (via `url_launcher`). **Sem busca/picker de mapa** — é a decisão final do Web (BF_v1.8.2), replicar exatamente.
  - **Histórico**: lista repetível de equipamentos (só descrição).
- **BF01**: campo/botão de geolocalização visível só pra usuário com perfil Comercial/Assistência/Administrador (usar os getters da sessão 10).
- **BF02**: usar os getters de perfil (sessão 10) pra mostrar/esconder o item de menu "Clientes" — hoje não existe menu nenhum de CRM no `home_screen.dart`, este é o primeiro item.
- **BF03**: ao abrir um atendimento, exibir os dados do cliente vinculado (nome, endereço) sem navegação extra — checar o que a tela de atendimento já mostra hoje e complementar com os campos novos que vêm do endpoint de Cliente.

## Não faz parte desta sessão
Persistência local/cache offline do cadastro de cliente (fila de sync) — isso é a sessão 12. Aqui a tela consome a API diretamente (online).

## Critérios de aceite a verificar
- Cadastrar/editar um cliente pelo app grava de verdade no banco `buildflow_fae` (mesma tabela que o Web usa) — validar abrindo o mesmo cliente no Web depois.
- Usuário técnico não vê o menu "Clientes"; usuário comercial vê.
- Autocomplete de cliente funciona ao vincular um atendimento novo.

## Próxima sessão
[13-android-configurador-telas.md](13-android-configurador-telas.md) (a numeração pula a 12 de propósito — persistência do Núcleo fica pra depois, ver [00-indice.md](00-indice.md))