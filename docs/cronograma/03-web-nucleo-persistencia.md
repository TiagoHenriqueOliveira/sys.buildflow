# Sessão Web — Bloco 1 (Núcleo) · Etapa 2: Persistência/Regras de Negócio

Ver [00-indice.md](00-indice.md). Pré-requisito: Telas do Núcleo concluídas (feito, ver [02-web-nucleo-telas.md](02-web-nucleo-telas.md)).

**Reescrito em 16/09/2026** (auditoria direta do código, `BF_v1.8.5`) e **revisado em 16/09/2026** depois de confirmação do usuário sobre os dois pontos que estavam em aberto — ver "Decisões confirmadas" abaixo. Não confiar em versão anterior deste documento.

## Escopo: acesso ao Cliente por perfil, histórico consolidado, integridade de CNPJ, Sistema de Notificações (novo) + alerta de recontato

**BF01/BF02/BF03 já estão completos e não precisam de trabalho nesta sessão** — geolocalização, os 5 níveis de `NivelAcesso` e a exibição de cliente no atendimento já funcionam de ponta a ponta.

## Decisões confirmadas com o usuário (16/09/2026)

1. **Não existe pré-cadastro com aprovação.** Cliente cadastrado já nasce ativo/utilizável — não tem status Pendente, não tem fluxo de aprovação. **Remover completamente essa ideia do escopo** (o item 1 e 2 da versão anterior deste arquivo não existem mais).
2. **Quem pode cadastrar/editar Cliente**: todos os perfis **exceto Técnico** (`Administrador`, `Comercial`, `Assistencia`, `Vendedor`). Hoje o middleware `SomenteComercialOuAdministrador` (`app/Http/Middleware/SomenteComercialOuAdministrador.php`) só libera Administrador/Comercial — precisa abrir pra Assistência/Vendedor **só nas rotas de Cliente**, sem mudar o acesso das outras rotas do grupo `comercial` (Orçamentos, Roteiro de Viagem, Mapa de Relações, Indicadores continuam Comercial/Admin apenas — o usuário não pediu mudança ali, não expandir por conta própria).
3. **Alerta de recontato é um Sistema de Notificações de verdade**, não um badge calculado: no Android, push nativa mesmo com o app fechado; no Web, ícone de sino com lista de notificações. **Não existe nenhuma infraestrutura de notificação hoje** (migration/model/UI, zero). Esta sessão constrói a parte **Web** (modelo genérico + sino + lista); a entrega **Android** (push via FCM/APNs) fica fora desta sessão — ver nota no final.
4. **Achado durante a auditoria, bônus direto desta decisão**: CRM03 (comentário de orçamento com "alertar usuário") já salva `orc_com_alerta_usuario_id`, mas **nunca notifica ninguém de verdade** — só aparece como badge no próprio comentário, se o usuário abrir aquele orçamento por conta própria. O Sistema de Notificações desta sessão deve ser genérico o bastante pra também cobrir esse alerta (mesma infra, dois gatilhos).

## Estado atual (auditado direto no código)

- `cli_segmento`, `cli_classificacao_id`, `cli_dias_alerta_recontato`, `cli_vendedor_id` já existem como colunas e já são validados/salvos (`ClienteRequest`/`ClienteRepository`) — **não recriar**.
- `SomenteComercialOuAdministrador` bloqueia por `user_nivel_acesso !== Administrador && !== Comercial` — é o middleware `comercial` usado em `Route::middleware('comercial')->group(...)` (`routes/web.php`), que hoje engloba Clientes **e** todo o resto do CRM (Orçamentos/Roteiro/Mapa/Indicadores) no mesmo grupo.
- A aba "Histórico" do cadastro de cliente (`clientes/form.blade.php`) mostra só o texto placeholder "Histórico de atendimentos e orçamentos deste cliente — disponível a partir da sessão de persistência do Núcleo/CRM."
- `clientes.cli_cnpj` (`string(14)`) não tem índice único no banco — só validação de aplicação.
- Zero infraestrutura de notificação (`grep -ri notifica` no projeto não retorna model/migration nenhuma).

## Entregáveis

### 1. Acesso ao Cliente aberto a todos exceto Técnico
- Separar a rota de Clientes do grupo `comercial` compartilhado. Criar `App\Http\Middleware\SomenteNaoTecnico` (bloqueia só `NivelAcesso::Tecnico`, libera os outros 4) e registrar (`app/Http/Kernel.php`, alias `nao-tecnico` ou nome equivalente).
- `routes/web.php`: mover `Route::resource('clientes', ClientesController::class)->except(['show','destroy'])` para fora do `Route::middleware('comercial')->group(...)` atual, envolvendo só ela com o middleware novo. **Orçamentos/Roteiro/Mapa/Indicadores continuam exatamente como estão, dentro do grupo `comercial`.**
- Conferir se algo na view (`clientes/form.blade.php`, `$podeGeolocalizar`) já assumia Assistência como já tendo acesso — ajustar se precisar, mas sem duplicar a regra (ela já lista `Administrador/Comercial/Assistencia`, falta só `Vendedor` — conferir e incluir se fizer sentido pra geo também, ou perguntar se geo continua restrita).

