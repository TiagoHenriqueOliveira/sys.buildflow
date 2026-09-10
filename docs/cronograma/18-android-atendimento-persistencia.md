# Sessão Android — Bloco 4 (Atendimento/Relatório, incrementos) · Etapa 2: Persistência/Sync

Ver [00-indice.md](00-indice.md). Pré-requisito: [17-android-atendimento-telas.md](17-android-atendimento-telas.md).

**Esta é a última sessão do cronograma completo** (Web + Android, todos os blocos exceto BF12, que segue bloqueado até decisão do cliente).

## Escopo: BF07, BF08, BF09, BF10, BF11 (mobile)

## Entregáveis

- Extensão da fila de sincronização offline (`sync_service.dart`) para: log de compartilhamento (BF07), checklist de peças (BF09), observações internas com fotos (BF11) — seguindo o padrão RNF01 já estabelecido.
- BF08 (mapa) e BF10 (status de aprovação) são majoritariamente consultas de leitura — sync mais simples, mas ainda precisam funcionar com dado em cache quando offline.

## Testes

- Cenário completo offline→online para cada um dos 5 itens, sem duplicar nem perder dados.
- Suíte de testes automatizados do app (se existente) rodando sem regressão sobre os fluxos herdados do MCL.

## Ao finalizar

- Bump de versão do app.
- **Revisão de segurança final** de todo o projeto: mapear rotas `web.php`/`api.php` (grupo `fae/v1`) e telas/rotas do app, confirmando que tudo que deveria exigir autenticação/perfil exige de fato — inclusive os itens novos de todos os blocos anteriores (Núcleo, Configurador, CRM, Atendimento).
- Checar novamente a tabela de pendências do [00-indice.md](00-indice.md): a esta altura, o prazo de retorno do cliente (23/09/2026, conforme `CLAUDE.md`) provavelmente já passou — revisar se alguma resposta chegou e gerar os ajustes aditivos indicados na coluna "retrabalho" de cada pendência.

## Cronograma concluído

Não há próxima sessão numerada. BF12 (contador de tempo de atendimento) permanece fora do escopo até decisão explícita do cliente — ao ser desbloqueado, merece sua própria sessão dedicada (Telas + Persistência), seguindo o mesmo padrão deste cronograma.
