# Contrato da API FAÉ (`/api/fae/v1`) — mudanças do plano de segurança

> **Escopo exclusivo da FAÉ.** Este contrato vale só para a API `/api/fae/v1` da branch `feature/fae` e para o app `app_buildflow_fae`; nenhuma outra API, branch, banco ou aplicativo é afetado por ele. Ele é o acordo entre as **duas sessões da FAÉ** — a do servidor (Web) e a do app (Android) — para que os dois lados implementem o mesmo comportamento.

| | |
|---|---|
| **Versão** | 3 — Fase 1 do servidor (28/11/2026; ver Changelog) |
| **Vale para** | `/api/fae/v1` (branch `feature/fae`) e app `app_buildflow_fae`, exclusivamente. |
| **Dono** | Sessão **Web** (`sys-buildflow-fae`). Só ela altera este arquivo. |
| **Consumidor** | Sessão **Android** (`app_buildflow_fae`). Só lê. Se precisar de mudança, para e pede ao usuário para levar o pedido à sessão Web. |
| **Origem** | `auditoria-seguranca-arquitetura-fae-2026-11-27.md` (achados S-02, S-03, S-04, S-05, S-13, S-14, S-15, S-17, G-02) |

**Regras deste arquivo**
- A sessão Web pode ajustar detalhes de implementação, mas toda mudança de comportamento visível ao app sobe a versão deste contrato e entra no **Changelog** (final do arquivo).
- A coluna "Status" da seção 12 é atualizada pela sessão Web a cada entrega (versão BF_vX.Y.Z e commit). A sessão Android só implementa o lado do app de um item quando ele estiver "implementado" ali.
- Compatibilidade: o app publicado hoje (v1.3.0+8) não envia os cabeçalhos novos. Cada regra nova que o quebraria fica atrás de uma flag de configuração, desligada até a versão nova do app estar instalada nos aparelhos.

---

## 1. Envelope de erro

Toda resposta de erro da `fae/v1` traz:

```json
{ "message": "Texto para mostrar ao usuário.", "codigo": "slug_estavel" }
```

- `422` de validação mantém o formato do Laravel (`errors` por campo) e ganha `codigo: "validacao"`.
- O app decide o comportamento **pelo `codigo`**, nunca pelo texto de `message`.

## 2. Autenticação

### 2.1 Login — `POST /api/fae/v1/login`

Corpo: `{ "email", "senha", "device_name" }`.

| Situação | Status | Corpo |
|---|---|---|
| Sem MFA exigido para o usuário | 200 | `{ "token", "token_type": "Bearer", "expira_em", "usuario" }` |
| MFA exigido e cadastrado | 200 | `{ "mfa_pendente": true, "desafio", "desafio_expira_em" }` (sem token) |
| MFA exigido e não cadastrado | 403 | `codigo: "mfa_nao_cadastrado"` — cadastrar o autenticador pelo painel web |
| Credenciais inválidas | 422 | `codigo: "credenciais_invalidas"` (mesma mensagem para e-mail inexistente e senha errada) |
| Senha correta, usuário desativado | 403 | `codigo: "usuario_inativo"` — o app apaga os dados locais (ver §2.4) |
| Conta bloqueada (5 senhas erradas, 15 min, qualquer IP) | 429 | `codigo: "conta_bloqueada"`, cabeçalho `Retry-After` |

- `expira_em`: ISO 8601 com fuso (ex.: `2026-12-27T10:00:00-03:00`).
- `desafio`: string opaca aleatória, uso único, válida por 5 minutos, ligada ao usuário.
- **Decisão D-08 (28/11/2026): MFA no app para todos os perfis**, como no web. A regra fica atrás da flag `API_MFA_PERFIS` (`nenhum` | `todos`; valor desconhecido = `todos`). Hoje `nenhum`: o app publicado não sabe fazer o segundo passo. Passa a `todos` quando o app novo estiver nos aparelhos.
- O bloqueio por erros de código (5 → 15 min) e o "código já usado" são os mesmos do web: errar no app bloqueia o web e vice-versa.

