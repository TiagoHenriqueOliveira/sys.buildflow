# D11 — Reteste, regressão e fechamento

[← Índice](../00-indice.md) · Anterior: [D10](D10-transversais-seguranca.md)

| | |
|---|---|
| **Objetivo** | Retestar os bugs corrigidos, gravar o **vídeo mestre** do fluxo completo (referência principal da wiki) e fechar a rodada com o relatório final |
| **Duração** | ~6 h, mais o tempo de reteste de cada correção que chegar |
| **Perfis** | Todos |
| **Pré-condições** | D1 a D10 executados; correções entregues numa nova versão (ex.: `v1.14.1`), com o número no rodapé |

## Vídeos

| Vídeo | Conteúdo |
|---|---|
| `RETESTE_BUG-NNN_<assunto>_v<versão>_<PASS/FAIL>.mp4` | Um por bug retestado |
| `D11_CT-REG-002_fluxo-ponta-a-ponta_v<versão>.mp4` | **Vídeo mestre:** o fluxo completo, narrado, para a wiki |

---

## Casos

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-REG-001 | Reteste dos bugs corrigidos | Para cada bug em "Corrigido": repetir os passos exatos do bug e os casos vizinhos do mesmo roteiro | Bug não acontece mais e os vizinhos continuam passando. Atualizar o status do bug ([03-padrao-bugs.md](../03-padrao-bugs.md)) | Positivo | P1 |
| CT-REG-002 | Vídeo mestre, ponta a ponta | Em um único vídeo de ~15 min, com massa nova `DEMO E2E`: (1) Admin confere natureza e modelo › (2) Comercial cadastra cliente com localização › (3) cria orçamento com perguntas e comentário com alerta › (4) Admin cria atendimento para o Técnico 1 › (5) Técnico 1 cria relatório, preenche horário, perguntas, sessão, fotos, assina e libera › (6) Assistência recebe a notificação e aprova › (7) gera o PDF › (8) confere o Mapa de Demandas | Todo o fluxo sem erro; PDF correto; notificações recebidas. Este vídeo vai para `Wiki - vídeos de referência/` | Positivo | P1 |
| CT-REG-003 | Smoke na versão final | Repetir o smoke do [D0](../01-preparacao.md#9-smoke-test) na versão com as correções | Todos os menus abrem; rodapé com a versão nova | Positivo | P1 |
| CT-REG-004 | Suíte automatizada na versão final | `php artisan test` | Sem falha nova além da baseline (6 em `AtendimentoRelatorioTest`) | Positivo | P1 |
| CT-REG-005 | Riscos fechados | Conferir a tabela de riscos R01–R20 no [índice](../00-indice.md#riscos-mapeados-na-leitura-do-código) | Toda linha com "Confirmado (BUG-NNN)" ou "Não reproduzido" | Positivo | P1 |
| CT-REG-006 | Relatório final publicado | Preencher a seção abaixo e o painel do índice; revisar os links dos vídeos | Relatório completo; todos os links abrem para quem é da organização | Positivo | P1 |

---

## Limpeza do ambiente (decidir com o responsável)

- **Opção A — manter a massa DEMO:** útil para demonstrações e para a próxima rodada. Deixe tudo com o prefixo `DEMO`.
- **Opção B — voltar ao estado anterior:** restaurar o backup do D0 ([01-preparacao.md](../01-preparacao.md#2-backup-do-banco-obrigatório)). Isso apaga tudo o que foi criado na rodada, inclusive os usuários QA.

Registre aqui a opção escolhida: `________` · quem decidiu: `________` · data: `____/____`

---

## Relatório final da rodada

> Copie esta seção, preencha e publique (link no canal da equipe e no índice).

**Versão testada:** v1.14.0 → retestada em v`______`
**Período:** D0 = `____/____` a D11 = `____/____`
**QA(s):** `________`
**Pasta de vídeos:** [link](...)

### Números

| Métrica | Valor |
|---|---|
| Casos planejados / executados | / |
| Passou / Falhou / Bloqueado | / / |
| % P1 executados | |
| % P2 executados | |
| Bugs abertos na rodada (Crítico / Alto / Médio / Baixo) | / / / |
| Bugs corrigidos e retestados | |
| Bugs aceitos como estão (com justificativa) | |
| Riscos R01–R20 confirmados / não reproduzidos | / |

### Recomendação

- [ ] **Liberar:** critérios de saída atendidos.
- [ ] **Liberar com ressalvas:** listar os bugs aceitos e o motivo.
- [ ] **Não liberar:** listar os bloqueadores.

### Pontos para decisão do produto

Itens que não são bugs, mas regras a confirmar, levantados durante a rodada:

- No CRM, quem tem acesso a Orçamentos vê e edita orçamentos de **todos** os vendedores (sem restrição por dono).
- Relatório pode ser liberado com perguntas em branco (não há resposta obrigatória).
- Não existe "esqueci a senha" no login.
- O agendador do Laravel (`schedule:run`) precisa estar ativo no servidor para o alerta de recontato rodar sozinho.
- (adicionar os demais encontrados)

---

## Registro de execução — D11

| ID | Status | Vídeo + tempo | Bug | Data / QA |
|---|---|---|---|---|
| CT-REG-001 | | | | |
| CT-REG-002 | | | | |
| CT-REG-003 | | | | |
| CT-REG-004 | | | | |
| CT-REG-005 | | | | |
| CT-REG-006 | | | | |
