# D8 — Assistência: Relatórios (criação e preenchimento)

[← Índice](../00-indice.md) · Anterior: [D7](D07-atendimentos.md) · Próximo: [D9 — Aprovação e PDF](D09-relatorios-aprovacao-pdf.md)

| | |
|---|---|
| **Objetivo** | Validar a criação do relatório (bloqueios REL-02), as abas geradas pelo modelo do Configurador, horários por dia, perguntas e sessões, respostas repetíveis, fotos, observações e o comprovante de compartilhamento (BF07, BF11) |
| **Por que agora** | É o coração do sistema para o cliente e depende de tudo o que veio antes: perfil, modelo, natureza, cliente, atendimento |
| **Duração** | ~6 h |
| **Perfis** | `qa.tecnico1` (principal), `qa.tecnico2` |
| **Pré-condições** | D7 concluído (A1–A4); arquivos de teste à mão |

## Abas esperadas para o `DEMO Modelo Assistência`

| Aba | De onde vem |
|---|---|
| Dados | Fixa |
| Horário | Fixa (por dia) |
| Perguntas | Perguntas "Sem sessão (geral)": `DEMO Serviço executado`, depois `DEMO Condição geral do equipamento` (ordem do CT-MOD-003) |
| DEMO Inspeção Elétrica | Sessão: `DEMO Equipamento operando?` e `DEMO Itens verificados` |
| Observações Gerais | Fixa |
| Anexos | Fixa (só fotos) |
| Observação Interna | Fixa (nunca vai para o PDF) |
| Compartilhamento | Fixa |
| Assinatura | Fixa (status e assinaturas, testada no D9) |

Abas **legadas** (Clima, Descrição, Serviços Prestados, Peças Substituídas, Ocorrências) só aparecem em relatórios antigos que já têm esses dados. **Não devem** aparecer num relatório novo.

## Vídeos

| Vídeo | Casos |
|---|---|
| `D08_CT-REL-001-007_relatorio-criacao-abas_...mp4` | Criação, bloqueios, abas, Dados |
| `D08_CT-REL-008-014_relatorio-horarios_...mp4` | Horário por dia |
| `D08_CT-REL-015-019_relatorio-perguntas-sessoes_...mp4` | Perguntas, sessão, repetível, fotos de resposta |
| `D08_CT-REL-020-027_relatorio-obs-anexos-compartilhamento_...mp4` | Observações, anexos, observação interna, compartilhamento, listagem |

---

## Bloco 1 — Criação, bloqueios e abas

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-REL-001 | Criar relatório | Como `qa.tecnico1`: Relatórios › Cadastrar › digitar 2+ letras e escolher A1 › data de hoje › salvar | Abre a tela do relatório, status **Preenchendo** | Positivo | P1 |
| CT-REL-002 | Atendimento muda de status | Abrir a lista de Atendimentos | A1 passou de "Não iniciada" para **"Em andamento"** | Positivo | P1 |
| CT-REL-003 | Atendimento Paralisado ou Concluído (REL-02) | No modal de novo relatório, buscar A3 (Paralisada) e A4 (Concluída) | Não aparecem no autocomplete; não é possível criar relatório para eles | Negativo | P1 |
| CT-REL-004 | Data futura | Novo relatório com data de amanhã | Erro: data não pode ser futura | Negativo | P2 |
| CT-REL-005 | Autocomplete mostra atendimento de outro técnico (**R05**) | No modal, buscar pelo cliente C2 (o A2 é do `qa.tecnico2`) e tentar salvar | **Esperado:** A2 não aparece para o técnico 1. **Hipótese R05:** aparece; ao salvar, dá 403. Registrar | Permissão | P1 |
| CT-REL-006 | Abas do modelo | Abrir o relatório criado | Exatamente as abas da tabela acima, nessa ordem; nenhuma aba legada | Positivo | P1 |
| CT-REL-007 | Aba Dados | Observar a aba Dados; alterar a data para ontem › Atualizar | Só a data é editável; cliente, contato, responsável, proposta, endereço, cidade/UF em somente leitura; equipamentos e anexos do atendimento visíveis; data salva | Positivo | P2 |

## Bloco 2 — Horário por dia

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-REL-008 | Dia válido | Aba Horário › entrada 08:00, início do intervalo 12:00, fim do intervalo 13:00, saída 17:00 › Atualizar | Mensagem de sucesso; ao recarregar, os horários continuam | Positivo | P1 |
| CT-REL-009 | Saída antes da entrada | Entrada 17:00, saída 08:00 | Erro indicando a data no formato "(dd/mm/aaaa)" | Negativo | P1 |
| CT-REL-010 | Intervalo incompleto | Só o início do intervalo, sem o fim | Erro de intervalo incompleto | Negativo | P2 |
| CT-REL-011 | Intervalo fora do expediente | Início do intervalo 07:00 com entrada 08:00 | Erro de intervalo fora do horário de trabalho | Negativo | P2 |
| CT-REL-012 | Adicionar dia e data repetida | "Adicionar dia"; depois mudar a data do novo dia para a mesma do primeiro | O novo dia vem com o dia seguinte (sem passar de hoje) e com os horários copiados; data repetida dá **"Há dias repetidos nos horários."** | Negativo | P2 |
| CT-REL-013 | Dia no futuro | Mudar a data de um dia para amanhã | Erro: data não pode ser futura | Negativo | P2 |
| CT-REL-014 | Último dia não sai | Remover dias até sobrar um | O botão "Remover dia" não remove o último | Borda | P3 |

