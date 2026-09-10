# Sessão Android — Bloco 4 (Atendimento/Relatório, incrementos) · Etapa 1: Telas

Ver [00-indice.md](00-indice.md). Pré-requisito: [16-android-crm-persistencia.md](16-android-crm-persistencia.md) e o Bloco 4 do Web concluído ([09-web-atendimento-persistencia.md](09-web-atendimento-persistencia.md)).

Este é o **último bloco do cronograma** — os incrementos da assistência técnica no app ficaram para depois do CRM por prioridade do usuário.

## Escopo: BF07, BF08, BF09, BF10, BF11 (mobile)

## Entregáveis

- **BF07**: compartilhamento do relatório em PDF com comprovante — reaproveita `share_plus` (já presente no `pubspec.yaml`) e o endpoint de hash do Web (Sessão 09).
- **BF08**: mapa de demandas no app (usa `google_maps_flutter`/`geolocator` da Sessão 10), com marcação por status e popup de detalhes.
- **BF09**: checklist de peças levadas/trocadas no app.
- **BF10**: tela de status de aprovação (leitura, já que a aprovação em si é feita pelo supervisor no painel web) + fluxo de revisão pelo técnico quando o relatório volta com status "Revisar".
- **BF11**: observações internas (texto + fotos) no app, associadas ao relatório, confirmando que não entram no PDF assinado.

## Não faz parte desta sessão

Extensão da fila de sincronização para esses itens — isso é a Sessão 18, última sessão do cronograma.

## Critérios de aceite a verificar

- Compartilhar relatório via WhatsApp e outro app gera comprovante em ambos os casos.
- Mapa de demandas reflete o status correto por ponto.
- Checklist de peças preenchido no app aparece no relatório final.
- Técnico vê relatório voltar como "Revisar" e consegue editá-lo novamente.
- Observação interna com foto não aparece na prévia do PDF assinado.

## Próxima sessão

[18-android-atendimento-persistencia.md](18-android-atendimento-persistencia.md) — última sessão do cronograma.
