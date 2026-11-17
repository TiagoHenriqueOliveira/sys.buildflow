# D9 — Assistência: Aprovação do relatório, PDF e Mapa de Demandas

[← Índice](../00-indice.md) · Anterior: [D8](D08-relatorios-preenchimento.md) · Próximo: [D10 — Transversais e segurança](D10-transversais-seguranca.md)

| | |
|---|---|
| **Objetivo** | Validar o fluxo de status do relatório (BF10: Preenchendo → Revisar → Liberado para aprovação → Aprovado), as assinaturas, a notificação para a Assistência, o modo somente leitura, o PDF (BF06) e o Mapa de Demandas (BF08) |
| **Por que agora** | É o fim do fluxo crítico do cliente e precisa de um relatório preenchido (D8) |
| **Duração** | ~6 h |
| **Perfis** | `qa.tecnico1`, `qa.tecnico2`, `qa.assistencia`, `qa.admin` |
| **Pré-condições** | D8 concluído: relatório do A1 (técnico 1) e relatório do A2 (técnico 2), ambos em Preenchendo |

## Regras do fluxo (resumo para a wiki)

```
Preenchendo ⇄ Revisar ⇄ Liberado para aprovação ──(Assistência/Admin)──► Aprovado
                           ▲ exige assinatura do técnico
```

- **Técnico:** move livremente entre Preenchendo, Revisar e Liberado. Para **liberar**, precisa ter a assinatura do técnico.
- **Aprovar:** só **Assistência** ou **Administrador**, e só a partir de **Liberado**. Esta regra é fixa por perfil: não depende da tela Perfis de Acesso.
- **Ao liberar:** todos os usuários **Assistência ativos** recebem notificação, menos quem liberou. Administradores não recebem.
- **Aprovado:** a tela fica somente leitura, com a faixa "Este relatório já foi **aprovado** e está somente para leitura."

## Vídeos

| Vídeo | Casos |
|---|---|
| `D09_CT-APR-001-007_fluxo-liberacao_...mp4` | Status, assinatura, liberação, notificação |
| `D09_CT-APR-008-014_aprovacao-somente-leitura_...mp4` | Aprovação, regras e somente leitura |
| `D09_CT-PDF-001-007_pdf_...mp4` | Conteúdo do PDF |
| `D09_CT-MDE-001-004_mapa-demandas_...mp4` | Mapa de Demandas |

---

## Bloco 1 — Fluxo até a liberação (relatório do A1, `qa.tecnico1`)

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-APR-001 | Preenchendo → Revisar | Aba Assinatura › Status do Relatório "Revisar" › Atualizar | Status salvo; o selo na lista de relatórios muda | Positivo | P1 |
| CT-APR-002 | Liberar sem assinatura | Escolher "Liberado para aprovação" sem ter assinado › Atualizar | **"Para liberar para aprovação, registre antes a assinatura do técnico."**; o status continua Revisar | Negativo | P1 |
| CT-APR-003 | Botão Aprovado oculto para o técnico | Observar os botões de status | Só Preenchendo, Revisar e Liberado para aprovação, com o texto de ajuda "Para liberar para aprovação é preciso ter a assinatura do técnico…" | Permissão | P1 |
| CT-APR-004 | Voltar de Liberado | Assinar (Assinatura Técnico › desenhar `DEMO` › Salvar Assinatura); liberar; voltar para Preenchendo; liberar de novo | Transições livres entre os três status do técnico | Positivo | P3 |
| CT-APR-005 | Liberar com assinatura | Com a assinatura do técnico registrada, escolher "Liberado para aprovação" › Atualizar | Status **Liberado para aprovação** | Positivo | P1 |
| CT-APR-006 | Notificação para a Assistência | Abrir o sino como `qa.assistencia`, como `qa.admin` e como `qa.tecnico1` | **Só** `qa.assistencia` recebe a notificação de relatório liberado | Positivo | P1 |
| CT-APR-007 | Abrir pela notificação | Como `qa.assistencia`, clicar na notificação | Abre a tela do relatório do A1 | Positivo | P1 |

## Bloco 2 — Aprovação e somente leitura

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-APR-008 | Aprovar fora de Liberado | Como `qa.assistencia`, abrir o relatório do **A2** (em Preenchendo) › Aprovado › Atualizar | **"O relatório precisa estar "Liberado para aprovação" antes de ser aprovado."** | Negativo | P1 |
| CT-APR-009 | Assinatura do cliente | No relatório do A1: Assinatura Cliente › desenhar › Salvar Assinatura sem nome/CPF; depois com Nome de quem assinou e CPF | Sem nome/CPF dá erro; com os dois, salva | Negativo | P2 |
| CT-APR-010 | CPF sem validação (**R19**) | Salvar a assinatura do cliente com CPF `123` | **Esperado:** recusar. **Hipótese R19:** aceita. Registrar | Negativo | P3 |
| CT-APR-011 | Observação do Supervisor | Como `qa.assistencia`, preencher a Observação do Supervisor; abrir como `qa.tecnico1` | A Assistência grava; para o técnico o campo é somente leitura | Permissão | P2 |
| CT-APR-012 | Aprovar | Como `qa.assistencia`, status Aprovado › Atualizar | Status **Aprovado**; faixa "Este relatório já foi aprovado e está somente para leitura. Aprovado por {nome} em {data/hora}." | Positivo | P1 |
| CT-APR-013 | Somente leitura | Como `qa.tecnico1`, abrir o relatório aprovado e percorrer todas as abas | Campos desabilitados; botão Atualizar oculto; sem botões de adicionar ou excluir | Permissão | P1 |
| CT-APR-014 | Administrador também aprova | `qa.tecnico2` assina e libera o relatório do A2; `qa.admin` aprova | Aprovação aceita para o Administrador | Positivo | P2 |

