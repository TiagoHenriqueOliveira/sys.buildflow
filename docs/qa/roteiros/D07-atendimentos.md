# D7 — Assistência: Atendimentos

[← Índice](../00-indice.md) · Anterior: [D6](D06-crm-mapas-indicadores-notificacoes.md) · Próximo: [D8 — Relatórios: criação e preenchimento](D08-relatorios-preenchimento.md)

| | |
|---|---|
| **Objetivo** | Validar o cadastro de Atendimentos (abas Dados, Observações, Equipamentos, Anexos, Relatórios), suas validações, status e a regra de **posse** (técnico só vê os seus). Criar os atendimentos A1–A4 usados nos relatórios |
| **Por que agora** | Relatório só nasce a partir de um Atendimento cuja natureza tenha modelo (D3) e cujo cliente exista (D4) |
| **Duração** | ~6 h |
| **Perfis** | `qa.admin` (cadastra), `qa.tecnico1`, `qa.tecnico2`, `qa.assistencia` |
| **Pré-condições** | D3 e D4 concluídos; arquivos de teste em `C:\QA-arquivos\` |

**Quem cadastra:** na matriz desta rodada, só o Administrador tem **Atendimentos › Editar**. Técnico e Assistência só visualizam.

## Massa criada neste dia

| Atendimento | Natureza | Cliente | Técnico | Status | Período |
|---|---|---|---|---|---|
| A1 | DEMO Manutenção corretiva | C1 | qa.tecnico1 | Não iniciada | hoje → hoje+5 |
| A2 | DEMO Manutenção corretiva | C2 | qa.tecnico2 | Não iniciada | hoje → hoje+5 |
| A3 | DEMO Manutenção corretiva | C3 | qa.tecnico1 | Paralisada | 10 dias atrás → ontem |
| A4 | DEMO Manutenção corretiva | C2 | qa.tecnico1 | Concluída | 5 dias atrás → hoje |

**Antes de criar o A3:** dê localização ao C3, simulando Curitiba (-25.4284, -49.2733) como no D4. O teste de "cliente sem localização" já foi feito no D6. Assim cada atendimento do Técnico 1 cai num ponto diferente do Mapa de Demandas (D9).

## Vídeos

| Vídeo | Casos |
|---|---|
| `D07_CT-ATE-001-006_atendimentos-cadastro_...mp4` | Cadastro e validações |
| `D07_CT-ATE-007-011_atendimentos-abas_...mp4` | Observações, equipamentos, entrega técnica, anexos |
| `D07_CT-ATE-012-016_atendimentos-status-posse_...mp4` | Status, massa, posse |
| `D07_CT-ATE-017-020_atendimentos-lista-historico_...mp4` | Listagem, filtros, histórico do cliente |

---

## Bloco 1 — Cadastro e validações (aba Dados)

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-ATE-001 | Cadastrar A1 | Atendimentos › Cadastrar › Natureza, Cliente C1 (autocomplete), Técnico `qa.tecnico1`, Início, Fim, Status "Não iniciada", Endereço por extenso, Nº Proposta › Salvar | Salva sem recarregar a página; a URL passa para `/atendimentos/{id}/edit`; as outras abas são liberadas | Positivo | P1 |
| CT-ATE-002 | Abas travadas antes de salvar | Em um cadastro novo, tentar clicar em Observações, Equipamentos, Anexos e Relatórios | Abas desabilitadas até o primeiro salvamento | Positivo | P2 |
| CT-ATE-003 | Obrigatórios | Salvar sem Natureza, Cliente, Técnico e datas | Erro em cada campo obrigatório; nada é criado | Negativo | P1 |
| CT-ATE-004 | Período invertido | Fim antes do Início | Erro de data final anterior à inicial | Negativo | P2 |
| CT-ATE-005 | Endereço com link | Colar `https://maps.app.goo.gl/abc` no Endereço | **"Informe o endereço por extenso; links do Google Maps não são aceitos neste campo."** | Negativo | P2 |
| CT-ATE-006 | Lista de técnicos | Abrir o select Técnico | Só usuários do perfil **Técnico** (qa.tecnico1, qa.tecnico2) | Positivo | P3 |

