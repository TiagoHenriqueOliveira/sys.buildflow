# D5 — CRM: Orçamentos e Roteiros de Viagem

[← Índice](../00-indice.md) · Anterior: [D4](D04-crm-clientes.md) · Próximo: [D6 — Mapas, Indicadores e Notificações](D06-crm-mapas-indicadores-notificacoes.md)

| | |
|---|---|
| **Objetivo** | Validar Orçamentos (CRM01–04, CRM08: tipo → perguntas, nível → prazo em dias úteis, vendedores, resultado, comentários com alerta) e Roteiros de Viagem (CRM05/06). Criar a massa que os Indicadores do D6 vão somar |
| **Por que agora** | Indicadores e Mapa de Relações só fazem sentido com orçamentos e visitas cadastrados |
| **Duração** | ~6 h |
| **Perfis** | `qa.comercial` (principal), `qa.admin` (destinatário de alerta e exclusão de comentário) |
| **Pré-condições** | D3 (modelo comercial, tipos `DEMO Caldeira` e `DEMO Sem modelo`) e D4 (clientes C1–C4) concluídos |

## Regra do prazo de envio (CRM02)

| Nível | Prazo |
|---|---|
| Simples | **1** dia útil depois da data de inclusão |
| Médio | **5** dias úteis |
| Complexo | **10** dias úteis |

- Conta só de segunda a sexta. **Feriados não são descontados.** O horário da inclusão não muda o dia.
- Se a inclusão cai no fim de semana, a contagem começa na segunda.
- Desde a v1.14.0 o campo é **editável**: um prazo informado à mão vale; se o campo ficar vazio, o sistema calcula pelo nível.

Antes de gravar, calcule e anote o esperado **para a data de hoje**. Exemplos da regra, tirados do teste automatizado `NivelOrcamentoPrazoTest`:

| Inclusão | Simples | Médio | Complexo |
|---|---|---|---|
| Qua 14/10/2026 | — | Qua 21/10 | — |
| Sex 16/10/2026 | Seg 19/10 | — | Sex 30/10 |
| Sáb 17/10/2026 | Seg 19/10 | Sex 23/10 | — |
| Seg 19/10/2026 | Ter 20/10 | — | — |
| **Hoje: ____/____** | ____ | ____ | ____ |

## Massa criada neste dia

| Orçamento | Cliente | Tipo | Nível | Resultado |
|---|---|---|---|---|
| O1 | C1 | DEMO Caldeira | Simples | Convertido |
| O2 | C2 | DEMO Caldeira | Médio | Convertido |
| O3 | C1 | DEMO Caldeira | Complexo | Não convertido |
| O4 | C2 | DEMO Sem modelo | — | Adiado |
| O5 | C3 | DEMO Caldeira | Simples | (Em aberto) |

Todos com vendedor responsável `qa.comercial`. Roteiro R1: vendedor `qa.comercial`, clientes C1 e C2.

## Vídeos

| Vídeo | Casos |
|---|---|
| `D05_CT-ORC-001-008_orcamentos-prazo_...mp4` | Cadastro e prazo |
| `D05_CT-ORC-009-012_orcamentos-perguntas-vendedores_...mp4` | Perguntas, vendedores, resultado |
| `D05_CT-ORC-013-017_orcamentos-comentarios-massa_...mp4` | Comentários, alerta, massa O2–O5, filtros |
| `D05_CT-ROT-001-008_roteiros_...mp4` | Roteiros de viagem |

---

