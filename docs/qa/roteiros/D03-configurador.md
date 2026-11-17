# D3 — Configurador de Relatórios, Naturezas e Tipos de Orçamento

[← Índice](../00-indice.md) · Anterior: [D2](D02-cadastros-apoio.md) · Próximo: [D4 — Clientes](D04-crm-clientes.md)

| | |
|---|---|
| **Objetivo** | Validar o cadastro de perguntas e modelos (NC02), o vínculo natureza → modelo (BF04) e o vínculo tipo de orçamento → modelo (CRM01). Deixar prontos os modelos usados no Orçamento (D5) e no Relatório (D8) |
| **Por que agora** | Natureza de atendimento **exige** um modelo do setor Assistência. As abas do relatório saem do modelo. As perguntas do orçamento saem do modelo do tipo. Sem este dia, D5, D7 e D8 não têm como começar |
| **Duração** | ~6 h |
| **Perfis** | `qa.admin` |
| **Pré-condições** | D2 concluído |

## Como o Configurador funciona (resumo para a wiki)

- **Pergunta** tem um de 3 tipos de resposta: *Texto livre*, *Escolha única* ou *Múltipla escolha* (estes dois com opções).
  - Opções: "Permite anexo de imagem" (foto na resposta) e "Permite múltiplas respostas" (pergunta repetível).
- **Sessão** é uma "pergunta" marcada como **"É uma sessão (vira uma aba no relatório)"**. Ela não tem resposta; vira uma **aba** com o nome informado.
- **Modelo** reúne perguntas e tem um **setor**: *Assistência* (usado pela Natureza → Relatório) ou *Comercial* (usado pelo Tipo de Orçamento → Orçamento).
  - Cada pergunta do modelo pode ser vinculada a uma sessão pelo select "Vincular à sessão". Quem fica em "Sem sessão (geral)" vai para a aba **Perguntas**.
  - A **ordem** da lista define a ordem de exibição dentro de cada aba.

## Massa criada neste dia

| Registro | Configuração |
|---|---|
| `DEMO Condição geral do equipamento` | Texto livre, **permite anexo de imagem** |
| `DEMO Equipamento operando?` | Escolha única: Sim / Não |
| `DEMO Itens verificados` | Múltipla escolha: Painel / Motor / Sensores |
| `DEMO Serviço executado` | Texto livre, **permite múltiplas respostas** |
| Sessão `DEMO Inspeção Elétrica` | É uma sessão |
| `DEMO Potência desejada (kW)` | Texto livre (Comercial) |
| `DEMO Prazo de instalação` | Escolha única: 30 dias / 60 dias / 90 dias (Comercial) |
| `DEMO Modelo Assistência` | Setor Assistência; perguntas gerais: Condição geral, Serviço executado; na sessão Inspeção Elétrica: Equipamento operando?, Itens verificados |
| `DEMO Modelo Comercial` | Setor Comercial; Potência desejada, Prazo de instalação |
| Natureza `DEMO Manutenção corretiva` | → DEMO Modelo Assistência |
| Tipo de orçamento `DEMO Caldeira` | → DEMO Modelo Comercial |
| Tipo de orçamento `DEMO Sem modelo` | sem modelo |

## Vídeos

| Vídeo | Casos |
|---|---|
| `D03_CT-PER-001-006_perguntas-cadastro_...mp4` | Cadastro das perguntas e da sessão |
| `D03_CT-PER-007-011_perguntas-validacoes_...mp4` | Validações e listagem |
| `D03_CT-MOD-001-008_modelos_...mp4` | Modelos, sessões, ordem |
| `D03_CT-NAT-001-004_CT-TPO-001-003_naturezas-tipos_...mp4` | Naturezas e Tipos de Orçamento |

---

## Perguntas (Configurações › Configurador de Relatórios › Perguntas)

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-PER-001 | Pergunta texto livre com anexo | Cadastrar `DEMO Condição geral do equipamento`, tipo Texto livre, marcar "Permite anexo de imagem" | Salva; aparece na lista com o tipo Texto livre | Positivo | P1 |
| CT-PER-002 | Escolha única | Cadastrar `DEMO Equipamento operando?`, tipo Escolha única, "Incluir Opção" Sim e Não | Salva; ao reabrir, as 2 opções estão lá | Positivo | P1 |
| CT-PER-003 | Múltipla escolha | Cadastrar `DEMO Itens verificados` com 3 opções | Salva com as 3 opções | Positivo | P1 |
| CT-PER-004 | Pergunta repetível | Cadastrar `DEMO Serviço executado`, Texto livre, marcar "Permite múltiplas respostas" | Salva; ao reabrir, a opção continua marcada | Positivo | P1 |
| CT-PER-005 | Sessão | Novo › marcar "É uma sessão (vira uma aba no relatório)" › Nome da aba `DEMO Inspeção Elétrica` | "Texto da pergunta" fica **desabilitado**; tipo, opções, anexo e repetível **somem**; salva sem texto; na lista aparece o nome da aba | Positivo | P1 |
| CT-PER-006 | Perguntas comerciais | Cadastrar `DEMO Potência desejada (kW)` (texto) e `DEMO Prazo de instalação` (escolha única, 3 opções) | Salvam normalmente | Positivo | P1 |
| CT-PER-007 | Escolha sem opção | Tipo Escolha única (e depois Múltipla) sem incluir opção | **"Cadastre ao menos uma opção de resposta para este tipo de pergunta."** | Negativo | P2 |
| CT-PER-008 | Sessão sem nome | Marcar "É uma sessão" e salvar sem Nome da aba | **"Informe o nome da aba desta sessão."** | Negativo | P2 |
| CT-PER-009 | Pergunta sem tipo | Preencher só o texto e salvar | **"Selecione o tipo de resposta."** | Negativo | P2 |
| CT-PER-010 | Opção em branco | Incluir uma linha de opção e deixá-la vazia | Erro de texto obrigatório na opção | Negativo | P3 |
| CT-PER-011 | Busca e filtro | 1) Filtrar texto `Inspeção` 2) Filtrar tipo Escolha única 3) Sair e voltar | 1) Encontra a sessão pelo nome da aba 2) Lista só as de escolha única 3) Filtro persiste | Positivo | P2 |

