# D6 — CRM: Mapa de Relações, Indicadores Comerciais e Notificações

[← Índice](../00-indice.md) · Anterior: [D5](D05-crm-orcamentos-roteiros.md) · Próximo: [D7 — Atendimentos](D07-atendimentos.md)

| | |
|---|---|
| **Objetivo** | Conferir que as telas de acompanhamento mostram exatamente a massa criada em D4/D5 (CRM07, CRM08) e validar o sino de notificações e o alerta de recontato |
| **Por que agora** | São telas de **leitura**: só dá para validar números e pontos depois que os dados existem. Usar massa controlada permite comparar o que a tela mostra com uma conta feita à mão |
| **Duração** | ~4 h |
| **Perfis** | `qa.comercial`, `qa.admin`, `qa.vendedor` |
| **Pré-condições** | D4 e D5 concluídos; acesso ao terminal na pasta do projeto (para o comando de recontato) |

## Conta esperada para os Indicadores (preencher antes de abrir a tela)

Considerando o período de hoje e o vendedor `qa.comercial`:

| Indicador | Regra | Esperado |
|---|---|---|
| Propostas levantadas | Todos os orçamentos criados no período em que ele é o **vendedor responsável** (adicionais não contam) | O1–O5 + o do CT-ORC-005 = **6** |
| Propostas fechadas | Resultado "Convertido" | O1, O2 = **2** |
| Taxa de conversão | Convertido ÷ (Convertido + Não convertido) × 100, arredondado. Adiado, Projeto futuro e Em aberto **ficam fora** | 2 ÷ (2 + 1) = **67%** |
| Clientes visitados | Clientes distintos com "Visitado" em roteiros que cruzam o período | C1 = **1** |

Se houver outros orçamentos de `qa.comercial` no banco (de outras rodadas), ajuste a conta antes de gravar.

## Vídeos

| Vídeo | Casos |
|---|---|
| `D06_CT-MRE-001-005_mapa-relacoes_...mp4` | Mapa de Relações |
| `D06_CT-IND-001-005_indicadores_...mp4` | Indicadores Comerciais |
| `D06_CT-NOT-001-008_notificacoes-recontato_...mp4` | Sino e recontato |

---

## Mapa de Relações

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-MRE-001 | Pontos no mapa | Abrir Mapa de Relações sem filtro | Pontos em Ribeirão Preto (C1) e Belo Horizonte (C2); **C3 não aparece** (sem localização); o mapa enquadra todos os pontos | Positivo | P1 |
| CT-MRE-002 | Popup do cliente | Clicar no ponto do C1 | Mostra nome, vendedor, classificação, segmento, equipamentos e o selo "Caso de sucesso" com a descrição | Positivo | P2 |
| CT-MRE-003 | Filtros | Filtrar por Vendedor, UF (`MG`), Segmento, Classificação; sair e voltar | Só os pontos que atendem ao filtro; filtro persiste | Positivo | P2 |
| CT-MRE-004 | Cliente inativo (**R20**) | Procurar Goiânia (C4, inativo) no mapa | **Esperado:** inativo não aparece. **Hipótese R20:** aparece. Registrar e validar a regra com o produto | Borda | P2 |
| CT-MRE-005 | Texto do popup é escapado | Renomear C2 para `DEMO <b>Beta</b> <img src=x onerror=alert(1)>` › abrir o popup › depois **restaurar o nome** | Popup mostra o texto **literal**, sem negrito e sem alerta do navegador | Permissão | P1 |

## Indicadores Comerciais

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-IND-001 | Propostas levantadas | Abrir Indicadores com o período de hoje | Linha de `qa.comercial` com o número da tabela de conta | Positivo | P1 |
| CT-IND-002 | Fechadas e taxa de conversão | Mesma tela | Fechadas = 2; taxa = 67% | Positivo | P1 |
| CT-IND-003 | Clientes visitados | Mesma tela | 1 | Positivo | P2 |
| CT-IND-004 | Período sem dados | Filtrar um período no ano passado | Zeros e "—" na taxa (sem erro de divisão por zero) | Borda | P2 |
| CT-IND-005 | Resultado fora da conta | Mudar O4 de Adiado para Não convertido; voltar aos Indicadores; depois **restaurar** para Adiado | Taxa passa a 2 ÷ 4 = 50%; volta para 67% depois de restaurar | Positivo | P3 |

