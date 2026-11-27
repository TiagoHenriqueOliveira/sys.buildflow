# Prompt — Sessão Android: plano de correção da auditoria até nota 9

> Cole na sessão aberta em `C:\Users\tiago\StudioProjects\app_buildflow_fae`:
> **"Leia e execute `C:\Apache24\htdocs\sys-buildflow-fae\docs\auditoria\PROMPT-SEGURANCA-ANDROID.md`. Comece pela Fase 0 e pare ao fim dela."**
> (A sessão vai pedir permissão para ler essa pasta do backend — é só leitura.)

## Objetivo

Corrigir, no app Flutter da FAÉ, todos os achados da auditoria de 27/11/2026 que cabem ao lado do aplicativo, até que A9 (segurança do app), B11 (qualidade do app) e a parte do app em A1, A4, A7, A11, B5, B6 e B10 cheguem a nota ≥ 9. O backend é de outra sessão (`PROMPT-SEGURANCA-WEB.md`); **não edite o repositório `sys-buildflow-fae`** — dele você só lê o relatório e o contrato.

## Leia antes de começar

1. `CLAUDE.md` deste repositório (padrão `BF_vMAJOR.MINOR.PATCH`, revisão de segurança a cada MINOR, distribuição por APK).
2. `C:\Apache24\htdocs\sys-buildflow-fae\docs\auditoria\auditoria-seguranca-arquitetura-fae-2026-11-27.md` — seções 5.4 (bloco de sincronização), S-05, S-13, S-14, S-15, S-16, seção 6 (plano) e seção 7 (checklist, bloco "App" e "Sincronização").
3. `C:\Apache24\htdocs\sys-buildflow-fae\docs\auditoria\CONTRATO-API-FAE-V1.md` — **fonte única do comportamento da API**. Só implemente o lado do app de uma seção quando a tabela 12 do contrato marcar essa seção como implementada (ou quando o usuário disser que já está no servidor de desenvolvimento). Se o contrato não cobrir algo que você precisa, pare e peça ao usuário para levar o pedido à sessão Web — não invente endpoint, código de erro ou cabeçalho.

## Regras da sessão

- **Responda em pt-BR.**
- **Só FAÉ.** O app consome apenas `/api/fae/v1`. Não reaproveite regra, endpoint ou configuração do app da MCL (`app.buildflow`, `/api/mcl/v1`) e não altere nada naquele repositório — cada cliente tem seu próprio app, API e servidor.
- **Confira antes de corrigir.** O relatório vale para o commit `a03c348` (v1.3.0+8). Rode `git log a03c348..HEAD` nos arquivos citados e confirme que o problema ainda existe antes de mexer. Se já mudou, registre no acompanhamento.
- **Não reverta mudanças de outras sessões.** `git status` antes de cada commit; commite só os seus arquivos. Hoje há uma alteração local de IP de desenvolvimento em `lib/core/constants/app_config.dart` — não é sua, não commite nem desfaça sem perguntar.
- **Uma fase por rodada.** Ao terminar, pare, entregue o relatório de fim de fase (modelo no final) e espere o usuário dizer "continuar".
- **Decisões do cliente:** se um item depende de decisão ainda não respondida, pergunte ao usuário (AskUserQuestion), siga com o resto da fase e marque o item como "bloqueado por D-xx".
- **Nunca perder trabalho de campo.** Qualquer mudança na fila de sincronismo ou no banco local precisa de migração que preserve as pendências existentes, com teste cobrindo a migração.
- **Segredos:** keystore, `key.properties` e senhas nunca entram no repositório nem em log. A geração do keystore de release é do usuário — explique o comando e onde guardar, não gere com senha sua.
- **Versão e commits:** `pubspec.yaml` `X.Y.Z+N` (N sempre sobe); commits `BF_vX.Y.Z: ...`. Pode commitar; **nunca faça push**; não publique APK (`tool/build_publish_apk.ps1`) sem pedido explícito.
- **Testes:** cada item fechado ganha teste (unitário, de widget ou de integração com servidor simulado). `flutter analyze` sem avisos novos.
- **Acompanhamento:** mantenha `docs/auditoria/ACOMPANHAMENTO-ANDROID.md` neste repositório, com uma linha por item: status, versão, commit e teste que prova.

## Fase 0 — Contenção (precisa estar nos aparelhos antes do bloqueio de relatório no servidor)