### 2. Sistema de Notificações (Web) — novo, genérico
- Migration + model `Notificacao`: `notif_id`, `notif_usuario_id` (destinatário), `notif_tipo` (string curta: `recontato_cliente`, `comentario_orcamento`, ...), `notif_titulo`, `notif_mensagem`, `notif_link` (rota pra onde clicar leva — ex.: `route('clientes.edit', $id)`), `notif_lida` (bool, default false), `notif_criado_em`.
- Sino no layout (`packages/sbadmin` — checar se o topbar já tem um slot pronto pra isso ou se precisa de um componente novo `<x-sbadmin::notificacoes-sino>`), com contador de não lidas e lista suspensa (as 10 mais recentes, com link pra "ver todas" se quiser uma tela própria depois).
- Endpoints: `GET /notificacoes` (lista/contagem, JSON pra alimentar o sino via fetch) e `POST /notificacoes/{id}/marcar-lida`.
- Retrofit do CRM03: `OrcamentosController::storeComentario()` passa a criar uma `Notificacao` pro `orc_com_alerta_usuario_id` quando informado, além de continuar salvando o campo como já faz.

### 3. Alerta de recontato (usa o Sistema de Notificações acima)
- Comando agendado (`php artisan clientes:verificar-recontato`, via `app/Console/Kernel.php`, `->daily()`): para cada cliente ativo com `cli_dias_alerta_recontato` preenchido, calcular dias desde o último atendimento/orçamento (reaproveitar a mesma lógica de "histórico consolidado" do item 5) e, se atrasado, criar uma `Notificacao` pro `cli_vendedor_id` — **evitar duplicar notificação todo dia** (checar se já existe uma não lida do mesmo tipo pra esse cliente antes de criar outra; ex.: só reabre depois que o vendedor marcar como lida, ou um cooldown de N dias — decidir o critério mais simples e documentar no código).

### 4. Histórico consolidado
- Método novo (`Cliente::historico()` ou um `ClienteHistoricoService`) que une `atendimentos` + `atendimentos_relatorios` + `orcamentos` do cliente, ordenado por data desc. 3 queries simples + `merge()`/`sortByDesc()` em coleção resolve, dado o volume esperado.
- Substituir o texto placeholder na aba Histórico por essa lista.

### 5. Integridade de CNPJ (RNF04)
- Migration: `$table->unique('cli_cnpj')` em `clientes`. Simples, aditiva, sem risco.

## ⚠️ Fora desta sessão — decisão futura

**Push notification no Android (FCM/APNs)** — exige projeto Firebase configurado, SDK no app Flutter, endpoint de registro de device token e o disparo real da push a partir da criação de uma `Notificacao`. É infraestrutura própria, maior que um item deste bloco — vira um item explícito quando a trilha Android (`docs/cronograma/1x-android-*`) for retomada; o model `Notificacao` desta sessão já nasce pronto pra alimentar isso depois (só falta o transporte).

## Testes

Criar/estender `tests/Feature/NucleoPersistenciaFaeTest.php`:
- Usuário Assistência/Vendedor consegue criar/editar cliente; usuário Técnico recebe 403.
- Usuário Assistência/Vendedor **não** acessa Orçamentos/Roteiro/Mapa/Indicadores (regra antiga preservada).
- Criar comentário de orçamento com `orc_com_alerta_usuario_id` gera uma `Notificacao` pro usuário certo.
- Comando de recontato cria notificação pro vendedor quando o cliente está atrasado, e não duplica em execuções seguidas antes de ser lida.
- Marcar notificação como lida reflete no contador do sino.
- Histórico consolidado retorna atendimentos + relatórios + orçamentos do cliente, em ordem cronológica.
- Cadastrar dois clientes com o mesmo CNPJ é rejeitado a nível de banco (não só de validação).

## Ao finalizar

- Bump de versão **MINOR** (`.env` `APP_VERSION`, convenção `BF_vMAJOR.MINOR.PATCH`).
- Revisão de segurança: middleware novo aplicado só onde deveria (Clientes), rotas de notificação exigem `auth` e só retornam notificação do próprio usuário autenticado (nunca de outro `notif_usuario_id`).

## Próxima sessão

[05-web-configurador-persistencia.md](05-web-configurador-persistencia.md)