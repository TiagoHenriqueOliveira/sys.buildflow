# Acompanhamento — correções da auditoria (sessão Web)

Relatório: `auditoria-seguranca-arquitetura-fae-2026-11-27.md` (base: commit `de25f8c`).
Contrato da API: `CONTRATO-API-FAE-V1.md` (versão 2, 28/11/2026).
Status: **feito** / **parcial** / **bloqueado** / **não se aplica**.

## Decisões do usuário registradas

| Decisão | Resposta (27–28/11/2026) |
|---|---|
| Banco de teste (G-04) | Criar `buildflow_fae_test` exclusivo |
| Rotas legadas (S-01) | Remover só da `feature/fae` (logs: nenhum aparelho usa; 1 chamada de teste local em 09/09) |
| Texto do PDF (S-16) | "A FAÉ Bioenergia, neste ato representada pelo técnico abaixo assinado, …" |
| D-03 — depois de Aprovado | Só assinatura do cliente que falta (1 vez), envio (compartilhamento) e observação interna |
| D-03 — devolver relatório assinado pelo cliente | Ninguém: aprovado e assinado é definitivo |
| Assinatura do cliente antes da aprovação | **Manter o fluxo de campo** (cliente assina e pode refazer antes da aprovação). Divergência aceita em relação ao critério do BF10 |
| D-01 — dono do contato Comercial | Quem cadastrou **e** o vendedor responsável do cliente |
| D-01 — perfil Comercial | Vê os contatos comerciais de todos |
| Migration de contatos em produção | Autorizada, com backup antes (feito em 28/11/2026) |

## Fase 0 — Contenção

| Passo | ID | Status | Versão BF | Commit | Teste que prova | Observações |
|---|---|---|---|---|---|---|
| 0.1 | G-04 (parte) | feito | 1.15.8 | `b0c1cf0` | suíte inteira roda em `buildflow_fae_test` (fixado no `phpunit.xml` com `force`) | Linha de base no banco novo: 293 passando, 6 falhas antigas (todas de testes da API legada, removidos no 0.3). Ao fim da fase: 317 passando, 0 falhas |
| 0.2 | S-06 | feito | 1.15.9 | `7becd0d` | `composer audit --locked`: nenhum aviso; `npm audit --omit=dev`: 0 | Exceção: 3 avisos só de desenvolvimento (esbuild/vite/laravel-vite-plugin, usados no build) exigem troca de versão major — reavaliar na Fase 4 (G-10) |
| 0.3 | S-01 | feito | 1.15.10 (+1.15.17) | `a231dd1`, `ae8da9a` | `RelatorioApiRegrasFaeTest` (12), `SegurancaRotasFaeTest` | 58 rotas, todas `api/fae/v1/*`. Regras que valem na FAÉ portadas sem mudança (REL-02, REL-03, criação avançando o atendimento, validações, listagem por técnico, exclusão de anexo com o arquivo). `AuthTest` passou a usar a `fae/v1` |
| 0.4 | S-07 | feito | 1.15.11 | `4e489f1` | `VisibilidadeVendedorFaeTest::test_resumo_do_cliente_respeita_o_vendedor_e_a_permissao` | Continua liberado a quem usa a tela de Atendimento (BF03) |
| 0.5 | S-16 (texto) | feito | 1.15.12 | `430b608` | `AtendimentoRelatorioHorariosFaeTest::test_pdf_da_entrega_tecnica_cita_a_fae_e_nao_outra_empresa` | — |
| 0.6 | S-04 | parcial | 1.15.13 | `074726d` | `MidiaPosseFaeTest` (5) | Posse feita (mesma regra das telas). **Pendente:** nome UUID em arquivos novos — a aba Anexos exibe o nome tirado do caminho e não há coluna de nome original; trocar agora mudaria o que o usuário vê. Entra com L-03/S-20 (Fase 2) |
| 0.7 | S-05 (parte 1) | feito | 1.15.14 | `5151cd6` | `ApiAutenticacaoFaeTest` (11) | Bloqueio por conta (mesma chave do web), `usuario_inativo`, expiração 30 dias / 7 dias sem uso (inclusive tokens antigos), habilidade `fae-app`, `expira_em`, política de senha (10+, letras e números, vazamentos). Botão "Sugerir" garante letra e número |
| 0.8 | S-02 | feito (com divergência aceita) | 1.15.15 | `628f474` | `RelatorioBloqueadoFaeTest` (10) | Guarda única em 33 rotas (17 web, 16 API). Assinatura do cliente antes da aprovação mantida por decisão do usuário. **Pendente:** SHA-256 do PDF no momento da assinatura (entra com o snapshot do PDF do S-17, Fase 1); hoje guarda o SHA-256 da imagem na auditoria |
| 0.9 | S-03 | feito | 1.15.18 | `a9a2ab5` | `ContatosClienteFaeTest` (6) | Migration `2026_11_28_090000` rodada em produção. PDF e histórico não exibem contatos do cliente (não se aplica). Equipamentos e localizações mantêm o "substitui a lista" (não há item oculto nelas) |
| — | Revisão de segurança (MINOR) | feito | 1.16.0 | `d8ebf5c` | `SegurancaRotasFaeTest` (2) | Ver seção abaixo |