| Passo | Item | O que fazer | Aceite |
|---|---|---|---|
| 0.1 | S-14 (fila) | A fila **nunca** descarta pendência em 4xx. Nova situação "erro" na `pending_writes` (migração do banco v5 → v6 preservando os itens), guardando código e mensagem do servidor. Tela "Pendências" acessível ao usuário comum (não só a tela técnica de log), com reenviar e descartar (descartar pede confirmação). Tratar `423 relatorio_bloqueado`, `403`, `409` e `422` como pendência com erro, conforme contrato §11 — funcionar mesmo antes de o servidor enviar `codigo` (usar o status HTTP). | Teste: resposta 422/423 deixa o item na fila com erro e visível; nada some sem ação do usuário; migração v5→v6 preserva as pendências |
| 0.2 | S-05 (token) | Token em `flutter_secure_storage` (Keystore), com migração: ler o token antigo do `SharedPreferences`, gravar no seguro e apagar o antigo. | Teste: depois da migração, `SharedPreferences` sem token e sessão continua válida |
| 0.3 | S-05 (401) | `401` pausa a fila e pede login mantendo as pendências (mesmo usuário); `403 usuario_inativo` no login faz limpeza total (cache, fila, arquivos, credenciais). Ler `expira_em` do login quando existir. Contrato §2.3, §2.4. | Testes dos dois caminhos |
| 0.4 | S-16 | Remover os comentários e restos de infraestrutura da MCL (`api_service.dart`, host pinado de outro cliente). Manter o comportamento "fail closed" da validação TLS. | `git grep -i mcl -- lib` vazio (fora de histórico) |
| 0.5 | — | Relatório para o usuário com a **ordem de deploy**: a versão com 0.1 precisa estar instalada em todos os aparelhos antes de o servidor ligar o `423` (S-02 da sessão Web). Sem isso, o técnico perde em silêncio o que gravou offline. | — |

**Fim da Fase 0:** revisão rápida de segurança do `CLAUDE.md` (token, `debugPrint` sem dados pessoais), relatório de fim de fase.

## Fase 1 — Segurança do app e sincronização segura

| Item | O que fazer | Contrato / dependência |
|---|---|---|
| S-05 (MFA) | Fluxo de login em dois passos: tratar `mfa_pendente` + `desafio`, tela do código de 6 dígitos, `POST /login/mfa`, erros `mfa_invalido`, `desafio_invalido`, `mfa_bloqueado`, `mfa_nao_cadastrado`. | §2.1, §2.2 · D-08 |
| S-14 (idempotência) | `Idempotency-Key` (UUID v4) gerada **ao enfileirar**, gravada na pendência (nova coluna, com migração) e reutilizada em toda retentativa; tratar `Idempotent-Replayed` e `409 idempotencia_*`. | §4 |
| S-14 (retentativa) | Backoff exponencial com jitter (1 min dobrando até 30 min); respeitar `Retry-After` no 429. | §11 |
| S-14 (versão) | Cabeçalho `X-App-Version` em toda requisição; `426 versao_minima` abre a atualização obrigatória. | §3 |
| S-14 (conflito) | Guardar `versao` de cliente/orçamento/roteiro, enviar `If-Match`, tela de conflito em `409 conflito_versao` (usar a do servidor ou reaplicar a minha sobre ela). Ao salvar cliente, sempre devolver o `id` dos contatos/equipamentos/localizações existentes. | §6, §7 |
| S-13 (identidade) | Novo `applicationId` exclusivo da FAÉ (**pergunte o nome**; ex.: `br.com.faeindustria.buildflow`) e plano de migração: o app antigo precisa sincronizar tudo antes de ser desinstalado — escreva o roteiro de troca para os técnicos. | Usuário |
| S-13 (build) | Assinatura de release via `key.properties` (o build de release deve **falhar** se o arquivo não existir, em vez de cair na chave de debug); `--obfuscate --split-debug-info` no `tool/build_publish_apk.ps1`, guardando os símbolos fora do APK. | Usuário (keystore) |
| S-13 (dados locais) | Banco local cifrado (SQLCipher, ex.: `sqflite_sqlcipher`) com chave no Keystore, com migração do banco atual sem perder pendências; acesso offline vence em 7 dias sem contato com o servidor (contrato §2.4). | §2.4 |
| S-13 (atualização) | Baixar APK só por HTTPS e conferir `sha256_apk` antes de instalar; recusar e avisar se não conferir. | §3 |
| S-13 (rede) | URL de produção real em `AppConfig` (**pergunte**; depende da hospedagem). Decidir com o usuário entre pinning por chave pública ou justificativa escrita para não usar. | Hospedagem |
| S-15 | Exibir e guardar só `cpf_mascarado`; o CPF completo só existe na pendência de assinatura (cifrada) e some após o envio. | §10 · D-05 |
| S-17 | Compartilhar o PDF devolvido por `POST /compartilhamentos` (`pdf_url`), mostrando o `codigo_verificacao`, em vez de baixar outro PDF. | §10 · D-04 |
| — | Tratar `403 sem_acesso_arquivo` no cache de mídia (não tentar de novo em loop). | §9 |

