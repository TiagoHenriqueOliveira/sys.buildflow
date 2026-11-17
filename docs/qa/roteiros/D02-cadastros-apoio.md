# D2 — Cadastros de apoio (Segmentos, Classificações, Ocorrências)

[← Índice](../00-indice.md) · Anterior: [D1](D01-acesso-seguranca.md) · Próximo: [D3 — Configurador](D03-configurador.md)

| | |
|---|---|
| **Objetivo** | Validar os cadastros simples de Configurações e criar os valores que o cadastro de Clientes vai usar |
| **Por que agora** | Segmento e Classificação aparecem como listas no Cliente (D4). São telas simples, com o mesmo padrão: modal, ativo/inativo, sem exclusão. Testar o padrão uma vez aqui economiza tempo nos próximos dias |
| **Duração** | ~3 h |
| **Perfis** | `qa.admin`; `qa.tecnico1` só para o caso de permissão |
| **Pré-condições** | D1 concluído; matriz de perfis do D0 aplicada |

**Padrão das telas** (vale para Segmentos, Classificações, Ocorrências, Naturezas e Tipos de Orçamento):
- Listagem paginada de 15 em 15, cadastro e edição em modal, **sem exclusão**.
- O botão Ativo/Inativo **só aparece na edição**: todo cadastro novo nasce ativo.

## Vídeos

| Vídeo | Casos |
|---|---|
| `D02_CT-SEG-001-007_segmentos_...mp4` | Segmentos |
| `D02_CT-CLA-001-004_CT-OCO-001-003_classificacoes-ocorrencias_...mp4` | Classificações e Ocorrências |

---

## Segmentos (Configurações › Segmentos)

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-SEG-001 | Criar segmentos DEMO | Cadastrar `DEMO Sucroenergético`, `DEMO Alimentos` e `DEMO Descontinuado` | Os três aparecem na lista como ativos; o modal de cadastro não tem botão Ativo | Positivo | P1 |
| CT-SEG-002 | Descrição duplicada | Cadastrar de novo `DEMO Alimentos` | **"Já existe um segmento com essa descrição."** | Negativo | P2 |
| CT-SEG-003 | Duplicado com caixa diferente | Cadastrar `demo alimentos` | Recusado como duplicado. Se aceitar, registrar como bug Baixo (duplicidade visual na lista do Cliente) | Borda | P3 |
| CT-SEG-004 | Descrição vazia ou só espaços | 1) Salvar vazio 2) Salvar com 3 espaços | Erro de campo obrigatório nos dois casos | Negativo | P2 |
| CT-SEG-005 | Inativar | Editar `DEMO Descontinuado` › Inativo › Salvar; abrir Clientes › Cadastrar › campo Segmento | Linha fica inativa na lista; o segmento **não** aparece no select do Cliente | Positivo | P2 |
| CT-SEG-006 | Filtro persistente | Filtrar por `Alim`; ir para outra tela e voltar | Filtro continua aplicado e a lista filtrada | Positivo | P3 |
| CT-SEG-007 | Sem permissão | Como `qa.tecnico1`, abrir `/segmentos` pela URL | Página 403 | Permissão | P2 |

## Classificações de Cliente (Configurações › Classificações de Cliente)

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-CLA-001 | Criar classificações DEMO | Cadastrar `DEMO A`, `DEMO B` e `DEMO Inativa` | As três aparecem ativas | Positivo | P1 |
| CT-CLA-002 | Nome duplicado | Cadastrar de novo `DEMO A` | **"Já existe uma classificação com esse nome."** | Negativo | P2 |
| CT-CLA-003 | Limite de tamanho | Nome com 51 caracteres | Erro de tamanho máximo (50) | Borda | P3 |
| CT-CLA-004 | Inativar | Inativar `DEMO Inativa`; abrir Clientes › Cadastrar › Classificação | Não aparece no select do Cliente (no Mapa de Relações, D6, o filtro lista todas) | Positivo | P2 |

## Ocorrências (Configurações › Ocorrências)

> Ocorrências só aparecem em relatórios antigos (aba legada). O teste aqui é só do cadastro.

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-OCO-001 | Criar ocorrência | Cadastrar `DEMO Chuva forte` | Aparece ativa na lista | Positivo | P2 |
| CT-OCO-002 | Descrição vazia | Salvar sem descrição | Erro de campo obrigatório | Negativo | P3 |
| CT-OCO-003 | Editar, inativar e reativar | Mudar para `DEMO Chuva intensa`; inativar; reativar | Cada mudança aparece na lista na hora | Positivo | P3 |

---

## Pontos de atenção

- Segmento e Classificação são **únicos**; Ocorrência **não**. Confirme que o comportamento bate com isso.
- O efeito de inativar um segmento **já usado** por um cliente é testado no D4 (CT-CLI-019, risco R16).

---

## Registro de execução — D2

| ID | Status | Vídeo + tempo | Bug | Data / QA |
|---|---|---|---|---|
| CT-SEG-001 | | | | |
| CT-SEG-002 | | | | |
| CT-SEG-003 | | | | |
| CT-SEG-004 | | | | |
| CT-SEG-005 | | | | |
| CT-SEG-006 | | | | |
| CT-SEG-007 | | | | |
| CT-CLA-001 | | | | |
| CT-CLA-002 | | | | |
| CT-CLA-003 | | | | |
| CT-CLA-004 | | | | |
| CT-OCO-001 | | | | |
| CT-OCO-002 | | | | |
| CT-OCO-003 | | | | |