## Bloco 2 — Observações, Equipamentos, Entrega técnica e Anexos

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-ATE-007 | Observações | Aba Observações › preencher › salvar | Salva; a aba ganha o "✔" | Positivo | P2 |
| CT-ATE-008 | Equipamentos | Adicionar 2 equipamentos; tentar um sem descrição; excluir 1 | Sem descrição dá erro; a exclusão remove na hora (anotar se **não** pede confirmação — R15) | Positivo | P2 |
| CT-ATE-009 | Desmarcar "Entrega Téc." (**R07**) | Marcar "Entrega Téc." › Salvar › recarregar; desmarcar › Salvar › recarregar | Marcado fica "Sim". **Esperado** ao desmarcar: "Não". **Hipótese R07:** volta para "Sim". Deixar A1 **marcado** no fim (o PDF do D9 usa) | Negativo | P2 |
| CT-ATE-010 | Anexos aceitos | Aba Anexos › enviar `foto_ok.jpg`, `documento.pdf`, `planilha.xlsx`, `video_curto.mp4`, `foto_15mb.jpg` | Todos aceitos (limite 20 MB por arquivo); nome original exibido; abrir cada um funciona | Positivo | P1 |
| CT-ATE-011 | Anexos recusados | Enviar `arquivo_25mb.pdf` e `compactado.zip` | Mensagens de tamanho máximo e de tipo não permitido; nada é gravado | Negativo | P2 |

## Bloco 3 — Status, massa e posse

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-ATE-012 | Concluída sem relatório aprovado (**R04**) | Criar A4 já com Status "Concluída" (não há relatório) › Salvar | **Esperado** (regra da API do app): bloquear a conclusão sem relatório aprovado. **Hipótese R04:** a Web aceita. Registrar e validar a regra com o produto. Se a Web bloquear, crie o A4 como "Não iniciada" e marque R04 como "Não reproduzido" | Negativo | P1 |
| CT-ATE-013 | Criar A2 e A3 | Conforme a tabela de massa | Salvos; A3 com período vencido aparece com o período **em vermelho** na lista | Positivo | P1 |
| CT-ATE-014 | Técnico vê só os seus | Logar como `qa.tecnico1` › Atendimentos | Vê A1, A3, A4; **não** vê A2; sem botão Cadastrar | Permissão | P1 |
| CT-ATE-015 | Técnico não abre atendimento de outro | Como `qa.tecnico1`, abrir na barra de endereço `/atendimentos/{id do A2}/observacoes` | 403 com "Você não tem acesso a este atendimento." | Permissão | P1 |
| CT-ATE-016 | Assistência vê todos | Logar como `qa.assistencia` › Atendimentos | Vê A1 a A4 | Permissão | P2 |

## Bloco 4 — Listagem, filtros e histórico do cliente

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-ATE-017 | Ordem e destaques da lista | Como `qa.admin`, observar a lista | Ordenada por status, início e técnico; período vencido em vermelho só quando o status não é Concluída | Positivo | P3 |
| CT-ATE-018 | Filtros | Filtrar por natureza, técnico, cliente, proposta, período e status; sair e voltar | Filtros funcionam e persistem | Positivo | P3 |
| CT-ATE-019 | Histórico do cliente | Abrir Clientes › C1 › aba Histórico | Lista o atendimento A1 e os orçamentos O1/O3, do mais recente para o mais antigo, com links (relatórios não entram no histórico do cliente) | Positivo | P2 |
| CT-ATE-020 | Aba Relatórios vazia | Abrir A1 › aba Relatórios | Lista vazia (será preenchida no D8) | Positivo | P3 |

---

## Pontos de atenção

- **R04** (CT-ATE-012) e **R07** (CT-ATE-009).
- **R03**, posse na **gravação** de atendimento: a tela de edição bloqueia, mas o `PUT` direto talvez não. Testado no D10 (CT-SEC-004).
- O cadastro de Atendimento grava **auditoria** (CRIAR/EDITAR), conferida no D10 (CT-AUD-002).
- IDs inválidos enviados à força (natureza ou cliente inexistente) geram erro 500 genérico. Só é alcançável fora da tela; não é prioridade desta rodada.

---

## Registro de execução — D7

IDs criados: A1 `____` · A2 `____` · A3 `____` · A4 `____`

| ID | Status | Vídeo + tempo | Bug | Data / QA |
|---|---|---|---|---|
| CT-ATE-001 | | | | |
| CT-ATE-002 | | | | |
| CT-ATE-003 | | | | |
| CT-ATE-004 | | | | |
| CT-ATE-005 | | | | |
| CT-ATE-006 | | | | |
| CT-ATE-007 | | | | |
| CT-ATE-008 | | | | |
| CT-ATE-009 | | | | |
| CT-ATE-010 | | | | |
| CT-ATE-011 | | | | |
| CT-ATE-012 | | | | |
| CT-ATE-013 | | | | |
| CT-ATE-014 | | | | |
| CT-ATE-015 | | | | |
| CT-ATE-016 | | | | |
| CT-ATE-017 | | | | |
| CT-ATE-018 | | | | |
| CT-ATE-019 | | | | |
| CT-ATE-020 | | | | |
