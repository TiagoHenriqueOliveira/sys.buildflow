# Sessão Android — Bloco 1 (Núcleo) · Etapa 1: Telas

Ver [00-indice.md](00-indice.md). Pré-requisito: [10-android-preparacao.md](10-android-preparacao.md).

## Escopo: BF01, BF02, BF03 (mobile)

## Estado atual (ponto de partida)

- `go_router` (`lib/app/router.dart`) só tem 5 rotas hoje: `/splash`, `/login`, `/home`, `/relatorios`, `/relatorios/:id`, dentro de um único shell (`lib/app/scaffold_with_nav.dart`). Nenhuma rota comercial existe.
- Nenhum gating por perfil no código hoje (busca por `comercial|perfil|role|profile` em `lib/` não retorna nada) — a Sessão 10 adicionou o campo no modelo, aqui entra o uso dele na UI.
- `lib/features/home/home_screen.dart` é o ponto de entrada do menu principal.

## Entregáveis

- **BF01**: tela/campo de captura de geolocalização no cadastro/edição de cliente (usa `geolocator` adicionado na Sessão 10) — visível só quando o usuário logado tem perfil comercial. Botão de abrir a localização no Google Maps.
- **BF02**: ajustar `home_screen.dart` (e o `ScaffoldWithNav`) para exibir/esconder itens de menu comerciais conforme o perfil do usuário logado (via `AuthProvider`).
- **BF03**: confirmar que a tela de relatório/atendimento (`relatorios_screen.dart`, `relatorio_form_screen.dart`) exibe os dados do cliente vinculado sem navegação extra — grande parte já deve existir, validar contra os campos novos de NC01 vindos do backend.

## Não faz parte desta sessão

Persistência local/sync dos novos campos — isso é a Sessão 12. Aqui a tela pode consumir a API diretamente (online) sem se preocupar ainda com o cenário offline completo.

## Critérios de aceite a verificar

- Usuário com perfil comercial vê o botão de geolocalização no cadastro de cliente; usuário sem o perfil, não vê.
- Menu do app mostra/esconde itens comerciais conforme o perfil.

## Próxima sessão

[12-android-nucleo-persistencia.md](12-android-nucleo-persistencia.md)
