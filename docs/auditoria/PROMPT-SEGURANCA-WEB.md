# Prompt — Sessão Web: plano de correção da auditoria até nota 9

> Cole na sessão aberta em `C:\Apache24\htdocs\sys-buildflow-fae`:
> **"Leia e execute `docs/auditoria/PROMPT-SEGURANCA-WEB.md`. Comece pela Fase 0 e pare ao fim dela."**

## Objetivo

Corrigir, no sistema web e na API `fae/v1` da FAÉ, todos os achados da auditoria de 27/11/2026 que cabem ao lado do servidor, até que todos os critérios A1–A11 e B1–B11 cheguem a nota ≥ 9 e não reste achado Alto/Crítico aberto. O app Flutter é de outra sessão (`PROMPT-SEGURANCA-ANDROID.md`); **não edite o repositório `app_buildflow_fae`**.

## Leia antes de começar

1. `CLAUDE.md` deste repositório (regras do projeto, versionamento, revisão de segurança a cada MINOR).
2. `docs/auditoria/auditoria-seguranca-arquitetura-fae-2026-11-27.md` — principalmente as seções 5 (achados com evidência), 6 (plano) e 7 (checklist). Os IDs abaixo (S-xx, G-xx, L-xx, D-xx) são os do relatório.
3. `docs/auditoria/CONTRATO-API-FAE-V1.md` — **esta sessão é dona do contrato**. Implemente a API exatamente como ele descreve; se precisar mudar algo, suba a versão do contrato, registre no Changelog e avise o usuário para repassar à sessão Android.

## Regras da sessão

- **Responda em pt-BR.**
- **Só FAÉ.** Todo o trabalho é na branch `feature/fae` e na API `/api/fae/v1`. Não copie regra, rota ou comportamento da MCL (`feature/mcl`, `/api/mcl/v1`) e não altere nada que a MCL use — cada cliente tem sua própria API, banco e app. O código da MCL que ainda está nesta branch sai no passo 0.3 (S-01).
- **Confira antes de corrigir.** O relatório vale para o commit `de25f8c`; o repositório mudou depois (outras sessões trabalham aqui). Para cada item, rode `git log de25f8c..HEAD` nos arquivos citados, confirme que o problema ainda existe e só então corrija. Se já foi resolvido ou mudou, registre isso no acompanhamento em vez de refazer.
- **Não reverta mudanças de outras sessões.** Antes de cada commit, rode `git status` e commite só os seus arquivos.
- **Uma fase por rodada.** Ao terminar a fase, pare, entregue o relatório de fim de fase (modelo no final) e espere o usuário dizer "continuar".
- **Decisões do cliente:** se um item depende de uma decisão D-xx ainda não respondida, pergunte ao usuário (AskUserQuestion) com as opções do relatório, implemente o resto da fase e deixe o item marcado como "bloqueado por D-xx". Nunca assuma a resposta.
- **Banco de dados:** nunca rode comando que apague ou altere dados (local ou produção) sem confirmar com o usuário antes. A suíte PHPUnit usa `RefreshDatabase` sobre `buildflow_test`, banco compartilhado com outros projetos — veja o passo 0.1.
- **Versão e commits:** padrão `BF_vMAJOR.MINOR.PATCH` do `CLAUDE.md` (correção = PATCH; funcionalidade nova, como MFA na API ou revisão de modelo = MINOR). Antes de alterar `APP_VERSION`, releia o valor atual no `.env` (ele não aparece no `git diff`). Pode commitar; **nunca faça push**.
- **Revisão de segurança a cada MINOR**, como manda o `CLAUDE.md` (mapear rotas fora de `auth`/`sanctum`).
- **Testes:** todo item fechado ganha teste automatizado que prova o critério de aceite do relatório. Um item só conta como "feito" com o teste passando.
- **Paridade web × API:** toda regra nova vale nas rotas web **e** na `fae/v1`, de preferência no mesmo Service/Policy.
- **Acompanhamento:** mantenha `docs/auditoria/ACOMPANHAMENTO-WEB.md` com uma linha por ID: status (feito / bloqueado / não se aplica), versão BF, commit, teste que prova. Atualize a seção 12 do contrato a cada item de API entregue.

## Fase 0 — Contenção (elimina os achados Altos)

Ordem sugerida (do mais barato e sem dependência ao que depende de decisão):

