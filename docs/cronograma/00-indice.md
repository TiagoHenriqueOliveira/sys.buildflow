# Índice — Cronograma FAÉ Bioenergia (Buildflow + CRM Comercial)

Ponto de entrada para qualquer sessão de desenvolvimento deste projeto. Se você é uma sessão nova do Claude Code começando um dos blocos abaixo, leia este arquivo primeiro e depois o arquivo específico da sua sessão (ex.: `06-web-crm-telas.md`).

**Fonte:** Documento de Requisitos — FAÉ Bioenergia (3io Digital, baseline 09/09/2026), também referenciado como "Especificações Técnicas v2.pdf" no `CLAUDE.md` do projeto. Consolida o Escopo já aprovado pelo cliente, organizado em NC (Núcleo compartilhado), BF (Buildflow/assistência técnica) e CRM (CRM Comercial), mais RNF (requisitos não funcionais). **Os nomes de tabela/coluna citados no documento são propostas de análise, não o schema real — sempre validar contra as migrations existentes antes de implementar.**

## Decisões de organização deste cronograma

1. **Etapa 1 (telas/navegação) → Etapa 2 (persistência/regras de negócio)**, para cada bloco de itens.
2. **Web primeiro, depois Android** — em sessões dedicadas do Claude Code.
3. **CRM Comercial priorizado**: vem logo após o Núcleo e o Configurador (que são pré-requisito técnico inevitável — não existe orçamento sem cliente cadastrado, nem pergunta de orçamento sem o banco de perguntas), e **antes** dos incrementos do módulo de Atendimento/Relatório da assistência técnica.
4. **Um arquivo `.md` por sessão** (esta pasta), para que cada sessão carregue só o contexto de que precisa.

**O que significa "Etapa 1" vs "Etapa 2" neste cronograma:** Etapa 1 entrega estrutura de tela, layout, navegação e wiring mínimo de dados (o suficiente para listar/salvar de forma simples e o cliente validar o fluxo visualmente) — **sem** regras de negócio finas (aprovações, versionamento de revisão, hash, cálculos automáticos, bloqueios de edição, notificações/alertas). Etapa 2 entrega essas regras completas, o schema definitivo (índices, constraints, migrations de ajuste) e os testes automatizados dos critérios de aceite do documento.

**Unidade do cronograma:** sessões, sem prazo de calendário. O histórico da branch `feature/fae` mostra que a migração completa de layout legado (19 commits, 8+ telas, upgrade Laravel 10→13) foi feita em ~1 dia com Claude Code (09/09/2026) — estimativas em "dias úteis" tradicionais não refletem o ritmo real. Cada item carrega a complexidade relativa (Baixa/Média/Alta) do próprio documento do cliente.

## Estado atual do código (levantado em 09/09/2026, leitura direta)

### Web — `sys-buildflow-fae`, branch `feature/fae`, v1.1.5
- ✅ 100% das telas legadas (clientes, atendimentos, atendimentos-relatorios, usuarios, ocorrencias, naturezas_atendimentos, modelos_relatorios, logs_auditoria, dashboard, login) já migradas para o template `sbadmin` (Bootstrap 5 + Alpine, `packages/sbadmin`, componentes `<x-sbadmin::layout>`/`<x-sbadmin::table>`/`<x-sbadmin::form.*>`) — sem jQuery/DataTables.
- ✅ Namespace `Api/Fae` e rotas `fae/v1` escafoldadas (RNF07).
- ✅ BF06 (PDF) já implementado via DomPDF (`AtendimentosRelatoriosController::pdf()`) — só precisa do ajuste de layout que NC03 exige.
- ❌ **Zero código** de NC01 (campos novos/contatos/pré-cadastro/aprovação), NC02 (Configurador inteiro), BF01, BF02, BF04, BF07, BF08.
- ⚠️ NC03/NC04: só existe a infraestrutura genérica de anexos do relatório (`atendimentos_relatorios_anexos/fotos/videos`), sem distinção por pergunta nem separação "anexo geral sem comentário".
- ⚠️ BF09: tabela de peças existe, mas só texto livre (`aten_rel_peca_descricao`) — falta os booleanos "levada"/"trocada".
- ⚠️ BF10: campo `aten_rel_status` já existe (0-preenchendo/1-revisar/2-aprovado), falta aprovador/data e bloqueio de edição pós-aprovação.
- ⚠️ BF11: existe tela de observações, mas no nível do **atendimento** (`AtendimentosController::getObservacoes/updateObservacoes`) — documento pede nível **relatório**.
- ❌ **CRM01 a CRM09: zero.** Nenhuma tabela, controller, rota ou view de orçamento/comercial existe.

