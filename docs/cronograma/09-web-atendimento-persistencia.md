# Sessão Web — Bloco 4 (Atendimento/Relatório, incrementos) · Etapa 2: Persistência

Ver [00-indice.md](00-indice.md). Pré-requisito: [07-web-crm-persistencia.md](07-web-crm-persistencia.md).

**Reescrito em 16/09/2026** com base em auditoria direta do código atual (`BF_v1.8.5`). Não confiar em versão anterior deste documento — BF06, BF07, BF08 e BF11 já estavam completos há várias sessões; a versão antiga (09/09) tratava todo o bloco como zero.

Última sessão do passe de Persistência do Web. Ao terminar, revisar a tabela de pendências do [00-indice.md](00-indice.md) antes de considerar o Web "fechado" para esta fase.

## Escopo: BF10 (bloqueio de edição pós-aprovação) + BF09 (checklist "levada")

**BF06, BF07, BF08 e BF11 já estão completos** — PDF só com perguntas respondidas, hash de comprovante (SHA-256, `AtendimentosRelatoriosController::storeCompartilhamento()`), mapa de demandas (`MapaDemandasController`) e observação interna a nível de relatório (fora do PDF assinado). Não fazem parte desta sessão.

## Estado atual (auditado direto no código)

- `AtendimentoRelatorio` já tem `aten_rel_status` (0/1/2) + `aten_rel_aprovado_por`/`aten_rel_aprovado_em`. A tela (`show()`) já calcula `somenteLeitura = ($status === Aprovado)` e manda isso pra view.
- **Mas nenhum endpoint de mutação checa isso no backend.** `updateDados`, `updateHorarios`, `updateClima`, `storeResposta`, `destroyResposta`, `destroyRespostaFoto`, `storeOcorrencia`/`destroyOcorrencia`, `storeServico`/`destroyServico`, `storePeca`/`destroyPeca`, `storeDescricaoItem`/`destroyDescricaoItem`, `uploadAnexos`/`destroyAnexo` — nenhum deles rejeita a requisição se o relatório já estiver Aprovado. `somenteLeitura` só desabilita botões na tela; a API aceita a alteração de qualquer forma.
- `aten_rel_peca_trocada` (boolean) existe em `atendimentos_relatorios_pecas`; `aten_rel_peca_levada` **não existe**.

## Entregáveis

### 1. Bloqueio de edição pós-aprovação (BF10)
- Novo método no controller (`garantirRelatorioEditavel(AtendimentoRelatorio $relatorio)`, ao lado do já existente `relatorioComPosseGarantida()`) que aborta com `403` se `aten_rel_status === AtendimentoRelatorioStatus::Aprovado`.
- Chamar esse método no **início de todo endpoint que altera conteúdo** do relatório (lista acima). **Não** aplicar em `storeCompartilhamento`/`getCompartilhamentos` nem na geração de PDF — um relatório aprovado continua podendo ser compartilhado/exportado, só não editado.
- Transição pra "Revisar" (status=1) já deveria reabrir edição — confirmar que nenhuma trava nova bloqueia esse caminho (o check é só `=== Aprovado`, então Revisar já passa livre).
- Mensagem de erro amigável no frontend quando a API rejeitar (hoje `somenteLeitura` já esconde os botões — o guard novo é a rede de segurança pro caso de a requisição chegar mesmo assim).

### 2. Checklist "peça levada" (BF09)
- Migration: `aten_rel_peca_levada` (boolean, default false) em `atendimentos_relatorios_pecas`, mesmo padrão da migration que já adicionou `aten_rel_peca_trocada`.
- UI: checkbox "Levada" ao lado do já existente "Trocada" na aba de Peças (tela legada — confirmar se essa aba ainda é usada por relatórios novos ou só por relatórios com dado legado, ver decisão da sessão 08/13 do Android sobre `sections/*`; se for só legado, avaliar se vale a pena ou se isso deveria migrar pra uma pergunta do Configurador em vez de coluna fixa).

## ⚠️ Ponto que continua bloqueado

Pendência #6 do cliente (histórico completo de aprovação vs. só o último status) **ainda não tem resposta** (prazo era 23/09/2026). Esta sessão implementa só o bloqueio de edição sobre o status atual — **não** criar tabela de histórico de aprovações sem confirmação, é retrabalho garantido se a resposta vier diferente.

## Testes

Criar/estender `tests/Feature/AtendimentoPersistenciaFaeTest.php`:
- Tentar editar dados/respostas/anexos de um relatório Aprovado retorna 403 em cada endpoint da lista acima.
- Relatório com status "Revisar" continua editável normalmente.
- Compartilhar (gerar hash) um relatório Aprovado continua funcionando.
- Marcar peça como "levada" persiste e aparece no relatório final, independente do campo "trocada".

## Ao finalizar

- Bump de versão **MINOR**.
- Revisão de segurança: conferir que o guard novo foi aplicado em **todos** os endpoints de mutação listados acima — é fácil esquecer um.
- **Fechamento do passe de Persistência do Web**: revisar a tabela de pendências do [00-indice.md](00-indice.md) e o levantamento de 16/09/2026 — os itens que dependiam de pendência do cliente continuam registrados como bloqueados, o resto deveria estar todo fechado neste ponto.

## Próxima sessão

Nenhuma agendada — próximo passo é o passe de Telas+Endpoints do Android (`10-android-preparacao.md` em diante), já em andamento/planejado separadamente.