### 2.2 Segundo passo — `POST /api/fae/v1/login/mfa`

Corpo: `{ "desafio", "codigo" }` (6 dígitos).

| Situação | Status | Corpo |
|---|---|---|
| Código certo | 200 | igual ao login sem MFA (`token`, `expira_em`, `usuario`) |
| Código errado ou já usado | 422 | `codigo: "mfa_invalido"` |
| Desafio vencido ou inexistente | 422 | `codigo: "desafio_invalido"` — voltar ao e-mail/senha |
| 5 códigos errados | 429 | `codigo: "mfa_bloqueado"`, `Retry-After` |

### 2.3 Token

- Habilidade única `fae-app`; nenhuma rota da `fae/v1` aceita token sem ela.
- Expira em 30 dias corridos **ou** em 7 dias sem uso (`SANCTUM_EXPIRACAO_DIAS`, `SANCTUM_INATIVIDADE_DIAS`).
- Token vencido: `401 codigo: "token_expirado"`. Sem token, token inválido ou revogado (troca de senha, desativação): `401 codigo: "nao_autenticado"`.
- `GET /me` também devolve `expira_em`.

### 2.4 Acesso offline e limpeza (lado do app)

- O app **não** apaga a fila de pendências num 401: pede login de novo e continua de onde parou, se for o mesmo usuário.
- Em `403 usuario_inativo` no login, o app apaga cache, fila, arquivos e credenciais.
- Acesso offline vence em 7 dias sem contato bem-sucedido com o servidor: o app exige login online e apaga o cache de leitura; as pendências do mesmo usuário continuam guardadas (cifradas) até o login.

## 3. Versão do app

- Toda requisição envia `X-App-Version: <versionName>+<buildNumber>` (ex.: `1.4.0+9`), inclusive o login.
- Abaixo de `MOBILE_APK_VERSAO_MINIMA`: `426 codigo: "versao_minima"` com `{ "versao_minima", "url_apk", "sha256_apk" }`.
- Sem o cabeçalho: aceito enquanto `MOBILE_EXIGIR_CABECALHO_VERSAO=false` (app antigo).
- `GET /app/versao` passa a devolver `{ "versao_minima", "obrigatoria", "url_apk", "sha256_apk" }`. `url_apk` sempre em HTTPS; o app confere o SHA-256 do arquivo baixado antes de instalar.

## 4. Idempotência

- Cabeçalho `Idempotency-Key: <UUID v4>` em todo `POST` que cria algo:
  `POST /atendimentos/{id}/relatorios`, `/relatorios/{id}/respostas`, `/relatorios/{id}/anexos`, `/relatorios/{id}/assinaturas`, `/relatorios/{id}/compartilhamentos`, `/relatorios/{id}/descricao-itens`, `/relatorios/{id}/servicos`, `/relatorios/{id}/pecas`, `/relatorios/{id}/ocorrencias`, `/clientes`, `/orcamentos`, `/orcamentos/{id}/comentarios`, `/roteiros-viagem`.
- O servidor guarda (usuário, chave, rota, hash do conteúdo, status e corpo da resposta) por 7 dias.
  - Mesma chave e mesmo conteúdo: devolve a resposta guardada, com o cabeçalho `Idempotent-Replayed: true`.
  - Mesma chave e conteúdo diferente: `409 codigo: "idempotencia_conflito"`.
  - Mesma chave ainda em processamento: `409 codigo: "idempotencia_em_andamento"` (o app tenta de novo depois).
  - Hash do conteúdo em `multipart`: campos + SHA-256 de cada arquivo.
- O app gera a chave **quando enfileira** a gravação e reutiliza a mesma chave em todas as retentativas.
- Sem o cabeçalho: comportamento antigo enquanto `API_EXIGIR_IDEMPOTENCIA=false`.

