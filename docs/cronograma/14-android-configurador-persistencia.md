# Sessão Android — Bloco 2 (Configurador) · Etapa 2: Persistência/Sync

Ver [00-indice.md](00-indice.md). Pré-requisito: [13-android-configurador-telas.md](13-android-configurador-telas.md).

## Escopo: BF05, NC03, NC04 (mobile)

## Entregáveis

- Cache local (sqflite, via `local_db.dart`) do banco de perguntas/modelos/revisões, para o formulário dinâmico funcionar **offline** (RNF01) — este é o requisito não funcional mais crítico do app inteiro, citado explicitamente no documento como aplicável a BF05/NC03/NC04.
- Fila de sincronização de respostas e anexos (por pergunta e gerais), reaproveitando/estendendo `sync_service.dart`, sem duplicar nem perder dados ao reconectar.
- Garantir que o formulário preenchido offline referencia a revisão de modelo que estava em cache no momento do preenchimento (mesma regra de versionamento do Web) — não deve haver drift se o modelo for atualizado no servidor enquanto o técnico está em campo sem sinal.

## Testes

- Preencher relatório offline com os 3 tipos de pergunta e validar sincronização ao reconectar, sem duplicar.
- Anexar foto offline a uma pergunta habilitada e validar sincronização.
- Simular atualização de modelo no servidor enquanto o app está com uma revisão antiga em cache — relatório em andamento deve continuar consistente com a revisão que tinha ao iniciar o preenchimento.

## Ao finalizar

- Bump de build number.

## Próxima sessão

[15-android-crm-telas.md](15-android-crm-telas.md) — **Bloco 3, CRM Comercial, priorizado.**
