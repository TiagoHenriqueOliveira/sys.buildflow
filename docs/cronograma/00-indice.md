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

## Estado atual do código (atualizado em 14/09/2026 — Web concluído, releitura direta)

### Web — `sys-buildflow-fae`, branch `feature/fae`, **BF_v1.8.5** (116 testes passando, baseline de 6 falhas pré-existentes em `AtendimentoRelatorioTest` inalterada)

**Os 4 blocos de Telas do Web (02→04→06→08, ver "Ordem das sessões" abaixo) estão concluídos e aprovados pelo cliente em rodadas sucessivas de feedback.** Só falta a segunda passada de Persistência (03/05/07/09) — fora de escopo até nova confirmação do usuário. Detalhe do que existe hoje, pra servir de referência direta às sessões Android:

- ✅ **NC01 (Cliente)** — CRUD completo em `clientes/form.blade.php` + `ClientesController`/`ClienteRepository`: abas Dados, Contatos (lista repetível, tipo Técnico/Comercial), Geolocalização, Histórico (equipamentos, lista repetível). **Geolocalização final (BF_v1.8.2): sem picker/busca de mapa** — só botão "Usar minha localização" (GPS do navegador) + campo de link do Google Maps colado à mão, mesmo padrão em localização principal, "Outras localizações" e Roteiro de Viagem. Não reintroduzir Leaflet/Nominatim no mobile.
- ✅ **NC02 (Configurador)** — `ConfigPergunta`/`ConfigModelo`, admin-only (`configurador/perguntas`, `configurador/modelos`). 3 tipos de resposta (`TipoPergunta`: TextoLivre/EscolhaUnica/MultiplaEscolha), anexo de imagem opcional por pergunta, pergunta repetível (múltiplas respostas). **Mecanismo "Sessão" (BF_v1.8.0/1.8.3, não estava na spec original):** uma pergunta pode ser marcada `cfg_perg_e_sessao=true` — vira um marcador sem tipo de resposta, com `cfg_perg_sessao_nome` (nome da aba). Outras perguntas se vinculam a ela de forma **explícita** via `cfg_mod_perg_sessao_id` (coluna na pivot `config_modelos_perguntas`, não por ordem/adjacência) — ver `ConfigModelo::perguntasAgrupadasPorSessao()`, é o método central pra "quais perguntas aparecem em qual aba". Sem revisão versionada (fora de escopo desta fase).
- ✅ **NC03/NC04 (anexos)** — anexo por pergunta reaproveita o storage de mídia do relatório; aba "Anexos" do relatório aceita só fotos (mesmo padrão do cadastro de Atendimento).
- ✅ **BF01/02/03** — geolocalização (Cliente, perfis Administrador/Comercial/Assistência), `NivelAcesso` com **5 valores** (`Administrador=0, Tecnico=1, Comercial=2, Assistencia=3, Vendedor=4` — Assistência/Vendedor sem regra de acesso própria definida ainda), exibição de dados do cliente no atendimento.
- ✅ **BF04** — vínculo modelo↔natureza de atendimento (`NaturezaAtendimento::nat_aten_config_modelo_id`).
- ✅ **Atendimento/Relatório reformado por completo** (`atendimentos-relatorios/show.blade.php`) — abas fixas antigas (Clima/Serviços/Peças/Ocorrências) só aparecem se o relatório já tiver dado legado; modelo novo usa só "Perguntas" (genéricas) + uma aba por Sessão, montadas dinamicamente a partir de `perguntasAgrupadasPorSessao()`. BF06 (PDF, DomPDF) só imprime perguntas respondidas. Aba "Compartilhamento" existe (botão "Compartilhar").
- ✅ **BF07 (comprovante)** — hash SHA-256 por compartilhamento (`AtendimentosRelatoriosController::storeCompartilhamento()`, tabela `atendimentos_relatorios_compartilhamentos`). ✅ **BF08 (mapa de demandas)** — `MapaDemandasController::index()`, rota `mapa-demandas.index`. ⚠️ **BF09 (checklist de peças) — só metade**: `aten_rel_peca_trocada` (boolean) existe, `aten_rel_peca_levada` **não existe**. ✅ **BF10 (aprovação)** — `aten_rel_status` + `aten_rel_aprovado_por`/`_em` no model, sem histórico completo (pendência #6 abaixo). ✅ **BF11 (observação interna)** — a nível de **relatório** (não mais de atendimento). Detalhe completo em [17-android-atendimento-telas.md](17-android-atendimento-telas.md).
- ✅ **CRM01 a CRM08 (Web)** — Orçamentos completos (`orcamentos/form.blade.php` + `OrcamentosController`): abas Dados (tipo de sistema, nível manual, prazo de envio manual — **fórmula automática de nível→prazo, CRM02, ainda NÃO implementada, é item de Persistência futura**), Perguntas (dinâmicas por tipo de orçamento, reaproveita o Configurador), Vendedores Adicionais (indicação conjunta sem comissão, CRM04), Comentários (com alerta a outro usuário e **exclusão**, CRM03). Roteiro de Viagem (saída/retorno, CRM05/06). Mapa de Relações (CRM07, Leaflet **só leitura**, múltiplos pontos — não confundir com o picker removido do Cliente, é outro uso). Indicadores Comerciais (CRM08, básico).
- ❌ **Nada disso está exposto em `routes/api.php`.** O grupo `fae/v1` (ver `routes/api.php`) continua **idêntico ao herdado do MCL** — só o fluxo fixo de atendimento/relatório antigo (login, atendimentos, relatórios com horarios/clima/mao-obra/equipamentos/atividades/ocorrencias/comentarios/anexos). **Nenhum endpoint existe para Cliente, Configurador/Sessão ou qualquer item de CRM01-09** — é trabalho novo, não só descoberta.

### Android — `app_buildflow_fae`, branch `feature/fae`, v1.7.1+42 (releitura em 14/09/2026)
- Ainda **100% o fluxo herdado do MCL** — login, lista de atendimentos, relatório com seções fixas (`lib/features/relatorios/sections/*`: clima, descricao, horarios, info_adicionais, ocorrencias, pecas, servicos, status, assinaturas, anexos). Nenhum commit específico da FAÉ além de branding/URL de backend e troca de fonte.
- ✅ Infra reaproveitável: sync offline (`lib/core/services/sync_service.dart` + `lib/core/database/local_db.dart`, sqflite), `go_router` (`lib/app/router.dart`), `provider`, `share_plus`, `image_picker`, `url_launcher` (cobre abrir link do Google Maps sem precisar de mapa embutido).
- ❌ Nenhuma dependência de geolocalização no `pubspec.yaml` (nem `geolocator`); nenhum pacote de mapa (`google_maps_flutter`/`flutter_map`) — só é necessário se o Mapa de Relações (CRM07) for exibido embutido no app (decisão em aberto, ver sessão 15).
- ❌ Zero telas/rotas de Cliente, Configurador/formulário dinâmico ou CRM — greenfield total nas três frentes.
- ❌ `UsuarioModel` (`lib/core/models/usuario_model.dart`) só tem `nivelAcesso` (int) com `isAdmin => nivelAcesso >= 2` — **desatualizado pro `NivelAcesso` real (5 valores)**: hoje classificaria Comercial/Assistência/Vendedor como admin, o que é errado. Precisa de getters próprios (`isComercial`, etc.) antes de qualquer gating de menu por perfil.

**Decisão do usuário (14/09/2026) pra próxima leva de sessões Android:** telas com **dado real** desde já — os endpoints REST novos em `fae/v1` (`sys-buildflow-fae`) são criados **junto** com cada tela correspondente, na mesma sessão, e não ficam pra depois. Continua valendo, porém, adiar as regras de negócio mais pesadas (fórmula automática de nível/prazo, hash de comprovante, sync offline completo, aprovação com histórico) — isso permanece nas sessões de Persistência (12/14/16/18), fora de escopo até nova confirmação. Sessões 11, 13 e 15 foram reescritas com esse escopo ampliado (telas + API); ver cada arquivo.

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

**Passe atual autorizado (14/09/2026, Android): só as sessões de Telas — 10 → 11 → 13 → 15 → 17 — com os endpoints REST novos criados junto em cada uma (ver decisão acima). Não iniciar 12/14/16/18 (Persistência) sem pedido explícito do usuário.**

**Passe atual autorizado (16/09/2026, Web): sessões de Persistência liberadas — 03 → 05 → 07 → 09, em ordem, uma de cada vez.** Todas as 4 foram reescritas em 16/09/2026 com base num levantamento direto do código atual (não confiar em leitura anterior desses 4 arquivos específicos) — cada uma já identifica exatamente o que falta, o que já está pronto (não refazer) e os pontos que precisam de confirmação do usuário antes de implementar (arquitetura de versionamento do Configurador, regras de pré-cadastro/aprovação do Cliente). Itens que dependem de pendência do cliente (histórico completo de aprovação, "Imagem" no Configurador, linha do tempo do orçamento) continuam fora até resposta.

## Regras que valem para todas as sessões

- Convenção de commit: `BF_vMAJOR.MINOR.PATCH` — **MINOR** ao fechar um item NC/BF/CRM completo (normalmente ao final da sessão de Persistência de um bloco), **PATCH** nos demais commits.
- A cada bump de **MINOR**, fazer a revisão de segurança descrita no `CLAUDE.md`: mapear rotas de `routes/web.php` e `routes/api.php` (grupo `fae/v1`) e confirmar que todas exigem `auth`/`sanctum`, exceto as que devem ser públicas de propósito (login, health-check).
- Toda alteração de schema é migration versionada específica da branch `feature/fae` (RNF07) — nunca aplicar em banco compartilhado com outro cliente.
- Ao implementar um item novo, criar o teste correspondente (`*FaeTest.php`) cobrindo os critérios de aceite listados no documento de requisitos para aquele item.
- BF12 fica fora do cronograma até decisão explícita do cliente.
