# Sessão Android — Bloco 3 (CRM Comercial, CRM09) · Etapa 2: Persistência/Sync — PRIORIDADE

Ver [00-indice.md](00-indice.md). Pré-requisito: [15-android-crm-telas.md](15-android-crm-telas.md).

## Escopo: CRM09 (persistência/sync)

## Entregáveis

- Este é o **maior esforço de sincronização offline do app inteiro**, dado o volume de entidades novas: orçamentos, comentários (com alerta), roteiro de viagem (saída/retorno), equipamentos vendidos/casos de sucesso.
- Cache local (sqflite) espelhando o schema definido no Web (Sessão 07) para essas entidades.
- Fila de sincronização cobrindo criação/edição offline de orçamentos e roteiros de viagem (cenário comum em campo, sem sinal) — reaproveitar o padrão já validado em `sync_service.dart` para atendimentos/relatórios.
- Compartilhamento de PDF de orçamento com comprovante — reaproveita `share_plus` (já presente) e a lógica de hash implementada no Bloco 4 do Web (Sessão 09); se a Sessão 09 já estiver concluída, o endpoint de hash já existe e o app só precisa consumi-lo.

## Testes

- Criar orçamento offline, sincronizar ao reconectar, sem duplicar.
- Registrar roteiro de saída/retorno offline e sincronizar.
- Compartilhar PDF de orçamento e confirmar geração do comprovante.

## Ao finalizar

- Bump de versão do app + revisão de segurança final (checar que nenhuma tela/rota comercial nova ficou acessível sem checagem de perfil, tanto na API consumida quanto na navegação local).

## Próxima sessão

[17-android-atendimento-telas.md](17-android-atendimento-telas.md)