## Modelos (Configurações › Configurador de Relatórios › Modelos)

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-MOD-001 | Criar modelo de Assistência | Novo › Nome do modelo `DEMO Modelo Assistência` › Setor Assistência › no campo de perguntas, digitar 2+ letras e adicionar as 4 perguntas de Assistência e a sessão | Autocomplete sugere a partir de 2 caracteres; a lista "Perguntas do modelo" mostra a sessão com destaque | Positivo | P1 |
| CT-MOD-002 | Vincular perguntas à sessão | Em `DEMO Equipamento operando?` e `DEMO Itens verificados`, escolher a sessão no select "Vincular à sessão"; deixar as outras em "Sem sessão (geral)"; Salvar e reabrir | Vínculos mantidos ao reabrir | Positivo | P1 |
| CT-MOD-003 | Ordem | Usar "Mover para cima/baixo" para pôr `DEMO Serviço executado` antes de `DEMO Condição geral`; salvar e reabrir | Ordem mantida (é a ordem em que as perguntas aparecem no relatório, conferida no D8) | Positivo | P2 |
| CT-MOD-004 | Pergunta repetida | Tentar adicionar `DEMO Itens verificados` de novo | A tela não deixa adicionar a mesma pergunta duas vezes | Negativo | P3 |
| CT-MOD-005 | Modelo sem perguntas | Novo modelo só com nome e setor | **"Selecione ao menos uma pergunta para o modelo."** | Negativo | P2 |
| CT-MOD-006 | Remover a sessão | No DEMO Modelo Assistência, remover a sessão da lista (sem salvar); depois cancelar o modal | As perguntas vinculadas voltam para "Sem sessão (geral)" na tela; ao cancelar, o modelo salvo continua intacto | Positivo | P2 |
| CT-MOD-007 | Criar modelo Comercial | `DEMO Modelo Comercial`, Setor Comercial, com as 2 perguntas comerciais | Salva | Positivo | P1 |
| CT-MOD-008 | Filtros | Filtrar por nome e por setor | Lista filtrada corretamente; filtro persiste | Positivo | P3 |

## Naturezas de Atendimentos (Configurações › Naturezas de Atendimentos)

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-NAT-001 | Criar natureza | `DEMO Manutenção corretiva` → modelo `DEMO Modelo Assistência` | Salva; aparece na lista | Positivo | P1 |
| CT-NAT-002 | Só modelos de Assistência | Abrir o select de modelo | Lista só modelos **ativos** do setor Assistência; `DEMO Modelo Comercial` **não** aparece | Positivo | P1 |
| CT-NAT-003 | Modelo obrigatório | Salvar sem modelo | Erro de campo obrigatório | Negativo | P2 |
| CT-NAT-004 | Natureza inativa | Criar `DEMO Natureza inativa`, inativar; abrir Atendimentos › Cadastrar › Natureza | Natureza inativa não aparece no select do Atendimento | Positivo | P3 |

## Tipos de Orçamento (Configurações › Tipos de Orçamento)

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-TPO-001 | Criar tipo com modelo | `DEMO Caldeira` → `DEMO Modelo Comercial` | Salva | Positivo | P1 |
| CT-TPO-002 | Só modelos Comerciais | Abrir o select de modelo | Só modelos ativos do setor Comercial | Positivo | P2 |
| CT-TPO-003 | Tipo sem modelo | `DEMO Sem modelo` sem escolher modelo | Salva (o modelo é opcional) | Positivo | P3 |

---

## Pontos de atenção

- **R08 — sem versionamento:** editar as opções de uma pergunta já respondida pode quebrar respostas antigas. **Não edite** as perguntas DEMO depois deste dia. O teste dedicado é o CT-INT-001 (D10), que roda quando já houver respostas.
- Mudar o setor de um modelo já vinculado a uma natureza não é bloqueado. Evite fazer isso na massa DEMO.
- O texto de ajuda do modal de Sessão ainda fala em "perguntas depois desta". Hoje o agrupamento é pelo select "Vincular à sessão". Se o texto confundir, registre como bug Baixo de texto.

---

## Registro de execução — D3

| ID | Status | Vídeo + tempo | Bug | Data / QA |
|---|---|---|---|---|
| CT-PER-001 | | | | |
| CT-PER-002 | | | | |
| CT-PER-003 | | | | |
| CT-PER-004 | | | | |
| CT-PER-005 | | | | |
| CT-PER-006 | | | | |
| CT-PER-007 | | | | |
| CT-PER-008 | | | | |
| CT-PER-009 | | | | |
| CT-PER-010 | | | | |
| CT-PER-011 | | | | |
| CT-MOD-001 | | | | |
| CT-MOD-002 | | | | |
| CT-MOD-003 | | | | |
| CT-MOD-004 | | | | |
| CT-MOD-005 | | | | |
| CT-MOD-006 | | | | |
| CT-MOD-007 | | | | |
| CT-MOD-008 | | | | |
| CT-NAT-001 | | | | |
| CT-NAT-002 | | | | |
| CT-NAT-003 | | | | |
| CT-NAT-004 | | | | |
| CT-TPO-001 | | | | |
| CT-TPO-002 | | | | |
| CT-TPO-003 | | | | |
