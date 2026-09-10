# Sessão Web — Bloco 4 (Atendimento/Relatório, incrementos) · Etapa 2: Persistência

Ver [00-indice.md](00-indice.md). Pré-requisito: [08-web-atendimento-telas.md](08-web-atendimento-telas.md).

Última sessão do Web. Ao terminar, o Web está funcionalmente completo para esta fase e o trabalho passa para o Android ([10-android-preparacao.md](10-android-preparacao.md)).

## Escopo: BF06 (ajuste), BF07, BF08, BF09, BF10, BF11

## Entregáveis

- **BF06**: query ajustada para o novo layout de PDF (1 imagem/linha + comentário).
- **BF07**: tabela de log de envio/compartilhamento (relatorio_id ou orcamento_id, data_hora, hash de 10 caracteres). O hash deve ser **gerado e armazenado de forma imutável** (RNF06) — não falsificável, não editável após criação. Gerado a cada compartilhamento, independente do app de destino (WhatsApp, Drive, OneDrive, Telegram, etc. — via menu nativo do celular, não restrito ao WhatsApp).
- **BF08**: índice geográfico em `clientes` (lat/long) se a performance exigir (RNF05) — validar com volume real antes de otimizar prematuramente.
- **BF09**: colunas booleanas `levada`/`trocada` na tabela de peças por atendimento (ajustar `atendimentos_relatorios_pecas` ou criar tabela nova associativa, dependendo de como ficou desenhado na Etapa 1).
- **BF10**: colunas `aprovador_id`, `data_aprovacao` no relatório; regra de bloqueio de edição quando `status = aprovado`; status "Revisar" reabre para edição pelo técnico (não existe status "Reprovado").
- **BF11**: campo/tabela de observações internas no nível de relatório; garantir exclusão explícita da query que monta o PDF assinado (BF06).

## Testes

- Hash gerado é único por envio, não reaproveitado.
- Compartilhar por um app diferente do WhatsApp também gera comprovante.
- Editar relatório aprovado é bloqueado; marcar "Revisar" reabre para edição.
- Observação interna nunca aparece no PDF assinado, em nenhum cenário.
- Checklist de peças preenchido aparece corretamente no relatório final.

## Ao finalizar

- Bump MINOR + revisão de segurança de todas as rotas novas/alteradas deste bloco.
- **Fechamento do Web**: revisar rapidamente contra a tabela de pendências do [00-indice.md](00-indice.md) — nenhuma delas deveria estar bloqueando neste ponto, exceto BF12 (fora do escopo).

## Próxima sessão

[10-android-preparacao.md](10-android-preparacao.md) — início da trilha Android, em sessão(ões) separada(s).