## 5. Relatório bloqueado (BF10)

- Escrita em relatório `Aprovado` (qualquer rota de escrita de relatório): `423 codigo: "relatorio_bloqueado"` com `status_relatorio`.
- **Depois de Aprovado continuam permitidos (decisão D-03):** a assinatura do **cliente** que ainda falta, uma única vez (`POST /assinaturas` com `cliente`); `POST /compartilhamentos`; `PUT /observacao-interna`. Assinatura do técnico, informações adicionais e todas as demais seções → `423`.
- Segunda assinatura do cliente em relatório Aprovado: `409 codigo: "assinatura_ja_registrada"`.
- Relatório Aprovado **e já assinado pelo cliente** não muda mais de status: `PUT /status` → `423`. Sem a assinatura do cliente, Assistência/Administrador ainda devolvem para Revisar e a edição volta.
- **Antes da aprovação nada muda** (decisão do usuário, 27/11/2026): o cliente assina em campo e pode refazer; **não existe** o `422 assinatura_cliente_antes_aprovacao` previsto na v1.
- Cada assinatura é gravada num arquivo novo (a anterior nunca é sobrescrita); a URL devolvida muda a cada assinatura.
- O app trata `423` como pendência com erro (§11), nunca descarta em silêncio.

## 6. Concorrência (cliente, orçamento, roteiro)

- O `GET` de cliente, orçamento e roteiro devolve `versao` (inteiro, sobe a cada gravação).
- `PUT` envia `If-Match: <versao>`. Versão diferente: `409 codigo: "conflito_versao"` com `atual` (a representação atual completa). Sem `If-Match`: aceito enquanto `API_EXIGIR_IF_MATCH=false`.
- Semântica do `PUT`: altera só os campos presentes no corpo. Listas (`contatos`, `equipamentos`, `localizacoes`) só são tocadas se a chave vier no corpo; item com `id` é atualizado, sem `id` é criado, item visível ausente é removido e item que o usuário **não** vê é preservado.

## 7. Contatos do cliente (NC01)

- A API omite os contatos que o usuário não pode ver (regra da **decisão D-01**).
- Cada contato traz `id`, `tipo` (0 Técnico, 1 Comercial) e `criado_por_id`.
- O app sempre devolve o `id` dos contatos existentes ao salvar (ver §6).

## 8. Revisão do modelo (NC02)

- Relatório e orçamento trazem `revisao_id`.
- `GET /relatorios/{id}/formulario` devolve as perguntas **da revisão do relatório** e o `revisao_id`.
- Resposta para pergunta que não está nessa revisão: `422 codigo: "pergunta_fora_do_modelo"`.
- Foto em pergunta sem "permite anexo": `422 codigo: "anexo_nao_permitido"`.
- O app guarda o formulário por `revisao_id` para preencher offline.

## 9. Mídia

- `GET /midia/{path}` continua aceitando o Bearer do app, agora com checagem de posse: `403 codigo: "sem_acesso_arquivo"` para arquivo de atendimento que o usuário não acessa.
- Arquivos novos têm nome aleatório (UUID). As URLs devolvidas no JSON continuam no formato `.../midia/<caminho>`. *(Ainda não implementado: entra com a remoção de EXIF, L-03/S-20; a checagem de posse já vale.)*

## 10. CPF e comprovante

- **CPF:** as respostas trazem `cpf_mascarado` (ex.: `***.456.789-**`) no lugar de `cpf`. O CPF completo só trafega no `POST` de assinatura. A **decisão D-05** pode remover o CPF de vez.
- **Comprovante (BF07/RNF06):** `POST /relatorios/{id}/compartilhamentos` com `{ "canal" }`. O servidor gera e guarda o PDF daquele envio, calcula o SHA-256, assina com HMAC e devolve `{ "id", "codigo_verificacao", "criado_em", "pdf_url", "pdf_sha256" }`. `codigo_verificacao` tem 10 caracteres em base32 (**decisão D-04**). O app baixa **esse** PDF (`pdf_url`) e o compartilha, sem gerar outro.