## Notificações (sino) e alerta de recontato

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-NOT-001 | Contador e lista | Como `qa.admin` (que recebeu o alerta do CT-ORC-013), abrir o sino | Contador de não lidas; lista com as 10 mais recentes, com hora relativa | Positivo | P2 |
| CT-NOT-002 | Abrir notificação | Clicar na notificação do comentário | Marca como lida, o contador diminui, abre o orçamento na edição | Positivo | P1 |
| CT-NOT-003 | Marcar como não lida | Clicar no ícone de envelope da notificação já lida | Volta a "não lida" **sem** navegar para o orçamento | Positivo | P2 |
| CT-NOT-004 | Atualização automática | Sino fechado e tela parada; em outra janela, `qa.comercial` comenta alertando `qa.admin`; esperar até 60 s | O contador do `qa.admin` sobe sozinho, sem recarregar a página | Positivo | P2 |
| CT-NOT-005 | Destinatário só com "Ver" (**R12**) | Dar ao perfil Vendedor **Orçamentos › Ver**; como `qa.comercial`, comentar alertando `qa.vendedor`; como `qa.vendedor`, clicar na notificação; depois **restaurar** a matriz | **Hipótese R12:** `qa.vendedor` aparece na lista de alerta, mas a notificação leva a uma página 403 (a edição exige "Editar"). Registrar | Permissão | P2 |
| CT-NOT-006 | Recontato de cliente sem contato | Cadastrar C5 `DEMO Recontato Épsilon` (vendedor `qa.comercial`, "Alerta de recontato (dias)" = 1, sem orçamento nem atendimento); no terminal, rodar `php artisan clientes:verificar-recontato`; como `qa.comercial`, abrir o sino | Notificação de recontato para C5; o clique abre a edição do cliente | Positivo | P1 |
| CT-NOT-007 | Texto da mensagem de recontato (**R13**) | **A partir do D7:** rodar o comando de novo (C1 tem recontato de 1 dia e o último contato é o orçamento do D5) e ler a mensagem do C1 | Mensagem com número **inteiro** de dias. **Hipótese R13:** aparecem casas decimais | Borda | P3 |
| CT-NOT-008 | Não duplica | Sem ler a notificação do C5, rodar o comando de novo | Não cria uma segunda notificação para C5 | Positivo | P2 |

---

## Pontos de atenção

- **R20** (CT-MRE-004), **R12** (CT-NOT-005), **R13** (CT-NOT-007).
- O comando de recontato roda sozinho todo dia **só se** o agendador do Laravel (`schedule:run`) estiver configurado na máquina. No ambiente local o QA roda à mão. No relatório final, recomende confirmar o agendador no servidor de produção.
- O sistema **não envia e-mail**: notificação é só o sino (e, no futuro, o app).

---

## Registro de execução — D6

| ID | Status | Vídeo + tempo | Bug | Data / QA |
|---|---|---|---|---|
| CT-MRE-001 | | | | |
| CT-MRE-002 | | | | |
| CT-MRE-003 | | | | |
| CT-MRE-004 | | | | |
| CT-MRE-005 | | | | |
| CT-IND-001 | | | | |
| CT-IND-002 | | | | |
| CT-IND-003 | | | | |
| CT-IND-004 | | | | |
| CT-IND-005 | | | | |
| CT-NOT-001 | | | | |
| CT-NOT-002 | | | | |
| CT-NOT-003 | | | | |
| CT-NOT-004 | | | | |
| CT-NOT-005 | | | | |
| CT-NOT-006 | | | | |
| CT-NOT-007 | | | | |
| CT-NOT-008 | | | | |