## Orçamentos

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-ORC-001 | Criar orçamento | Orçamentos › Cadastrar › cliente C1 (autocomplete a partir de 3 letras) › Vendedor responsável `qa.comercial` › Tipo de sistema `DEMO Caldeira` › Nível Simples › Salvar | Prazo de envio sugerido = próximo dia útil; ao salvar, abre a **edição** do orçamento com o número (O1) | Positivo | P1 |
| CT-ORC-002 | Prazo por nível | No O1, trocar o Nível para Médio e depois Complexo (sem salvar) | Prazo recalculado na tela: +5 e +10 dias úteis, igual à tabela anotada | Positivo | P1 |
| CT-ORC-003 | Prazo manual | Voltar o nível para Simples, digitar outra data no Prazo de envio › Salvar › reabrir | Mantém a data digitada | Positivo | P1 |
| CT-ORC-004 | Prazo vazio é calculado | Apagar o Prazo de envio › Salvar › reabrir | Prazo preenchido pelo sistema conforme o nível | Positivo | P2 |
| CT-ORC-005 | Sem nível e sem prazo | Novo orçamento sem nível e com o prazo vazio | Salva com o prazo vazio | Borda | P3 |
| CT-ORC-006 | Prazo conta da inclusão, não da edição | **Executar a partir do D6** em um orçamento criado em dia anterior: apagar o prazo e salvar | Prazo calculado a partir da data em que o orçamento foi **criado**, não da data da edição | Borda | P3 |
| CT-ORC-007 | Trocar nível com prazo manual (**R10**) | Digitar um prazo à mão; depois trocar o Nível | **Hipótese R10:** a sugestão do nível sobrescreve o prazo digitado sem avisar. Registrar o comportamento (validar com o produto se é o desejado) | Borda | P2 |
| CT-ORC-008 | Prazo após erro de validação (**R10**) | Novo orçamento: digitar um prazo à mão, deixar o Vendedor vazio › Salvar | Erro do vendedor. **Esperado:** prazo digitado preservado. **Hipótese:** o prazo volta para a sugestão do nível | Negativo | P2 |
| CT-ORC-009 | Perguntas pelo tipo | No O1 › aba Perguntas › responder `DEMO Potência desejada (kW)` e `DEMO Prazo de instalação` › Salvar › reabrir | Mostra exatamente as perguntas do DEMO Modelo Comercial; respostas mantidas | Positivo | P1 |
| CT-ORC-010 | Tipo sem modelo | Trocar o Tipo de sistema para `DEMO Sem modelo` › aba Perguntas; voltar para DEMO Caldeira sem salvar | Sem perguntas para o tipo sem modelo; ao voltar, as respostas anteriores reaparecem | Borda | P3 |
| CT-ORC-011 | Vendedor responsável repetido | Aba Vendedores Adicionais › incluir `qa.comercial` (que já é o responsável) › Salvar | **"O vendedor responsável não pode ser selecionado também como vendedor adicional."** | Negativo | P2 |
| CT-ORC-012 | Resultado (**R16**) | Definir Resultado "Convertido" › Salvar; tentar voltar para "Em aberto" | Convertido salvo e visível na coluna da lista. **Hipótese R16:** não é possível voltar para "Em aberto" pela tela | Borda | P3 |
| CT-ORC-013 | Comentário com alerta | Aba Comentários › Novo comentário › "Alertar usuário (opcional)" = `qa.admin` › Salvar; logar como `qa.admin` e abrir o sino | Comentário listado com autor e data; a tela volta **na aba Comentários**; `qa.admin` recebe **"Novo comentário adicionado no orçamento Nº {id}. Incluído por {nome}."** | Positivo | P1 |
| CT-ORC-014 | Excluir comentário de outro autor (**R15**) | Como `qa.admin`, excluir o comentário feito por `qa.comercial` | **Hipótese R15:** exclui sem pedir confirmação e sem checar o autor. Registrar | Permissão | P2 |
| CT-ORC-015 | Comentário vazio | Salvar comentário vazio | **"Escreva o comentário antes de salvar."** | Negativo | P2 |
| CT-ORC-016 | Massa para Indicadores | Criar O2 a O5 conforme a tabela de massa | 5 orçamentos de `qa.comercial` no período de hoje, com resultados variados. Anotar os números | Positivo | P1 |
| CT-ORC-017 | Filtros e cliente inativo | Filtrar por cliente, vendedor, tipo e nível; no cadastro, buscar `DEMO Destilaria` no autocomplete de cliente | Filtros funcionam e persistem; o cliente inativo C4 **não** aparece no autocomplete | Positivo | P3 |