## 11. Tratamento de resposta na fila do app

| Status / código | Ação do app |
|---|---|
| 2xx, ou `Idempotent-Replayed` | Concluído; remove da fila |
| 401 `token_expirado` / `nao_autenticado` | Pausa a fila e pede login; mantém as pendências |
| 403 `usuario_inativo` (no login) | Limpeza total (§2.4) |
| 403 outros, 409, 422, 423 | Pendência **com erro**, visível ao usuário com a mensagem; ações reenviar/descartar (descartar pede confirmação) |
| 409 `idempotencia_em_andamento` | Retentativa com backoff |
| 409 `conflito_versao` | Tela de conflito: usar a versão do servidor ou reaplicar a minha sobre ela |
| 426 `versao_minima` | Bloqueia o envio e abre a atualização obrigatória |
| 429 | Respeita `Retry-After`; senão, backoff |
| 5xx, timeout, sem rede | Backoff exponencial com jitter (1 min, dobrando até 30 min) |

## 12. Status de implementação (atualizado pela sessão Web)

| Seção | Status | Versão web / commit | Flags |
|---|---|---|---|
| §1 Envelope de erro | parcial: `codigo` nas respostas de login, 401, 403 de mídia, 409 e 423; o resto na Fase 1 | BF_v1.15.13–1.15.15 | — |
| §2 Autenticação (lockout, expiração, `usuario_inativo`) | **implementado** (habilidade `fae-app`; tokens antigos com `*` valem até vencer; `expira_em` = validade absoluta) | BF_v1.15.14 / `5151cd6` | `SANCTUM_EXPIRACAO_DIAS=30`, `SANCTUM_INATIVIDADE_DIAS=7` |
| §2 MFA na API (`/login/mfa`) | **implementado** (D-08: todos os perfis) | BF_v1.17.0 | `API_MFA_PERFIS=nenhum` hoje → `todos` com o app novo |
| §3 Versão do app | pendente | — | `MOBILE_EXIGIR_CABECALHO_VERSAO` |
| §4 Idempotência | pendente | — | `API_EXIGIR_IDEMPOTENCIA` |
| §5 Relatório bloqueado | **implementado** (regras da v2) | BF_v1.15.15 / `628f474` | — |
| §6 Concorrência | pendente | — | `API_EXIGIR_IF_MATCH` |
| §7 Contatos | **implementado** (visibilidade D-01, `criado_por_id`, sincronização por `id` preservando ocultos) | BF_v1.15.18 / `a9a2ab5` | — |
| §8 Revisão do modelo | pendente | — | — |
| §9 Mídia | parcial: posse e `sem_acesso_arquivo` implementados; nome UUID pendente (L-03) | BF_v1.15.13 / `074726d` | — |
| §10 CPF e comprovante | pendente | — | — |

## Changelog

- **v1 (27/11/2026):** proposta inicial, derivada da auditoria.
- **v2 (28/11/2026):** §5 conforme as decisões do usuário — o cliente continua assinando antes da aprovação (sem o `422 assinatura_cliente_antes_aprovacao`); depois de Aprovado, só a assinatura do cliente que falta (uma vez), compartilhamentos e observação interna; aprovado e assinado pelo cliente não muda de status. §9: nome UUID adiado. Seção 12 com o status da Fase 0. **Repassar à sessão Android.**
- **v3 (28/11/2026, Fase 1 do servidor):** §2.1/§2.2 — D-08 decidida (MFA para todos os perfis), flag `API_MFA_PERFIS` (`nenhum`/`todos`), bloqueio de código compartilhado com o web. **Repassar à sessão Android.**