## Revisão de segurança — fechamento da Fase 0 (BF_v1.16.0)

184 rotas. Fora de `auth`/`sanctum`, todas intencionais (travadas pelo `SegurancaRotasFaeTest`):

| Rota | Motivo |
|---|---|
| `GET /`, `POST login`, `GET/POST login/codigo`, `POST login/esquecer` | login web (guest, com limitador) |
| `POST api/fae/v1/login` | login do app (limitador por IP+e-mail e bloqueio por conta) |
| `GET api/fae/v1/app/versao` | o app confere a versão antes do login |
| `GET apk/{arquivo}` | instalador do app |
| `GET sanctum/csrf-cookie` | padrão do Sanctum |
| `_ignition/*` | ferramenta de depuração; com `APP_DEBUG=false` (produção) devolve 404 — testado |

Achados desta revisão para a Fase 1: rotas `admin/*` de manutenção por GET (S-19); `clientes/autocomplete` e `clientes/{id}/resumo` sem `can:` na rota (a checagem está no controller desde o S-07).

## Checklist §7 — itens dos achados Altos

| Item | Situação | Evidência |
|---|---|---|
| API só `api/fae/v1/*`; 404 nas rotas legadas | ok | `RelatorioApiRegrasFaeTest::test_apis_herdadas_nao_existem_mais`, `SegurancaRotasFaeTest` |
| Login da API: bloqueio por conta; token expira; habilidade restrita | ok | `ApiAutenticacaoFaeTest` |
| Login da API: MFA | pendente (S-05 parte 2, Fase 1, D-08) | — |
| Senha ≥ 10 com checagem de vazamento | ok | `ApiAutenticacaoFaeTest::test_politica_de_senha…`, `…vazamento…` |
| Troca de e-mail/senha exige a senha atual; MFA não cadastrável só com a senha | pendente (S-08, Fase 1) | — |
| `/midia`: 403 para outro técnico; 200 para dono e acesso total | ok | `MidiaPosseFaeTest` |
| `clientes/{id}/resumo` respeita `visivelPara` | ok | `VisibilidadeVendedorFaeTest` |
| Contato Comercial oculto (HTML/JSON) e preservado ao editar | ok | `ContatosClienteFaeTest` |
| Escrita em relatório Aprovado → 423 (web e API) | ok | `RelatorioBloqueadoFaeTest` |
| Assinatura do cliente só após aprovação | divergência aceita (decisão do usuário) | — |
| Assinatura do cliente uma vez após aprovação; arquivo imutável | ok | `RelatorioBloqueadoFaeTest` |
| Hash do PDF na assinatura | pendente (S-17) | — |
| Nenhum texto de outra empresa nos PDFs | ok | `AtendimentoRelatorioHorariosFaeTest` |
| `composer audit --locked` sem avisos | ok | saída do comando em 27/11/2026 |
| Matriz perfil × rota | pendente (G-04, Fase 3) | — |

## Ocorrências desta fase

- **Isolamento dos testes:** assinaturas e miniaturas eram gravadas com `storage_path()` direto, fora do disco `public`, e por isso a suíte gravava PNGs de teste (100 bytes) na pasta real `storage/app/public/atendimentos_relatorios/{id}/assinaturas/`. Nenhuma assinatura real foi sobrescrita. Corrigido na 1.15.15 (disco `public` + `Storage::fake('public')` em toda a suíte). Os arquivos de teste que ficaram na pasta real aguardam autorização do usuário para serem apagados.
- **Deploy:** esta pasta é a produção; cada mudança entrou no ar ao ser gravada. Tokens do app emitidos há mais de 30 dias, ou sem uso há 7, passam a pedir login de novo.