**Fim da Fase 1:** revisão de segurança (MINOR), relatório de fim de fase com as instruções de build/assinatura e o roteiro de troca do `applicationId`.

## Fase 2 — LGPD (lado do app)

| Item | O que fazer | Dependência |
|---|---|---|
| L-07 | Aviso de privacidade no primeiro acesso e tela "Privacidade" no menu (texto do jurídico); registrar a ciência no servidor se a sessão Web criar o endpoint. | Jurídico |
| L-03 | Remover EXIF (inclusive GPS) das fotos antes do envio, junto com a compressão. | — |
| L-05 | Retenção local: apagar cache de leitura e mídias já sincronizadas mais antigas que o prazo definido. | D-05 |
| — | Conferir que nenhum `debugPrint`/log grava dado pessoal, token ou CPF; trocar `debugPrint` por um logger que não escreve nada em release. | — |

## Fase 3 — Escopo e qualidade (B10, B11)

| Item | O que fazer | Dependência |
|---|---|---|
| G-02 (app) | Guardar o formulário por `revisao_id` e preencher sempre com a revisão do relatório. | §8 · Web G-02 |
| G-03 | Formulário dinâmico (BF05), foto por pergunta (NC03) e observação interna (BF11) funcionando offline, sobre a fila com idempotência; tratar `pergunta_fora_do_modelo` e `anexo_nao_permitido`. | §8 · Web G-02 |
| G-08 (app) | BF09 "peça levada" e BF11 fotos, quando o servidor tiver os campos. | Web G-08 |
| B11 | Dividir `api_service.dart` (847 linhas) e `sync_service.dart` (935) por domínio; remover as seções legadas que o servidor não usa mais (confirmar com o usuário). | — |
| G-04 (app) | Testes: estados da fila, idempotência, conflito, migrações do banco, tela de Pendências; teste de integração offline → online com servidor simulado. | — |

## Fase 4 — Sustentação

| Item | O que fazer |
|---|---|
| G-09 (app) | CI com `flutter analyze`, `flutter test`, OSV-Scanner no `pubspec.lock` e gitleaks (pergunte a plataforma). |
| G-10 (app) | Atualizar as 17 dependências presas a majors antigos (`flutter pub outdated`), uma por vez e com teste; Dependabot/Renovate para pub e Gradle. |
| — | `docs/RELEASE.md`: checklist de build de release (keystore, ofuscação, versão, hash do APK, publicação). |

**Fim da Fase 4:** rodar os blocos "App" e "Sincronização" do checklist (seção 7 do relatório) e entregar a nota reavaliada de A9 e B11, com evidência para cada item.

## Coordenação com a sessão Web

- **Ordem de deploy:** (1) app com a Fase 0 instalado nos aparelhos → (2) servidor liga o bloqueio de relatório (`423`) → (3) servidor publica o MFA da API e o app com a Fase 1 → (4) servidor liga as flags `API_EXIGIR_IDEMPOTENCIA`, `API_EXIGIR_IF_MATCH` e `MOBILE_EXIGIR_CABECALHO_VERSAO` só depois que todos os aparelhos estiverem na versão nova.
- Antes de cada item que depende do servidor, releia a tabela 12 do contrato.
- Dúvida ou divergência de contrato: pare e descreva ao usuário exatamente o que precisa mudar, para ele levar à sessão Web.

## Decisões que esta sessão vai precisar

D-03 (o que muda depois de Aprovado — afeta as telas) · D-04 (comprovante) · D-05 (retenção e CPF) · D-08 (MFA no app) · nome do novo `applicationId` · keystore de release · URL de produção e pinning · texto de privacidade.

## Modelo do relatório de fim de fase

```
Fase N — <nome>
| Item | Status (feito/bloqueado/aguardando servidor) | Versão | Commit | Teste que prova |
Dependências do servidor ainda pendentes: §x do contrato
Decisões pendentes: D-xx — pergunta exata
Ordem de deploy / cuidados para os técnicos: ...
```