### Android — `app_buildflow_fae`, branch `feature/fae`, v1.7.1+42
- Fork do app MCL: só 2 commits específicos da FAÉ (branding/config de backend) — todo o código de atendimentos/relatórios ainda é herdado do MCL, com formulário fixo (não o Configurador).
- ✅ Infra reaproveitável: sync offline (`lib/core/services/sync_service.dart` + `lib/core/database/local_db.dart`, sqflite), `go_router` (`lib/app/router.dart`), `provider`, `share_plus` (cobre BF07), `image_picker`.
- ❌ Nenhuma dependência de mapa/geo no `pubspec.yaml` (nem `geolocator` nem `google_maps_flutter`).
- ❌ Zero telas/rotas CRM — greenfield total.
- ❌ `UsuarioModel` (`lib/core/models/usuario_model.dart`) só tem `nivelAcesso` (int) — sem campo de perfil/role.

## Sequenciamento (CRM priorizado)

| Bloco | Itens | Por quê vem nessa posição |
|---|---|---|
| **1 — Núcleo** | NC01, BF01, BF02, BF03 | Pré-requisito de tudo |
| **2 — Configurador** | NC02, NC03, NC04, BF04 | Pré-requisito de BF05 (mobile) e CRM01 |
| **3 — CRM Comercial** *(priorizado)* | CRM01-08 (Web) / CRM09 (Android) | Só depende de 1 e 2; entregue antes dos incrementos de Atendimento por pedido do usuário |
| **4 — Atendimento/Relatório (incrementos)** | BF06(ajuste), BF07, BF08, BF09, BF10, BF11 | Depende de 1 (geo) e 2 (anexo/comentário); último bloco |

## Pendências do cliente (seção 9 do documento) — não travam, exceto uma

| # | Pendência | Bloco afetado | Ação nesta fase | Retrabalho se a resposta divergir |
|---|---|---|---|---|
| 1 | Segmento do cliente: lista ou texto livre | 1 (NC01) | Campo texto livre/configurável | Baixo — migration aditiva |
| 2 | Lista completa de campos (ERP SINPROD) | 1 (NC01) | Só os campos já mapeados no documento | Baixo — adição de colunas |
| 3 | Opções de Classificação do cliente | 1 (NC01) | Lista configurável, vazia | Baixo — popular lookup |
| 4 | Regra do alerta de recontato (dias) | 1 (NC01) | Campo configurável, default provisório | Baixo — troca de valor |
| 5 | Contador de tempo de atendimento | **BF12 — bloqueado** | Não entra no cronograma | N/A |
| 6 | Aprovação: histórico completo ou só último status | 4 (BF10) | Status simples agora | Médio, aditivo (tabela auditoria) |
| 7 | Tipo de resposta "Imagem" no Configurador | 2 (NC02/RNF03) | Fora do escopo desta fase | Fora do cronograma atual |
| 8 | Linha do tempo do orçamento | 3 (CRM08) | Fora do escopo desta fase | Fora do cronograma atual |

## Ordem das sessões

**Web:** [01-web-preparacao](01-web-preparacao.md) → [02](02-web-nucleo-telas.md)/[03](03-web-nucleo-persistencia.md) (Núcleo) → [04](04-web-configurador-telas.md)/[05](05-web-configurador-persistencia.md) (Configurador) → [06](06-web-crm-telas.md)/[07](07-web-crm-persistencia.md) (**CRM**) → [08](08-web-atendimento-telas.md)/[09](09-web-atendimento-persistencia.md) (Atendimento)

**Android (só depois do Web):** [10-android-preparacao](10-android-preparacao.md) → [11](11-android-nucleo-telas.md)/[12](12-android-nucleo-persistencia.md) (Núcleo) → [13](13-android-configurador-telas.md)/[14](14-android-configurador-persistencia.md) (Configurador) → [15](15-android-crm-telas.md)/[16](16-android-crm-persistencia.md) (**CRM**) → [17](17-android-atendimento-telas.md)/[18](18-android-atendimento-persistencia.md) (Atendimento)

## Regras que valem para todas as sessões

- Convenção de commit: `BF_vMAJOR.MINOR.PATCH` — **MINOR** ao fechar um item NC/BF/CRM completo (normalmente ao final da sessão de Persistência de um bloco), **PATCH** nos demais commits.
- A cada bump de **MINOR**, fazer a revisão de segurança descrita no `CLAUDE.md`: mapear rotas de `routes/web.php` e `routes/api.php` (grupo `fae/v1`) e confirmar que todas exigem `auth`/`sanctum`, exceto as que devem ser públicas de propósito (login, health-check).
- Toda alteração de schema é migration versionada específica da branch `feature/fae` (RNF07) — nunca aplicar em banco compartilhado com outro cliente.
- Ao implementar um item novo, criar o teste correspondente (`*FaeTest.php`) cobrindo os critérios de aceite listados no documento de requisitos para aquele item.
- BF12 fica fora do cronograma até decisão explícita do cliente.