| Passo | ID | O que fazer | Aceite (resumo — detalhe no relatório §6.1) |
|---|---|---|---|
| 0.1 | G-04 (parte) | Propor ao usuário um banco de teste exclusivo (`buildflow_fae_test`) e apontar o `.env.testing` para ele. Criar o banco **só depois da confirmação**. Rodar a suíte uma vez para registrar a linha de base. | Suíte rodando em banco exclusivo; resultado da linha de base anotado no acompanhamento |
| 0.2 | S-06 | `composer update` dentro de `^13` e `npm audit fix`; rodar a suíte. | `composer audit --locked` e `npm audit --omit=dev` sem avisos (ou exceções justificadas) |
| 0.3 | S-01 | Remover `/api/v1` e `/api/mcl/v1` (rotas, controllers `Api/Mcl/*` e `Api/{Atendimentos,Relatorios,Catalogo}Controller`, `Requests/Mcl/*`, testes `*Mcl*`), portando para a `fae/v1` o que ainda for usado. Antes, pergunte ao usuário se há logs de produção mostrando algum aparelho chamando essas rotas. | `route:list --path=api` só com `api/fae/v1/*`; testes 404 para as rotas antigas |
| 0.4 | S-07 | `clientes/{id}/resumo` com `visivelPara` e permissão. | Teste: Vendedor A → 403 no cliente do Vendedor B |
| 0.5 | S-16 (texto) | Trocar "A MCLvale…" em `pdf.blade.php`. **Pergunte ao usuário o texto aprovado pela FAÉ.** | HTML do PDF sem "MCL" |
| 0.6 | S-04 | Posse no `/midia` (prefixo do caminho → registro dono → `garantirPosse`; resto só acesso total); arquivos novos com nome UUID. Contrato §9. | Teste: técnico B → 403 no arquivo do técnico A; dono e administrador → 200 |
| 0.7 | S-05 (parte 1) | Login da API: bloqueio por conta igual ao web, `usuario_inativo`, expiração do token (absoluta e por inatividade), habilidade `fae-app`, `expira_em` na resposta, política de senha `Password::min(10)->letters()->numbers()->uncompromised()` em todos os cadastros de senha. Contrato §1, §2.1, §2.3. | Testes do relatório §6.1 (S-05) |
| 0.8 | S-02 | Guarda única de "relatório editável" em **todas** as rotas de escrita (web e API) → `423 relatorio_bloqueado`; assinatura do cliente só após aprovação e uma vez; arquivo de assinatura imutável com SHA-256 do PDF; status e assinatura na mesma transação. Contrato §5. **Depende de D-03.** | Testes do relatório §6.1 (S-02) |
| 0.9 | S-03 | `cli_cont_usuario_id` (autor) + escopo `contatosVisiveisPara`; aplicar em formulário web, API, histórico e resumo; trocar o "apaga e recria" das listas do cliente por sincronização por `id`, preservando o que o editor não vê. Contrato §6 (semântica do PUT) e §7. **Depende de D-01.** | Testes do relatório §6.1 (S-03) |

**Atenção ao deploy do S-02:** hoje o app descarta da fila qualquer resposta 4xx. Se o `423` for para produção antes de o app novo (Fase 0 da sessão Android) estar instalado nos aparelhos, o técnico perde em silêncio o que gravou offline num relatório já aprovado. Implemente e teste, mas avise o usuário dessa ordem no relatório de fim de fase.

**Fim da Fase 0:** revisão de segurança (MINOR), itens do checklist §7 do relatório correspondentes aos achados Altos, relatório de fim de fase.

## Fase 1 — Segurança restante e sincronização segura (lado servidor)

| ID | O que fazer | Contrato / dependência |
|---|---|---|
| S-05 (parte 2) | MFA na API: `POST /login` com desafio e `POST /login/mfa`. Perfis obrigados conforme **D-08** (pergunte). | §2.1, §2.2 |
| S-08 | Cadastro do MFA por código de ativação de uso único gerado pelo administrador (ou prazo de 24 h após a criação da conta); senha atual + código MFA para trocar e-mail/senha; recuperação de senha com código de uso único. | — |
| S-14 (servidor) | Middleware de idempotência; `versao` + `If-Match` em cliente/orçamento/roteiro; `PUT` parcial; `X-App-Version` com `426`. Todas atrás das flags do contrato, desligadas por padrão. | §3, §4, §6 |
| S-11 | Auditoria de toda escrita (web e API) por observer/middleware; trilha de leitura de cliente/contato/relatório; menu de auditoria visível ao administrador; `logs_auditoria` só de inserção (usuário de banco sem UPDATE/DELETE nela — peça ao usuário para aplicar o GRANT) e encadeamento por hash. | — |
| S-12 | Conta do fornecedor visível (só leitura) aos administradores da FAÉ, sem acesso à API, toda ação auditada. **Depende de D-06.** | — |
| S-17 | Comprovante com snapshot do PDF, SHA-256 e HMAC; `codigo_verificacao` de 10 caracteres; tabela só de inserção; rota de verificação. **Depende de D-04.** | §10 |
| S-09 | Vídeo: validar com `ffprobe`, ffmpeg com demuxer explícito (sem HLS/concat), em fila (`QUEUE_CONNECTION=database`); limite menor que 100 MB ou upload em partes; cota por relatório. | — |
| S-18 | CSP com nonce, sem `unsafe-inline` (scripts inline para módulos Vite) e sem `unsafe-eval` (build CSP do Alpine); HSTS com `includeSubDomains`; `SESSION_SECURE_COOKIE=true` por padrão. | — |
| S-19 | Remover as rotas de manutenção por GET (ou POST + CSRF + confirmação). | — |
| S-21, S-22 | Limite de tamanho da assinatura base64; pergunta precisa pertencer ao modelo; foto só com "permite anexo". | §8 (códigos 422) |
| S-23 | Limitador da API pela identidade do token; `TrustProxies` configurável por variável de ambiente (faixas da Cloudflare). | — |
| S-15 / L-02 | `cpf_mascarado` na API e no PDF; CPF cifrado em repouso (cast `encrypted`) se for mantido. **Depende de D-05.** | §10 |