## Bloco 3 — PDF do relatório

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-PDF-001 | Abrir o PDF | Relatórios › "Gerar PDF" do relatório do A1 (e também pela aba Relatórios do A1) | Abre no navegador; ao baixar, o nome é `relatorio_{id}_{data}.pdf` | Positivo | P1 |
| CT-PDF-002 | Conteúdo principal | Ler o PDF inteiro | Dados do cliente e do atendimento; **uma linha por dia** de horário (os 2 dias do D8); perguntas respondidas com as respostas certas; foto da resposta; fotos dos anexos | Positivo | P1 |
| CT-PDF-003 | O que **não** pode sair | Procurar no PDF a observação interna, a observação do supervisor, o status e o hash | Nenhum desses aparece (em especial "DEMO nota interna — não pode ir ao PDF") | Negativo | P1 |
| CT-PDF-004 | Pergunta sem resposta | Conferir que uma pergunta deixada em branco (se houver) não aparece | Só perguntas respondidas aparecem | Positivo | P2 |
| CT-PDF-005 | Marca no bloco Entrega Técnica (**R17**) | Com "Entrega Téc." marcada no A1, ler o bloco Entrega Técnica | **Esperado:** texto da FAÉ ou neutro. **Hipótese R17:** "A MCLvale, neste ato representado pelo técnico…". Registrar como bug de conteúdo | Negativo | P2 |
| CT-PDF-006 | Data da assinatura (**R17**) | Procurar a data/hora das assinaturas | **Hipótese:** não aparece. Validar com o produto se deveria | Borda | P3 |
| CT-PDF-007 | PDF de outro técnico | Como `qa.tecnico2`, abrir a URL do PDF do relatório do A1 | 403 "Você não tem acesso a este relatório." | Permissão | P1 |

## Bloco 4 — Mapa de Demandas

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-MDE-001 | Técnico vê só os seus | `qa.tecnico1` › Mapa de Demandas | Pontos do A1 (Ribeirão Preto), A3 (Curitiba) e A4 (Belo Horizonte); **sem** o A2 | Permissão | P1 |
| CT-MDE-002 | Assistência e Admin veem todos | `qa.assistencia` › Mapa de Demandas | Pontos de A1 a A4 (A2 e A4 ficam no mesmo ponto, pois são do mesmo cliente C2) | Permissão | P2 |
| CT-MDE-003 | Cores por status | Comparar a cor de cada ponto com o status do atendimento | Cinza = Não iniciada · Amarelo = Paralisada · Azul = Em andamento · Verde = Concluída | Positivo | P2 |
| CT-MDE-004 | Filtro de técnico | Abrir o filtro "Técnico" | **Observação:** lista todos os usuários ativos, não só técnicos. Registrar como melhoria de UX se confundir | Borda | P3 |

---

## Pontos de atenção

- **R17** (CT-PDF-005/006) e **R19** (CT-APR-010).
- O teste de gravação **direta** num relatório aprovado (**R01**) fica para o D10 (CT-SEC-006). Hoje só se testa a tela.
- **Não há transação:** se o envio da assinatura falhar, o status pode já ter mudado. Se aparecer erro 500 ao salvar a assinatura, confira o status depois de recarregar e anote no bug.
- A tela não oferece caminho para "desaprovar" um relatório. Se o produto precisar, é melhoria, não bug.

---

## Registro de execução — D9

| ID | Status | Vídeo + tempo | Bug | Data / QA |
|---|---|---|---|---|
| CT-APR-001 | | | | |
| CT-APR-002 | | | | |
| CT-APR-003 | | | | |
| CT-APR-004 | | | | |
| CT-APR-005 | | | | |
| CT-APR-006 | | | | |
| CT-APR-007 | | | | |
| CT-APR-008 | | | | |
| CT-APR-009 | | | | |
| CT-APR-010 | | | | |
| CT-APR-011 | | | | |
| CT-APR-012 | | | | |
| CT-APR-013 | | | | |
| CT-APR-014 | | | | |
| CT-PDF-001 | | | | |
| CT-PDF-002 | | | | |
| CT-PDF-003 | | | | |
| CT-PDF-004 | | | | |
| CT-PDF-005 | | | | |
| CT-PDF-006 | | | | |
| CT-PDF-007 | | | | |
| CT-MDE-001 | | | | |
| CT-MDE-002 | | | | |
| CT-MDE-003 | | | | |
| CT-MDE-004 | | | | |
