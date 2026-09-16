# Sessão Android — Bloco 4 (Atendimento/Relatório, incrementos) · Etapa 1: Telas + Endpoints

Ver [00-indice.md](00-indice.md). Pré-requisito: [15-android-crm-telas.md](15-android-crm-telas.md).

Último bloco do passe de Telas — os incrementos da assistência técnica ficaram para depois do CRM por prioridade do usuário.

## Escopo: BF07, BF08, BF09, BF10, BF11 (mobile) — **com os endpoints REST novos**

## Estado real no Web hoje (conferido direto no código em 14/09/2026 — não confiar em versões antigas deste arquivo)

- ✅ **BF07 (comprovante de compartilhamento)** — implementado: `AtendimentosRelatoriosController::storeCompartilhamento()`/`getCompartilhamentos()`, hash SHA-256 (`hash('sha256', id|timestamp|random)`), tabela `atendimentos_relatorios_compartilhamentos`. Endpoint web: `atendimentos-relatorios.store-compartilhamento`/similar — conferir rota exata antes de espelhar em `fae/v1`.
- ✅ **BF08 (mapa de demandas)** — implementado: `MapaDemandasController::index()`, rota `mapa-demandas.index` (web, dentro do grupo autenticado, não `comercial` — confirmar se assistência técnica deve ver isso no mobile também, ou só comercial/admin).
- ⚠️ **BF09 (checklist de peças) — só metade feita**: `atendimentos_relatorios_pecas` ganhou `aten_rel_peca_trocada` (boolean), mas **não existe `aten_rel_peca_levada`** — o item "peças levadas" do documento de requisitos não foi implementado nem no Web. Decidir nesta sessão: implementar os dois lados (web + mobile) ou confirmar com o usuário se "levada" ainda é necessário antes de replicar só a metade existente.
- ✅ **BF10 (aprovação)** — `aten_rel_status` (0/1/2) + `aten_rel_aprovado_por`/`aten_rel_aprovado_em` no model `AtendimentoRelatorio`. Sem histórico completo de mudanças de status (pendência #6 do cliente, ver `00-indice.md`) — só o último estado, que é o que replicar aqui.
- ✅ **BF11 (observação interna)** — a nível de **relatório** (não mais de atendimento), confirmado em `AtendimentoRelatorio`/`AtendimentosRelatoriosController`. Não aparece no PDF assinado — conferir exatamente onde essa exclusão é garantida (`AtendimentosRelatoriosController::pdf()`) antes de expor o campo errado no endpoint mobile.

## Parte A — Endpoints novos em `sys-buildflow-fae` (`routes/api.php`, grupo `fae/v1`)

Mapear e expor em `Api\Fae\RelatoriosController` os equivalentes dos endpoints web listados acima (compartilhamento, mapa de demandas, peças com booleanos, status/aprovação, observação interna) — reaproveitando os models e regras já existentes, mesmo padrão das sessões 11/13/15. Se decidir implementar `aten_rel_peca_levada` nesta sessão, criar a migration no Web primeiro (schema é compartilhado pelas duas pontas).

**Ao terminar a Parte A:** revisão de segurança do MINOR, testes cobrindo os endpoints novos.

## Parte B — Telas no `app_buildflow_fae`

- **BF07**: compartilhar relatório em PDF com comprovante — reaproveita `share_plus` (já presente).
- **BF08**: mapa de demandas no app — **mesma decisão de mapa embutido vs. lista da sessão 15** (Mapa de Relações); não fixar dependência de mapa duas vezes com respostas diferentes, decidir uma vez e aplicar nos dois lugares.
- **BF09**: checklist de peças levadas/trocadas (dependente da decisão da Parte A sobre "levada").
- **BF10**: tela de status de aprovação (leitura — aprovação em si é do supervisor no painel Web) + fluxo de revisão quando o relatório volta como "Revisar".
- **BF11**: observações internas (texto + fotos) no relatório, confirmando visualmente que não entram na prévia/compartilhamento do PDF assinado.

## Não faz parte desta sessão
Extensão da fila de sincronização offline para esses itens — isso é a sessão 18, última do cronograma.

## Critérios de aceite a verificar
- Compartilhar relatório via WhatsApp e outro app gera comprovante com hash em ambos os casos.
- Mapa/lista de demandas reflete o status correto por ponto.
- Checklist de peças preenchido no app aparece no relatório final (escopo conforme decisão da Parte A).
- Técnico vê relatório voltar como "Revisar" e consegue editá-lo novamente.
- Observação interna com foto não aparece na prévia do PDF assinado nem no comprovante de compartilhamento.

## Próxima sessão
[18-android-atendimento-persistencia.md](18-android-atendimento-persistencia.md) — fora de escopo até nova confirmação do usuário (ver [00-indice.md](00-indice.md)).