**Fim da Fase 1:** revisão de segurança, seção 12 do contrato atualizada (o que já pode ser ligado), relatório de fim de fase com a lista de flags e a ordem sugerida para ligá-las depois que o app novo estiver em campo.

## Fase 2 — LGPD (técnico)

| ID | O que fazer | Dependência |
|---|---|---|
| L-01 | Rascunho do inventário de dados (ROPA) em `docs/lgpd/`, por tabela/campo, finalidade, perfis com acesso e retenção proposta — para o jurídico validar. | Jurídico |
| L-03 / S-20 | Remover EXIF de toda imagem no upload; nomes UUID. | — |
| L-04 | Anonimização/exclusão de cliente, contato e usuário (preservando o que a lei obriga, como relatório assinado) e exportação dos dados de um titular. | Jurídico |
| L-05 | Comando agendado de retenção (fotos, logs, notificações, snapshots de PDF). | D-05 |
| L-06 / S-10 | Remover `BackupAutomatico`; script de backup para o servidor (`mysqldump --single-transaction` + mídias, cifrado, cópia externa) e roteiro de restauração em `docs/`. | Hospedagem |
| L-07 | Página de política de privacidade e aviso no primeiro acesso do web (texto do jurídico). | Jurídico |
| L-08 | Relatório "quem acessou os dados do titular X", sobre a trilha do S-11. | S-11 |

## Fase 3 — Padrões e arquitetura (B1–B11)

| ID | O que fazer | Dependência |
|---|---|---|
| G-02 | Revisão do modelo do Configurador: tabela de revisões (snapshot), `revisao_id` em relatório e orçamento, nova revisão quando o modelo já tem uso, `formulario` por revisão. Contrato §8. | D-02 |
| G-01 | Policies para Cliente, Orçamento, Roteiro e Relatório; API Resources em toda a `fae/v1`; regras de relatório num Service único (web e API); dividir `AtendimentosRelatoriosController`; Pint + Larastan nível ≥ 6 limpos. | S-01, S-02 |
| G-04 | Corrigir ou remover as 6 falhas conhecidas; teste para cada critério de aceite do escopo e para cada S-xx; cobertura ≥ 70% em `app/Http`, `app/Models`, `app/Services`. | 0.1 |
| G-05 | `/up` (health), log JSON com `request_id`, integração com coletor de erros (pergunte qual), alertas de 5xx/fila/backup. | Hospedagem |
| G-06 / S-24 | README próprio, `.env.example` comentado, OpenAPI 3.1 da `fae/v1` validado em teste, runbook de incidentes. | — |
| G-07 | Agrupamento de marcadores nos mapas; PDF com miniaturas redimensionadas; miniaturas de vídeo em fila. | S-09 |
| G-08 (web) | BF09 "peça levada"; BF11 fotos na observação interna; CRM03 comentários no cliente; CRM08 filtro de vendedor; BF01 regra de perfil. Formalizar em `docs/` as divergências aceitas (D-07). | D-07 |
| S-16 (resto) | Remover docs, Postman e testes restantes da MCL. | S-01 |

## Fase 4 — Sustentação

| ID | O que fazer |
|---|---|
| G-09 | CI (pergunte a plataforma; GitHub Actions é o padrão do repositório remoto): Pint, Larastan, PHPUnit, `composer audit`, `npm audit`, gitleaks. |
| G-10 | Dependabot ou Renovate para Composer e npm; política de atualização mensal documentada. |
| G-11 | Preencher o checklist do Apêndice A do relatório com o que depende do código (ex.: `.env` de produção de referência, `TrustProxies`) e listar para o usuário o que depende da hospedagem. |
| G-12 | Preparar o escopo do teste de intrusão externo (rotas, perfis de teste, ambiente de homologação). |

**Fim da Fase 4:** rodar o checklist completo da seção 7 do relatório e entregar uma tabela de notas reavaliadas por critério (A1–A11, B1–B11), com evidência para cada nota ≥ 9.

## Decisões que esta sessão vai precisar (pergunte quando chegar nelas)

D-01 dono do contato comercial · D-02 modelo de revisão · D-03 o que muda depois de Aprovado · D-04 comprovante e PDF de orçamento · D-05 retenção e CPF · D-06 conta do fornecedor · D-07 divergências de escopo · D-08 perfis com MFA no app · texto do PDF (S-16) · plataforma de CI e coletor de erros.

## Modelo do relatório de fim de fase

```
Fase N — <nome>
| ID | Status (feito/bloqueado/não se aplica) | Versão BF | Commit | Teste que prova |
Decisões pendentes: D-xx — pergunta exata
Contrato: seções alteradas / status atualizado
Riscos e ordem de deploy: ...
Notas estimadas após a fase: A1.. B11
```
