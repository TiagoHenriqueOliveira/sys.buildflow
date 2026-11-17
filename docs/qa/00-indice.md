# Cronograma de Testes QA — Buildflow FAÉ (Web)

> **Versão sob teste:** BF_v1.14.0 (commit `1411d0a`, branch `feature/fae`)
> **Ambiente:** local — `https://sys-buildflow-fae.local`, banco `buildflow_fae`
> **Escopo:** painel Web (o app Android terá cronograma próprio quando entrar em teste)
> **Formato:** dias relativos (D0…D11). Quem executa define o D0.

Esta pasta é a **wiki de QA** do Buildflow FAÉ. Cada roteiro diz **o que validar e como**. Cada execução é **gravada em vídeo**, e o link fica registrado no próprio roteiro. Assim o mesmo material serve de evidência de teste e de documentação de uso do sistema.

---

## Como usar esta wiki

1. Faça o **D0 inteiro** ([01-preparacao.md](01-preparacao.md)) antes de qualquer roteiro.
2. Configure a gravação conforme o [02-padrao-gravacao.md](02-padrao-gravacao.md). Nenhum caso conta como executado sem vídeo.
3. Execute os roteiros **na ordem**, de D1 a D11. Cada dia cria a massa de dados que o dia seguinte usa.
4. Para cada defeito encontrado, abra um bug conforme o [03-padrao-bugs.md](03-padrao-bugs.md).
5. No fim de cada dia:
   - preencha o **Registro de execução** no fim do roteiro;
   - atualize o [Painel de execução](#painel-de-execução) deste índice.

---

## Por onde começar, e por quê

A ordem segue a **dependência de dados** entre os módulos. Não dá para testar Relatório sem Atendimento. Atendimento depende de Natureza. Natureza depende de um Modelo do Configurador. E nada funciona sem usuários e perfis de acesso.

É a mesma sequência do treinamento do cliente: **Configurar → Vender → Atender → Acompanhar**.

```
D0  Preparação (ambiente, backup, testes automatizados, smoke)
 └─ D1  Acesso, usuários e Perfis de Acesso
     └─ D2  Cadastros de apoio (Segmentos, Classificações, Ocorrências)
         └─ D3  Configurador (Perguntas, Modelos) + Naturezas + Tipos de Orçamento
             └─ D4  CRM: Clientes
                 └─ D5  CRM: Orçamentos e Roteiros de Viagem
                     └─ D6  CRM: Mapa de Relações, Indicadores, Notificações
                         └─ D7  Assistência: Atendimentos
                             └─ D8  Assistência: Relatórios (criação e preenchimento)
                                 └─ D9  Assistência: Aprovação, PDF e Mapa de Demandas
                                     └─ D10 Transversais e segurança
                                         └─ D11 Reteste, regressão e fechamento
```

---

## Cronograma

| Dia | Roteiro | O que entra | Perfis usados | Vídeos | Massa que o dia deixa pronta |
|---|---|---|---|---|---|
| D0 | [Preparação](01-preparacao.md) | Ambiente, backup do banco, suíte automatizada (critério de entrada), ferramentas de gravação, smoke de todos os menus | Administrador | 1 | Ambiente validado |
| D1 | [Acesso e segurança](roteiros/D01-acesso-seguranca.md) | Login, sessão, Minha conta, Usuários, **Perfis de Acesso**, menu por perfil, página 403 | Todos | 4 | Usuários QA e matriz de perfis |
| D2 | [Cadastros de apoio](roteiros/D02-cadastros-apoio.md) | Segmentos, Classificações de Cliente, Ocorrências, ativar/inativar, filtro persistente | Administrador, Técnico | 2 | Segmentos e classificações DEMO |
| D3 | [Configurador](roteiros/D03-configurador.md) | Perguntas (3 tipos, anexo, repetível, sessão), Modelos, Naturezas, Tipos de Orçamento | Administrador | 4 | Modelos Assistência e Comercial, natureza e tipo DEMO |
| D4 | [CRM: Clientes](roteiros/D04-crm-clientes.md) | Abas Dados, Contatos, Geolocalização, Histórico; CNPJ; recontato; filtros | Comercial, Técnico | 3 | 4 clientes DEMO (C1–C4) |
| D5 | [CRM: Orçamentos e Roteiros](roteiros/D05-crm-orcamentos-roteiros.md) | Prazo em dias úteis, perguntas por tipo, vendedores, resultado, comentários com alerta, roteiros | Comercial, Administrador | 4 | 5 orçamentos e 1 roteiro DEMO |
| D6 | [CRM: Mapas, Indicadores e Notificações](roteiros/D06-crm-mapas-indicadores-notificacoes.md) | Mapa de Relações, Indicadores Comerciais, sino, alerta de recontato | Comercial, Vendedor | 3 | Indicadores conferidos |
| D7 | [Atendimentos](roteiros/D07-atendimentos.md) | Cadastro em abas, validações, status, equipamentos, anexos, posse do técnico | Administrador, Técnico 1, Técnico 2, Assistência | 4 | 4 atendimentos DEMO (A1–A4) |
| D8 | [Relatórios: criação e preenchimento](roteiros/D08-relatorios-preenchimento.md) | Bloqueios de criação, abas pelo modelo, horários por dia, perguntas, sessões, fotos, compartilhamento | Técnico 1 | 4 | Relatório DEMO pronto para liberar |
| D9 | [Relatórios: aprovação e PDF](roteiros/D09-relatorios-aprovacao-pdf.md) | Fluxo Preenchendo → Revisar → Liberado → Aprovado, assinaturas, notificação, somente leitura, PDF, Mapa de Demandas | Técnico 1, Assistência, Administrador | 4 | Relatório aprovado e PDF |
| D10 | [Transversais e segurança](roteiros/D10-transversais-seguranca.md) | Permissão por URL, IDOR, `/midia`, gravação em relatório aprovado, XSS, filtros, auditoria, tema, celular, APK | Todos | 5 | Matriz de segurança preenchida |
| D11 | [Reteste, regressão e fechamento](roteiros/D11-regressao-fechamento.md) | Reteste dos bugs, vídeo mestre ponta a ponta, relatório final | Todos | 2+ | Relatório final de QA |

**Ritmo esperado:** 1 QA, cerca de 6 h efetivas por dia, já contando gravação e registro.

**Com 2 QAs:**
- O D10 pode correr em paralelo ao D9, desde que a massa do D7 já exista.
- O reteste do D11 começa assim que o primeiro bug for corrigido.

---

## Critérios de entrada e saída

**Entrada geral (fim do D0):**
- Rodapé do sistema mostra `v1.14.0`.
- `php artisan test` sem falha nova. A baseline conhecida são as 6 falhas em `AtendimentoRelatorioTest`.
- Smoke OK: todos os menus abrem sem erro 500.
- Backup do banco feito.
- Gravação configurada.

**Entrada de cada dia:** o roteiro anterior foi concluído (ou os bloqueios estão registrados) e a massa que ele cria existe.

**Suspensão:** pare a rodada e abra bug **Crítico** se acontecer qualquer um destes:
- não é possível fazer login;
- não é possível criar Atendimento ou Relatório;
- aparece erro 500 em tela principal.

Retome depois da correção, refazendo o smoke do D0.

**Saída (fim do D11):**
- 100% dos casos **P1** e pelo menos 90% dos **P2** executados.
- Todo caso executado tem vídeo linkado.
- Nenhum bug **Crítico** ou **Alto** aberto sem aceite formal do responsável pelo produto.
- Relatório final publicado no [D11](roteiros/D11-regressao-fechamento.md).

**Prioridade dos casos:**

| Prioridade | Quando usar |
|---|---|
| **P1** | Caminho crítico, segurança ou perda de dado. Bloqueia a liberação. |
| **P2** | Regra de negócio importante, mensagem de validação, permissão secundária. |
| **P3** | Borda, cosmético, usabilidade. |

---

## Riscos mapeados na leitura do código

Antes de montar os roteiros, o código da BF_v1.14.0 foi lido módulo a módulo. Os pontos abaixo são **hipóteses a confirmar** na execução, não bugs confirmados. Cada um tem um caso de teste dedicado.

Se o comportamento se confirmar, abra o bug e preencha a coluna **Resultado**. Se não se confirmar, marque "Não reproduzido".

| # | Hipótese | Onde no código | Verificado em | Resultado |
|---|---|---|---|---|
| R01 | Relatório **Aprovado** só fica somente leitura na tela; o backend ainda aceita gravação direta (dados, horários, respostas, fotos, exclusões) | `resources/views/atendimentos-relatorios/show.blade.php` (comentário "BF10 - bloqueio VISUAL") | CT-SEC-006 | |
| R02 | `/midia/{path}` não confere posse: qualquer usuário logado abre arquivo de outro técnico. Assinaturas têm caminho previsível | `app/Http/Controllers/StorageController.php` | CT-SEC-005 | |
| R03 | `PUT /atendimentos/{id}` não confere se o atendimento é do técnico logado | `AtendimentosController::update` | CT-SEC-004 | |
| R04 | A Web deixa o Atendimento ir para **Concluída** sem relatório aprovado; a API bloqueia (422) | `AtendimentosController` × `Api/Fae/AtendimentosController::updateStatus` | CT-ATE-012 | |
| R05 | Autocomplete de atendimento no "Novo relatório" lista atendimentos de outros técnicos (o salvar bloqueia) | `AtendimentosRelatoriosController::autocomplete` | CT-REL-005 | |
| R06 | Botão "Cadastrar" de relatório aparece para quem não tem `relatorios.editar` (dá 403 ao salvar) | `atendimentos-relatorios/index.blade.php` | CT-SEC-003 | |
| R07 | "Entrega técnica" do Atendimento: desmarcar e salvar não grava (o valor antigo volta) | `atendimentos/form.blade.php` (checkbox sem hidden 0) | CT-ATE-009 | |
| R08 | Editar as opções de uma pergunta recria os ids; respostas antigas (relatório, orçamento, PDF) perdem a correspondência | `ConfiguradorPerguntaRepository` | CT-INT-001 | |
| R09 | Roteiro com o mesmo cliente duas vezes dá erro 500; depois de erro de validação a lista de clientes some | `RoteiroViagemRepository`, `roteiros-viagem/form.blade.php` | CT-ROT-006, CT-ROT-007 | |
| R10 | Prazo do orçamento digitado à mão se perde após erro de validação; trocar o nível sobrescreve o prazo manual | `orcamentos/form.blade.php` | CT-ORC-007, CT-ORC-008 | |
| R11 | Botão "Limpar" não limpa os filtros em Indicadores, Mapa de Relações e Mapa de Demandas | `PersisteFiltros` + views | CT-FIL-004 | |
| R12 | "Alertar usuário" lista quem só tem `orcamentos.ver`, mas o link da notificação exige `orcamentos.editar` (dá 403) | `OrcamentosController::storeComentario` | CT-NOT-005 | |
| R13 | Mensagem do alerta de recontato pode mostrar dias com casas decimais | `VerificarRecontatoClientes.php` | CT-NOT-007 | |
| R14 | Usuário desativado continua navegando com a sessão Web já aberta | `app/Http/Middleware/Authenticate.php` | CT-USR-009 | |
| R15 | Exclusões de subitens não pedem confirmação; comentário de orçamento pode ser excluído por quem não é o autor | `orcamentos/form.blade.php` e outras | CT-ORC-014 | |
| R16 | Selects com opção vazia desabilitada: um valor escolhido não volta a vazio (ex.: resultado "Em aberto"); valor inativo pode ser trocado sem aviso | `packages/sbadmin/.../form/select.blade.php` | CT-ORC-012, CT-CLI-019 | |
| R17 | PDF traz o texto fixo "A MCLvale, neste ato…" no bloco Entrega Técnica (marca errada) e não mostra a data da assinatura | `atendimentos-relatorios/pdf.blade.php` (linha ~565) | CT-PDF-005, CT-PDF-006 | |
| R18 | Filtro de ação dos Logs de Auditoria oferece INATIVAR/CONCLUIR (nunca gravadas) e não oferece ATUALIZAR (gravada) | `LogsAuditoriaController` | CT-AUD-003 | |
| R19 | Validações fracas: CNPJ sem dígito verificador, UF "XX" aceita, CPF da assinatura sem validação | `ClienteRequest`, assinatura do relatório | CT-CLI-005, CT-CLI-006, CT-APR-010 | |
| R20 | Mapa de Relações mostra clientes **inativos** | `MapaRelacoesController` | CT-MRE-004 | |

---

## Fora de escopo nesta rodada

| Item | Motivo |
|---|---|
| App Android e API `fae/v1` | Cronograma próprio quando o app entrar em teste. As chamadas diretas do D10 usam só as rotas Web. |
| BF12 (contador de tempo de atendimento) | Pendente de definição do cliente |
| Versionamento de modelos do Configurador (NC02) | Não implementado. O risco R08 cobre o efeito colateral. |
| BF09 "peça levada" | Não implementado (só existe "peça trocada") |
| Notificação push (FCM) | Não implementado |
| E-mail | O sistema não envia e-mail nenhum |
| Recuperação de senha ("esqueci a senha") | Não existe. Registrar só como observação de UX (CT-LOG-008). |
| Dashboard com indicadores | A tela inicial ainda é um texto de boas-vindas |

---

## Painel de execução

Atualize ao fim de cada dia. "Executados" conta Passou + Falhou + Bloqueado.

| Dia | Casos | P1 | Executados | Passou | Falhou | Bloqueado | Vídeos gravados | Bugs abertos |
|---|---|---|---|---|---|---|---|---|
| D0 | 8 | 8 | | | | | | |
| D1 | 36 | 19 | | | | | | |
| D2 | 14 | 2 | | | | | | |
| D3 | 26 | 12 | | | | | | |
| D4 | 21 | 5 | | | | | | |
| D5 | 25 | 9 | | | | | | |
| D6 | 18 | 6 | | | | | | |
| D7 | 20 | 7 | | | | | | |
| D8 | 27 | 12 | | | | | | |
| D9 | 25 | 14 | | | | | | |
| D10 | 25 | 10 | | | | | | |
| D11 | 6 | 6 | | | | | | |
| **Total** | **251** | **110** | | | | | | |

---

## Referências

- [Testes automatizados](../../tests/Feature): os arquivos `*Fae*Test.php` já cobrem parte das regras. O QA manual foca no que o teste automatizado não vê: tela, fluxo, permissão no menu, PDF e vídeo.
- [Cronograma de desenvolvimento](../cronograma/00-indice.md): estado de cada item NC/BF/CRM.
- Manual do usuário (prints e treinamento): `docs/manual-usuario/`.
- Matriz de permissões: `app/Support/Permissoes.php` e `database/seeders/PermissoesPadraoSeeder.php`.