## Roteiros de Viagem

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-ROT-001 | Criar roteiro | Roteiro de Viagem › Cadastrar › Vendedor `qa.comercial` › Período de hoje a hoje+7 › Link do Google Maps › "Adicionar cliente" C1 e C2 › Salvar | Roteiro criado com situação **Não iniciada**; na lista, status "Aguardando retorno" | Positivo | P1 |
| CT-ROT-002 | Roteiro sem cliente | Salvar sem nenhum cliente | **"Adicione ao menos um cliente ao roteiro."** | Negativo | P1 |
| CT-ROT-003 | Período invertido | Fim anterior ao início | Erro de data final antes da inicial | Negativo | P2 |
| CT-ROT-004 | Registrar retorno | Editar R1 › Situação da viagem "Em andamento" › C1 = Visitado (com observação), C2 sem resultado › Salvar; depois C2 = Não realizado › Salvar | Lista mostra "Retorno parcial (1/2)" e depois "Retorno completo" | Positivo | P1 |
| CT-ROT-005 | Abrir o link do mapa | Clicar no botão ao lado do link | Abre o link em nova aba | Positivo | P3 |
| CT-ROT-006 | Mesmo cliente duas vezes (**R09**) | Novo roteiro com C1 adicionado duas vezes › Salvar | **Esperado:** mensagem de cliente repetido. **Hipótese R09:** erro 500 | Negativo | P2 |
| CT-ROT-007 | Lista após erro de validação (**R09**) | Novo roteiro com C1 e C2 e período invertido › Salvar | **Esperado:** mensagem de erro com os clientes preservados. **Hipótese R09:** a lista de clientes volta vazia | Negativo | P2 |
| CT-ROT-008 | Cancelado e filtros | Criar R2 e mudar a situação para Cancelada; filtrar por vendedor e por data | R2 em vermelho na lista; filtros funcionam | Positivo | P3 |

---

## Pontos de atenção

- **R10** (CT-ORC-007/008), **R15** (CT-ORC-014), **R16** (CT-ORC-012), **R09** (CT-ROT-006/007).
- Não há restrição por dono no CRM: qualquer perfil com acesso a Orçamentos vê e edita **todos** os orçamentos. Isso é a regra atual, não bug, mas cite no relatório final para o produto validar.
- O alerta de comentário com destinatário que só tem "Ver" de Orçamentos (**R12**) é testado no D6 (CT-NOT-005).

---

## Registro de execução — D5

Números criados: O1 `____` · O2 `____` · O3 `____` · O4 `____` · O5 `____` · R1 `____`

| ID | Status | Vídeo + tempo | Bug | Data / QA |
|---|---|---|---|---|
| CT-ORC-001 | | | | |
| CT-ORC-002 | | | | |
| CT-ORC-003 | | | | |
| CT-ORC-004 | | | | |
| CT-ORC-005 | | | | |
| CT-ORC-006 | | | | |
| CT-ORC-007 | | | | |
| CT-ORC-008 | | | | |
| CT-ORC-009 | | | | |
| CT-ORC-010 | | | | |
| CT-ORC-011 | | | | |
| CT-ORC-012 | | | | |
| CT-ORC-013 | | | | |
| CT-ORC-014 | | | | |
| CT-ORC-015 | | | | |
| CT-ORC-016 | | | | |
| CT-ORC-017 | | | | |
| CT-ROT-001 | | | | |
| CT-ROT-002 | | | | |
| CT-ROT-003 | | | | |
| CT-ROT-004 | | | | |
| CT-ROT-005 | | | | |
| CT-ROT-006 | | | | |
| CT-ROT-007 | | | | |
| CT-ROT-008 | | | | |