Deixe o relatório com **2 dias válidos** (ontem e hoje), que aparecem no PDF do D9.

## Bloco 3 — Perguntas, sessão e fotos de resposta

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-REL-015 | Texto livre com foto | Aba Perguntas › `DEMO Condição geral do equipamento` › texto + `foto_ok.jpg` › salvar a resposta | Resposta e miniatura da foto aparecem; continuam após recarregar | Positivo | P1 |
| CT-REL-016 | Escolha única e múltipla na sessão | Aba DEMO Inspeção Elétrica › `Equipamento operando?` = Sim; `Itens verificados` = Painel + Motor › salvar | Respostas salvas e mantidas após recarregar | Positivo | P1 |
| CT-REL-017 | Pergunta repetível | Aba Perguntas › `DEMO Serviço executado` › responder › "Adicionar outra resposta" › responder de novo › remover uma das duas | Ficam N respostas independentes; a removida some | Positivo | P1 |
| CT-REL-018 | Pergunta não repetível não duplica | Responder de novo `Equipamento operando?` = Não | A resposta é **atualizada** (continua uma só) | Positivo | P2 |
| CT-REL-019 | Foto inválida na resposta | Anexar `foto_15mb.jpg`; depois `falso.jpg` | Ambos recusados (limite de 10 MB; arquivo não é imagem) | Negativo | P2 |

## Bloco 4 — Observações, Anexos, Observação Interna, Compartilhamento

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-REL-020 | Observações Gerais | Preencher › Atualizar › recarregar | Texto mantido | Positivo | P2 |
| CT-REL-021 | Fotos aceitas | Aba Anexos › enviar `foto_ok.jpg`, `foto_ok.png`, `foto.webp`, `foto.gif` › Enviar; excluir uma | Fotos listadas com miniatura (a webp pode aparecer sem miniatura reduzida); a excluída some | Positivo | P1 |
| CT-REL-022 | Anexos recusados | Enviar `foto_15mb.jpg` e `compactado.zip` | Recusados (só fotos, até 10 MB) | Negativo | P2 |
| CT-REL-023 | Observação Interna | Preencher `DEMO nota interna — não pode ir ao PDF` › Atualizar | Salva (a ausência no PDF é conferida no CT-PDF-003) | Positivo | P2 |
| CT-REL-024 | Comprovante de compartilhamento | Aba Compartilhamento › "Compartilhar" duas vezes | Cada clique cria uma linha com Data/Hora, Canal "painel-web" e um **hash diferente** | Positivo | P2 |
| CT-REL-025 | Aba Relatórios do atendimento | Como `qa.admin`, abrir A1 › aba Relatórios | O relatório aparece com status e os links "Abrir relatório" e "Abrir PDF" | Positivo | P2 |
| CT-REL-026 | Listagem por técnico | `qa.tecnico2` cria um relatório para o A2 (usado no D9) e abre a lista de Relatórios; `qa.tecnico1` abre a lista | Cada técnico vê **só** os próprios relatórios; filtros funcionam | Permissão | P1 |
| CT-REL-027 | Botão "Atualizar" em aba de lista | Na aba Anexos, clicar em Atualizar | Aviso "Use os botões para adicionar e remover itens nesta aba." | Positivo | P3 |

---

## Pontos de atenção

- **R05** (CT-REL-005).
- **R01**, relatório aprovado ainda gravável, é testado no D10 (CT-SEC-006), depois da aprovação do D9.
- Não há resposta obrigatória: o relatório pode ser liberado com perguntas em branco. Isso é a regra atual; cite no relatório final para o produto validar.
- Exclusões (resposta, foto) **não pedem confirmação** (R15). Anote em vídeo.

---

## Registro de execução — D8

IDs criados: relatório do A1 `____` · relatório do A2 `____`

| ID | Status | Vídeo + tempo | Bug | Data / QA |
|---|---|---|---|---|
| CT-REL-001 | | | | |
| CT-REL-002 | | | | |
| CT-REL-003 | | | | |
| CT-REL-004 | | | | |
| CT-REL-005 | | | | |
| CT-REL-006 | | | | |
| CT-REL-007 | | | | |
| CT-REL-008 | | | | |
| CT-REL-009 | | | | |
| CT-REL-010 | | | | |
| CT-REL-011 | | | | |
| CT-REL-012 | | | | |
| CT-REL-013 | | | | |
| CT-REL-014 | | | | |
| CT-REL-015 | | | | |
| CT-REL-016 | | | | |
| CT-REL-017 | | | | |
| CT-REL-018 | | | | |
| CT-REL-019 | | | | |
| CT-REL-020 | | | | |
| CT-REL-021 | | | | |
| CT-REL-022 | | | | |
| CT-REL-023 | | | | |
| CT-REL-024 | | | | |
| CT-REL-025 | | | | |
| CT-REL-026 | | | | |
| CT-REL-027 | | | | |
