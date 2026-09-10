# Sessão Android — Bloco 1 (Núcleo) · Etapa 2: Persistência/Sync

Ver [00-indice.md](00-indice.md). Pré-requisito: [11-android-nucleo-telas.md](11-android-nucleo-telas.md).

## Escopo: BF01, BF02, BF03 (mobile)

## Estado atual (ponto de partida)

- Sync offline já existe para atendimentos/relatórios via `lib/core/services/sync_service.dart` + `lib/core/database/local_db.dart` (sqflite) — seguir o mesmo padrão para os novos campos/entidades em vez de criar um mecanismo paralelo.

## Entregáveis

- Cache local (sqflite) dos campos novos de cliente (geo, segmento, classificação, contatos) espelhando o schema definido no Web (Sessão 03).
- Fila de sincronização cobrindo criação/edição de cliente feita offline pelo vendedor (captura de geo em campo, sem sinal) — reaproveitar o padrão de fila já usado para relatórios.
- Persistir localmente o perfil do usuário logado (já carregado na Sessão 10/11) para o gating de menu funcionar mesmo offline.

## Testes

- Capturar geolocalização offline e validar sincronização ao reconectar.
- Login com usuário comercial vs. técnico reflete corretamente o menu, mesmo após reabrir o app (perfil persistido localmente).

## Ao finalizar

- Bump de build number (`pubspec.yaml`, formato `X.Y.Z+N`, convenção `BF_vMAJOR.MINOR.PATCH` do app).

## Próxima sessão

[13-android-configurador-telas.md](13-android-configurador-telas.md)
