# Auditoria de Segurança e Arquitetura — Buildflow FAÉ Bioenergia (Web + API + App)

| | |
|---|---|
| **Data** | 27/11/2026 |
| **Escopo** | Sistema web Laravel (`C:\Apache24\htdocs\sys-buildflow-fae`, branch `feature/fae`, commit `de25f8c` — BF_v1.15.4), API `/api/*` e app Flutter (`C:\Users\tiago\StudioProjects\app_buildflow_fae`, branch `feature/fae`, commit `a03c348` — v1.3.0+8) |
| **Critério de escopo** | Documento de Requisitos FAÉ (baseline 09/09/2026) e Especificações Técnicas v2 (08/09/2026) |
| **Método** | Leitura do código-fonte, configuração, migrations, testes e dependências; `composer audit`, `npm audit` e `flutter pub outdated` executados em 27/11/2026. Nenhum teste dinâmico contra ambiente publicado. |
| **Veredito** | **REPROVADO** — nenhum critério atinge 9; 6 achados de severidade Alta em aberto (veto). |

> Caminhos `app/...`, `routes/...`, `config/...`, `database/...`, `resources/...` referem-se ao repositório web (`sys-buildflow-fae`). Caminhos `lib/...`, `android/...`, `tool/...` referem-se ao app (`app_buildflow_fae`).

---

## 1. Resumo executivo

### Veredito

**Reprovado.** O sistema tem uma base boa em vários pontos (login web com verificação em duas etapas, bloqueio por tentativas, perfis de acesso configuráveis, restrição do perfil Vendedor bem aplicada, segredos fora do repositório, PDF gerado com configuração segura). Mas há falhas que permitem a usuários comuns ver ou alterar o que não deveriam, e o app ainda não está pronto para produção. Pela regra combinada (todos os critérios ≥ 9 e nenhum achado Alto/Crítico aberto), o sistema não pode ser aprovado agora.

### Notas por critério (0–10)

| Segurança | A1 | A2 | A3 | A4 | A5 | A6 | A7 | A8 | A9 | A10 | A11 |
|---|---|---|---|---|---|---|---|---|---|---|---|
| Nota | 5 | 4 | 7 | 3 | 7 | 5 | 4 | 5 | 4 | 4 | 5 |

| Código e arquitetura | B1 | B2 | B3 | B4 | B5 | B6 | B7 | B8 | B9 | B10 | B11 |
|---|---|---|---|---|---|---|---|---|---|---|---|
| Nota | 6 | 6 | 6 | 6 | 5 | 5 | 6 | 4 | 6 | 5 | 6 |

### Os 5 principais riscos (em linguagem não técnica)

1. **Relatório aprovado e assinado ainda pode ser alterado.** A tela trava a edição, mas o servidor não. Um técnico consegue mudar o conteúdo de um relatório depois de aprovado e até depois da assinatura do cliente — e, por uma API antiga herdada de outro cliente que continua ligada, consegue aprovar o próprio relatório sem o supervisor. Isso compromete o valor do relatório como prova.
2. **Dados comerciais visíveis para quem não deveria.** O escopo exige que contatos comerciais fiquem só com o vendedor dono e os administradores. Hoje qualquer perfil que acessa Clientes (incluindo Assistência, por padrão) vê todos os contatos, e o app recebe todos eles.
3. **Fotos e assinaturas de qualquer atendimento abertas para qualquer usuário logado.** O endereço dos arquivos é previsível (ex.: assinatura do cliente do relatório nº X) e o sistema só confere se a pessoa está logada, não se o arquivo é de um atendimento dela.
4. **O app entra só com e-mail e senha, e o acesso nunca expira.** O painel web exige o código do autenticador, mas a API do app não — inclusive para administradores. O "crachá" digital do app não vence e fica guardado no celular sem a proteção do cofre do Android.
5. **LGPD ainda não tratada e componentes desatualizados.** Não há política de privacidade, regras de retenção ou exclusão, e há dados não mapeados (o CPF de quem assina pelo cliente vai impresso no PDF compartilhado por WhatsApp). Bibliotecas do servidor têm 18 alertas de segurança públicos, um deles explorável sem login para derrubar o serviço.

### Esforço até a aprovação

A contenção (Fase 0) é de esforço **pequeno a médio** e elimina todos os achados Altos. Chegar a 9 em todos os critérios exige ainda as Fases 1–4 (sincronização segura, LGPD, arquitetura e sustentação), com destaque para três itens de produto que dependem de decisão do cliente: revisão versionada do modelo (NC02), formulário dinâmico offline (BF05/RNF01) e o tratamento de LGPD.

---

## 2. Contexto, premissas, lacunas e limitações

### 2.1 Contexto confirmado no código

| Item | Situação real |
|---|---|
| Framework | **Laravel 13.31.0** (`composer.lock`), `laravel/framework ^13.0` (`composer.json`). Dentro do ciclo de suporte (política padrão do Laravel: 18 meses de correções e 2 anos de segurança a partir do lançamento no 1º tri/2026 — confirmar datas em laravel.com/docs/releases). A versão instalada está 9 patches atrás de 13.40.0, que corrige um aviso de segurança (ver S-06). |
| PHP | `config.platform.php = 8.4.2` (`composer.json`). PHP 8.4 tem suporte ativo até 31/12/2026 e de segurança até 31/12/2028. O patch 8.4.2 é antigo; o patch real do servidor não foi verificado. |
| Banco | MySQL, banco `buildflow_fae`; 74 migrations versionadas na branch. Banco de teste `buildflow_test` **compartilhado** com o setup padrão do projeto (`CLAUDE.md`). |
| Autenticação web | Sessão Laravel + MFA TOTP obrigatório (`ExigirMfa`, `MfaService`), bloqueio de 15 min após 5 senhas erradas por e-mail, sessão com prazo de 6 h (`SessaoComPrazo`). |
| Autenticação da API | **Laravel Sanctum 4.3.3, token pessoal (Bearer)**, emitido só com e-mail + senha (`app/Http/Controllers/Api/AuthController.php:20-47`), sem MFA e sem expiração (`config/sanctum.php:49`). |
| Autorização | Catálogo de permissões por tela (`App\Support\Permissoes`) + `Gate::before` (`AuthServiceProvider`) + `can:<tela>.<acao>` nas rotas; posse de atendimento por `AtendimentoPolicy`; escopos `visivelPara()` em Cliente/Orçamento/Roteiro só para o perfil Vendedor. |
| Perfis reais | `NivelAcesso`: Administrador=0, Técnico=1, Comercial=2, Assistência=3 (aprova relatórios — faz o papel do "supervisor"), Vendedor=4, além de um "usuário protegido" do fornecedor. |
| App | Flutter, `provider` + `go_router`, `sqflite` (cache e fila), `shared_preferences` (token), consome só `/api/fae/v1`. |

### 2.2 Premissas

- A auditoria vale para o código no estado dos commits citados. **Durante a auditoria, outra sessão alterou o repositório web** (commit `de25f8c` e uma mudança não commitada em `Api/Fae/ClientesController.php`, que passa a devolver `vendedor_id` e `dias_alerta_recontato`). As citações de linha se referem ao `HEAD de25f8c`; essa mudança não altera nenhum achado. No app, havia uma alteração local só no IP de desenvolvimento (`app_config.dart`).
- "Supervisor" do escopo = perfil **Assistência** no código (`AtendimentoRelatorioStatus::usuarioPodeAprovar`).
- Pré-cadastro com aprovação (NC01) foi **removido do escopo por decisão do cliente** em 16/09/2026 (registro interno do projeto). Tratado como divergência de escopo a formalizar, não como defeito.
- Hospedagem: o documento *Requisitos de Infraestrutura* (24/09/2026) recomenda VPS + Cloudflare, mas nada consta como contratado. Um comentário em `app/Http/Middleware/CabecalhosDeSeguranca.php:35-37` cita algo "visto em produção em 27/11/2026" — **confirmar se já existe ambiente publicado com dados reais**; se existir, a Fase 0 passa a ser urgente.

### 2.3 Lacunas (informação ausente)

| Lacuna | Efeito na avaliação |
|---|---|
| Ambiente de produção (servidor, TLS, firewall, backups) | A10 avaliado só como prontidão/documentação; requisitos mínimos no Apêndice A. |
| `.env` de produção | Configuração avaliada pelo `.env` local (APP_ENV=production, APP_DEBUG=false, SESSION_DRIVER=database) e pelos padrões de `config/*`. Não existe `.env.example` no repositório. |
| URL de produção do app | `lib/core/constants/app_config.dart:15` usa `https://PLACEHOLDER-fae-producao.invalid` — o app de release ainda não aponta para servidor real. |
| Responsável LGPD / DPO, contrato controlador-operador | O bloco LGPD separa o que é técnico do que é jurídico. |
| Inventário de dados de produção | Não houve acesso a dados reais; volumes (fotos, clientes) não medidos. |

### 2.4 Limitações

- **Testes não executados.** A suíte PHPUnit usa `RefreshDatabase` sobre o banco `buildflow_test`, compartilhado; rodá-la recria esse banco. Contagem estática: 289 métodos de teste; o próprio projeto registra 6 falhas pré-existentes em `AtendimentoRelatorioTest`. Os testes do app (3 arquivos) também não foram executados.
- Sem teste dinâmico (DAST), sem medição de desempenho (N+1, tempo de PDF) e sem varredura de CVE para pacotes Dart (não há ferramenta instalada; só `flutter pub outdated`).
- iOS não analisado (o projeto existe, mas a distribuição prevista é Android/APK).
- Histórico git conferido de forma pontual (busca por `.env`, keystore, `google-services.json`): nada encontrado.

---

## 3. Notas por critério

Âncoras: 10 exemplar · 9 aderente · 7–8 lacunas relevantes · ≤6 deficiente · ≤3 crítico/ausente. Achado Alto aberto limita o critério a 6.

| Crit. | Nota | ≥9? | Justificativa resumida | Gap até 9 |
|---|---|---|---|---|
| **A1** Autenticação e sessão | 5 | Não | Web forte (bcrypt, bloqueio por conta, MFA TOTP sem reuso de código, prazo de 6 h, revogação de sessões/tokens na troca de senha). API sem MFA, token sem expiração, sem bloqueio por conta, senha mínima de 6 caracteres, cadastro inicial do MFA só com senha. **Veto S-05.** | S-05, S-08 |
| **A2** Autorização | 4 | Não | Fundamento bom (permissões por tela, `AtendimentoPolicy`, `visivelPara`), mas: APIs legadas fora da matriz, contatos comerciais sem restrição, `/midia` sem posse, `clientes/{id}/resumo` sem checagem, relatório aprovado sem trava no servidor. **Veto S-01..S-04.** | S-01, S-02, S-03, S-04, S-07 |
| **A3** Validação e injeções | 7 | Não | Eloquent/bindings em todo lugar (único `selectRaw` com bindings), Blade escapado, `escapeHtml` nos mapas, DomPDF com `enable_remote=false`/`enable_php=false`/`chroot`, `mimes` em todos os uploads, Flysystem bloqueia path traversal. Faltam: ffmpeg sobre vídeo não confiável, uploads de 512 MB, assinatura base64 sem limite, regras do Configurador só na tela. | S-09, S-21, S-22 |
| **A4** Dados pessoais / LGPD | 3 | Não | Nenhum item do programa LGPD implementado; CPF não mapeado e impresso no PDF; backups em texto claro; SQLite do app sem criptografia; EXIF/GPS mantido nas fotos; sem retenção/exclusão/anonimização. | Bloco LGPD, S-10, S-15, S-20 |
| **A5** Segredos e configuração | 7 | Não | Sem segredos versionados, `.env` ignorado, CSRF ativo, cookies `HttpOnly`, sessão criptografada, CORS sem origens, cabeçalhos de segurança. CSP com `unsafe-inline`/`unsafe-eval`, cookie `secure` só por variável, sem `.env.example`, manutenção por GET. | S-18, S-19, S-24 |
| **A6** Dependências | 5 | Não | Laravel 13 e PHP 8.4 dentro do suporte, lockfiles versionados; porém 18 avisos do `composer audit` (1 Alto alcançável sem login), 1 Alto no `npm audit`, 17 pacotes Dart presos a versões antigas, nenhuma varredura automática. **Veto S-06.** | S-06, G-10 |
| **A7** Segurança da API | 4 | Não | `fae/v1` versionada, Sanctum, paginação nas listas, rate limit. Inventário sujo (`/api/v1`, `/api/mcl/v1` ativas), autenticação fraca, sem exigência de versão mínima do app, sem contrato OpenAPI, payload expõe contatos comerciais e CPF. **Veto S-01, S-05.** | S-01, S-05, S-14, G-06 |
| **A8** Logs e auditoria | 5 | Não | `AuditService` registra login, falhas, MFA, perfis e mudança de status na web. Nada é auditado na API (aprovações pelo app), nem CRM/clientes/usuários; sem trilha de leitura de dados pessoais; registros alteráveis. | S-11 |
| **A9** Segurança do app | 4 | Não | `allowBackup=false`, cleartext só no build de debug, validação TLS "fail closed". Token em `SharedPreferences`, SQLite sem criptografia, sem ofuscação, release com chave de debug, `applicationId` igual ao app da MCL, sem wipe/expiração do acesso offline. | S-05, S-13 |
| **A10** Hardening da hospedagem | 4 | Não | Existe um documento de infraestrutura funcional, mas sem requisitos de segurança (firewall/WAF, banco isolado, backup cifrado e externo, rotação de segredos, proxies confiáveis). | S-10, S-23, Apêndice A |
| **A11** Isolamento multi-cliente | 5 | Não | Migrations e banco por branch, `.env` separado, sem dados reais de outro cliente. Mas: rotas/controllers/testes da MCL ativos (vetor de S-01), texto jurídico da MCL no PDF da FAÉ, mesmo `applicationId` do app MCL, banco de teste compartilhado. | S-01, S-16 |
| **B1** Arquitetura | 6 | Não | Repositories e Services existem; catálogo de permissões bem desenhado. Controllers gordos (`AtendimentosRelatoriosController` com 1.249 linhas), três implementações quase iguais da API de relatórios, regras de negócio espalhadas, policy só para Atendimento. | G-01 |
| **B2** Qualidade | 6 | Não | Nomes consistentes e comentários ricos. Duplicação alta, comentários desatualizados (ex.: "Middleware comercial" em `Api/Fae/ClientesController.php:59`), texto com codificação quebrada nos comentários de `routes/api.php`. | G-01 |
| **B3** Padrões Laravel | 6 | Não | Form Requests na maioria, `can:` nas rotas. Sem API Resources, várias validações inline, closures de rota para manutenção, Pint sem execução automática. | G-01 |
| **B4** Banco MySQL | 6 | Não | FKs em 37 migrations, todas com `down()`, CNPJ único, migration de índices. Sem revisão de modelo (NC02), listas do cliente apagadas e recriadas a cada edição, N+1 não medido. | G-02, S-14 |
| **B5** Resiliência | 5 | Não | Transações nos pontos críticos de criação, fila persistente no app com retentativa. Sem idempotência, descarte silencioso em 4xx, retentativa sem backoff exponencial, fila `sync`, backup no ciclo da requisição, status e assinatura não atômicos. | S-14, S-10 |
| **B6** Testes | 5 | Não | 289 testes, incluindo segurança (IDOR, perfis, visibilidade do vendedor). Sem CI, 6 falhas conhecidas, sem teste de trava de aprovado nem de desligamento das APIs legadas; app com 3 arquivos de teste. Suíte não executada nesta auditoria. | G-04 |
| **B7** Desempenho | 6 | Não | Paginação e índices. Mapas sem agrupamento de pontos, PDF com todas as fotos em base64 na memória, backup que carrega tabelas inteiras, miniaturas de vídeo síncronas, rate limit da API por IP. | G-07, S-23 |
| **B8** Observabilidade | 4 | Não | Tabela `logs_erros` e log do Laravel; log de sincronismo no aparelho. Sem health check, alertas, métricas ou log centralizado. | G-05 |
| **B9** Documentação | 6 | Não | `CLAUDE.md`, `docs/api-fae.md`, Postman, cronograma e roteiros de QA; padrão BF_vX.Y.Z seguido. README é o padrão do Laravel, sem OpenAPI, sem `.env.example`, sem runbook. | G-06, S-24 |
| **B10** Aderência ao escopo | 5 | Não | De 32 itens: 15 atendidos, 13 parciais, 2 divergentes, BF12 ausente por decisão, RNF04 não aplicável. Falhas em critérios de aceite centrais (BF10 somente leitura, NC02 revisão, BF05/NC03 offline, visibilidade NC01). | Seção 4, G-02, G-03, G-08 |
| **B11** Qualidade do app | 6 | Não | Estrutura clara (core/features/shared), fila com log de sincronismo, banner offline, permissões do backend no menu. Offline só nas seções legadas, perda silenciosa em 4xx, poucos testes, `debugPrint` em release. | S-14, G-03, G-04 |

---

## 4. Matriz de aderência ao escopo

Legenda: ✅ atendido · 🟡 parcial · ❌ ausente · ↔ divergente (implementado diferente do documento, em geral por decisão do cliente — formalizar) · ➖ não aplicável.

| Item | Status | Evidência | Critérios de aceite |
|---|---|---|---|
| **NC01** Cadastro unificado | 🟡 | Contatos com tipo (`app/Http/Requests/ClienteRequest.php:80`), CNPJ único (migration `2026_09_16_090000`), classificação, alerta de recontato (`VerificarRecontatoClientes`), histórico (`app/Models/Cliente.php:128`), filtros segmento/cidade/UF/classificação (`ClientesController.php:44`) | ✅ múltiplos contatos; ✅ histórico; ✅ classificação; ✅ recontato; ✅ CNPJ duplicado; ✅ filtros; ↔ pré-cadastro/aprovação removido por decisão do cliente; ❌ **contato comercial invisível à assistência** (S-03); ➖ unificação de bases legadas (base nova) |
| **NC02** Configurador | 🟡 | `config_perguntas`/`config_modelos` (migrations `2026_09_10_0900xx`), 3 tipos (`TipoPergunta`), vínculo natureza/tipo de orçamento | ✅ checkbox/radio/texto; ✅ reaproveitamento; ✅ setor e vínculo; ❌ **revisão do modelo**: relatório aponta para o modelo vivo (`Api/Fae/RelatoriosController.php:153`; `ConfiguradorModeloRepository.php:49` "sem revisão versionada ainda"); ❌ preenchimento offline |
| **NC03** Imagem por pergunta | 🟡 | `atendimentos_relatorios_respostas_fotos` com comentário; `Api/Fae/RelatorioFormularioController.php:139-153` | ✅ foto + comentário no relatório e PDF; 🟡 "permite anexo" só respeitado na tela (S-22); ❌ **anexar offline e sincronizar** |
| **NC04** Anexos gerais | ↔ | Aba Anexos aceita só fotos (decisão do cliente); fila offline em `lib/features/relatorios/sections/anexos_section.dart` | ✅ área própria; ✅ offline (fotos); ↔ arquivos (PDF) não aceitos, logo "aparece só pelo nome" não se aplica |
| **BF01** Geolocalização | ↔ | `cli_latitude/longitude`, link do Maps, `geolocator` no app (`lib/features/clientes/cliente_form_screen.dart:201-212`) | ✅ capturar; ✅ abrir no Maps; ↔ "Atribuir localização" liberado a quem tem `clientes.editar` (`resources/views/clientes/form.blade.php:48`), não só ao comercial |
| **BF02** Perfil comercial | ✅ | Perfis configuráveis (`app/Support/Permissoes.php`, migration `2026_11_16_090000`), `/me` devolve permissões | ✅ CRM aparece/oculta conforme perfil (web e app) |
| **BF03** Cliente no atendimento | ✅ | `clientes.resumo` (`routes/web.php:122`) | ✅ (mas sem checagem de visibilidade — S-07) |
| **BF04** Modelo ↔ tipo de atendimento | ✅ | migration `2026_09_10_090004`; criação exige modelo (`Api/Fae/RelatoriosController.php:115-117`) | ✅ |
| **BF05** Formulário dinâmico no app | 🟡 | `lib/features/relatorios/sections/perguntas_formulario_section.dart`, `RelatorioFormularioController` | ✅ online, 3 tipos, foto só onde habilitado (na tela); ❌ **offline** — o próprio código declara "sem cache local nem fila" (`perguntas_formulario_section.dart:15-16`) |
| **BF06** PDF só com respondidas | ✅ | `resources/views/atendimentos-relatorios/pdf.blade.php:406-410` | ✅ (ressalva: texto jurídico da MCL no bloco Entrega Técnica — S-16) |
| **BF07** Envio com comprovante | 🟡 | `storeCompartilhamento` (`Api/Fae/RelatoriosController.php:389-405`) | ✅ data/hora + hash por compartilhamento; ✅ hash único; 🟡 hash de 64 caracteres (doc pede 10) e não vinculado ao conteúdo do PDF (S-17); ❓ orçamento em PDF: §4 do documento prevê, §8 tira de escopo — decidir (D-04) |
| **BF08** Mapa de demandas | ✅ | `MapaDemandasController.php:31` (filtros status/natureza/técnico), `lib/features/relatorios/mapa_demandas_screen.dart` | ✅ pontos por status; ✅ detalhe ao tocar |
| **BF09** Peças levadas/trocadas | 🟡 | migration `2026_09_11_090005` (`trocada`) | ✅ trocada; ❌ **levada** (nenhuma coluna `peca_levada`) |
| **BF10** Aprovação interna | 🟡 | `erroMudancaStatus` (`app/Models/AtendimentoRelatorio.php:223-258`), `aprovado_por/_em`, observação do supervisor | ↔ status Preenchendo/Liberado/Aprovado/Revisar (sem "Pendente"); ✅ só Assistência aprova (na `fae/v1` e web); ❌ **bloquear assinatura do cliente sem aprovação**; ❌ **relatório aprovado somente leitura** (S-02); ✅ Revisar reabre |
| **BF11** Observações internas | 🟡 | `aten_rel_observacao_interna`, fora do PDF | ✅ texto, fora do PDF; ❌ **fotos** |
| **BF12** Contador de tempo | ❌ | — | Pendente de decisão do cliente (bloqueado por escopo) |
| **CRM01** Modelo ↔ tipo de orçamento | ✅ | `crm_tipos_orcamento` | ✅ |
| **CRM02** Nível e prazo | ✅ | `NivelOrcamento::calcularPrazo`, `OrcamentoRepository.php:60-71` | ✅ cálculo; ✅ edição manual preservada; 🟡 sem indicador "editado manualmente" |
| **CRM03** Comentários com alerta | 🟡 | `orcamentos_comentarios` + `Notificacao::notificar` | ✅ autor/data/hora; ✅ destinatário notificado; ❌ comentários **no cliente** |
| **CRM04** Indicação conjunta | ✅ | `orcamentos_vendedores_adicionais` | ✅ |
| **CRM05/06** Roteiro saída/retorno | ✅ | `crm_roteiros_viagem(_clientes)`, `ResultadoVisitaRoteiro` | ✅ |
| **CRM07** Relações no mapa | ✅ | `MapaRelacoesController.php:19` (vendedor/estado/segmento/classificação/caso de sucesso), `lib/features/crm/mapa_relacoes_screen.dart` | ✅ |
| **CRM08** Indicadores | 🟡 | `IndicadoresComerciaisController.php:31-50` | ✅ conversão real por vendedor; 🟡 só filtro de período — falta **filtro de vendedor** |
| **CRM09** CRM no app | ✅ | `lib/features/crm/*` com permissões do backend | ✅ (online apenas) |
| **RNF01** Sincronismo offline | 🟡 | `lib/core/services/sync_service.dart`, tabela `pending_writes` | 🟡 fila confiável só para seções legadas/anexos/assinaturas; ❌ formulário dinâmico, NC03, BF11; ❌ idempotência; ❌ descarte em 4xx (S-14) |
| **RNF02** App multi-perfil | ✅ | `lib/core/auth/permissoes.dart` | ✅ |
| **RNF03** Limite de tipos | ✅ | `TipoPergunta` (3 tipos; "Sessão" é agrupador, não resposta) | ✅ |
| **RNF04** Migração de clientes | ➖ | Base nova, sem migração de cadastros legados no código | Não aplicável / não verificável |
| **RNF05** Performance de mapas | 🟡 | Leaflet sem agrupamento de marcadores | 🟡 não medido; sem clustering nem limite de pontos |
| **RNF06** Integridade do comprovante | 🟡 | ver BF07 | 🟡 hash aleatório sem vínculo criptográfico (S-17) |
| **RNF07** Migrations por branch | ✅ | 74 migrations na branch, banco `buildflow_fae` | ✅ (ressalva: banco de teste compartilhado) |

**Resumo (32 itens, CRM05 e CRM06 contados separadamente):** 15 ✅ · 13 🟡 · 2 ↔ · 1 ❌ (BF12, por decisão) · 1 ➖.

---

## 5. Achados detalhados (por severidade)

Formato: severidade contextual · CVSS 3.1 estimado · mapeamento OWASP Top 10 2021 (A0x), OWASP API Security Top 10 2023 (APIx) e OWASP MASVS v2.

### 5.1 Alta

#### S-01 — APIs legadas `/api/v1` e `/api/mcl/v1` ativas na FAÉ, fora da matriz de permissões e com a regra de aprovação da MCL
- **Severidade:** Alta · CVSS 7.1 (`AV:N/AC:L/PR:L/UI:N/S:U/C:L/I:H/A:N`) · API9 (inventário), API5 (BFLA), A01.
- **Evidência:**
  - `routes/api.php:31-90` (`/api/v1`) e `routes/api.php:98-158` (`/api/mcl/v1`): só `auth:sanctum` (`:37`, `:105`), **sem** nenhum `can:` — enquanto o grupo da FAÉ (`:165+`) exige `can:relatorios.editar` etc.
  - `app/Http/Controllers/Api/Mcl/RelatoriosController.php:569-590`: técnico (nível ≠ 0) com as duas assinaturas grava `Aprovado` diretamente; não usa `erroMudancaStatus`, não grava `aprovado_por/_em` e permite tirar um relatório de Aprovado.
  - O app da FAÉ só usa `fae/v1` (`lib/core/constants/app_config.dart:14-15`) — as rotas legadas não têm consumidor.
- **Impacto no contexto:** qualquer técnico dono do atendimento aprova o próprio relatório sem o supervisor (BF10), reabre relatórios aprovados e escreve em relatórios mesmo que o perfil dele tenha perdido `relatorios.editar` em "Perfis de Acesso".

#### S-02 — Relatório aprovado não é somente leitura no servidor; assinatura do cliente aceita sem aprovação e sobrescrevível
- **Severidade:** Alta (contextual: integridade de documento assinado) · CVSS 6.5 (`AV:N/AC:L/PR:L/UI:N/S:U/C:N/I:H/A:N`) · A04 (design inseguro), API6 (fluxo de negócio sensível).
- **Evidência:**
  - A trava existe só na tela: `app/Http/Controllers/AtendimentosRelatoriosController.php:255` (`somenteLeitura` vai para a view).
  - Endpoints de escrita sem checagem de status: web `AtendimentosRelatoriosController.php:263-300`, `:446-466` (`updateTexto`), `:357-445` (`updateAssinaturas` grava assinaturas em qualquer status); API `Api/Fae/RelatoriosController.php:334-341` (informações adicionais), `:722-755` (assinaturas); `Api/Fae/RelatorioFormularioController.php:109-158` (respostas e fotos); idem nas APIs legadas.
  - `AtendimentoRelatorio::erroMudancaStatus` (`app/Models/AtendimentoRelatorio.php:223-258`) controla só a transição de status, não a edição de conteúdo.
  - Assinatura gravada sempre no mesmo arquivo e o registro é atualizado (`app/Services/RelatorioMclService.php:27`, `:47-58`): a assinatura anterior é perdida sem rastro.
- **Impacto:** o relatório que o cliente assinou pode ser alterado depois (texto, respostas, fotos, assinatura), o que o invalida como evidência. Os critérios de aceite do BF10 "tentar editar um relatório aprovado deve bloquear" e "assinatura do cliente sem aprovação deve bloquear" **falham**.

#### S-03 — Contatos comerciais visíveis a todos os perfis com acesso a Clientes (regra de visibilidade do NC01 não implementada)
- **Severidade:** Alta (contextual: regra explícita do escopo + dados pessoais) · CVSS 6.5 (`AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N`) · API3 (exposição de propriedades), A01.
- **Evidência:**
  - `cli_cont_tipo` só é gravado (`ClienteRequest.php:80`, `app/Repositories/ClienteRepository.php:129`); nenhum filtro de leitura por tipo em `app/` ou `resources/views`.
  - A API devolve todos os contatos, com o tipo: `Api/Fae/ClientesController.php:133-140`.
  - A restrição existente vale só para o **perfil Vendedor** e só no nível do cliente: `app/Models/Cliente.php:76-92`; Comercial e Assistência veem tudo.
  - A permissão padrão dá `clientes.ver` à Assistência: `database/seeders/PermissoesPadraoSeeder.php:34-35`.
  - O contato não guarda quem o cadastrou (a lista é apagada e recriada a cada edição: `ClienteRepository.php:117-135`).
- **Impacto:** a assistência técnica vê nome, cargo, telefone e e-mail de contatos comerciais, contrariando o NC01 e o princípio da necessidade (LGPD art. 6º, III).

#### S-04 — `/midia/{path}` serve qualquer arquivo a qualquer usuário autenticado (sem autorização por objeto)
- **Severidade:** Alta (contextual: assinaturas e fotos de todos os clientes) · CVSS 6.5 (`AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N`) · API1 (BOLA), A01.
- **Evidência:** `routes/web.php:58-60` (só `auth:web,sanctum`, `usuario.ativo`, `sessao.prazo`, `mfa`); `app/Http/Controllers/StorageController.php:17-22` (`exists` + `response`, nenhuma checagem de dono). Caminhos previsíveis: `atendimentos_relatorios/{id}/assinaturas/cliente.png` (`RelatorioMclService.php:27`), `atendimentos_relatorios/{id}/fotos/<nome original>` (`Api/Fae/RelatoriosController.php:826-827`).
- **Impacto:** um Vendedor ou Técnico baixa assinaturas e fotos de atendimentos de outros técnicos e clientes enumerando IDs, contornando a posse aplicada no resto do sistema.

#### S-05 — Autenticação da API sem MFA, tokens sem expiração e sem bloqueio por conta; token guardado sem proteção no aparelho
- **Severidade:** Alta · CVSS 7.4 (`AV:N/AC:H/PR:N/UI:N/S:U/C:H/I:H/A:N`) · API2, A07, MASVS-AUTH-1/2, MASVS-STORAGE-1.
- **Evidência:**
  - Login da API só com e-mail e senha, token com todas as habilidades: `app/Http/Controllers/Api/AuthController.php:20-47`.
  - `config/sanctum.php:49` → `'expiration' => null`.
  - MFA só na sessão web, por decisão registrada no código: `app/Http/Middleware/ExigirMfa.php:10-16`.
  - Bloqueio por conta (5 erros → 15 min) existe só no web (`app/Http/Controllers/AuthController.php:69-93`); na API há só o limitador de 5/min por IP+e-mail (`app/Providers/RouteServiceProvider.php:56-58`), contornável com IPs distribuídos.
  - Senha mínima de 6 caracteres, sem complexidade nem checagem de vazamento: `app/Http/Requests/UsuarioRequest.php:29-40`, `MinhaContaRequest.php:20`.
  - Token em `SharedPreferences` (texto claro no sandbox do app): `lib/core/api/api_service.dart:176-187`, `lib/core/auth/auth_provider.dart:39-41`.
- **Impacto:** o MFA exigido no painel é contornado pela API para as mesmas contas (inclusive Administrador e Assistência), e um token vazado vale até a troca de senha ou a desativação do usuário.

#### S-06 — Dependências com vulnerabilidades conhecidas
- **Severidade:** Alta · CVSS 7.5 (`AV:N/AC:L/PR:N/UI:N/S:U/C:N/I:N/A:H`) · A06.
- **Evidência (`composer audit --locked`, 27/11/2026 — 18 avisos em 8 pacotes):**
  - `symfony/http-foundation` v8.1.6 — **CVE-2026-108082 (alta)**: ReDoS em `HeaderUtils::split()` via cabeçalho `Cache-Control`, alcançável sem autenticação; mais 5 avisos.
  - `symfony/routing` (3 avisos, incluindo contorno de controle de acesso por caminho com placeholders de host), `symfony/http-kernel` (2), `symfony/mailer` (2), `symfony/mime` (1), `symfony/yaml` (1).
  - `laravel/framework` v13.31.0 — CVE-2026-128083 (baixa), corrigido em 13.40.0.
  - `league/commonmark` 2.10.1 — 2 avisos (1 alto, DoS; provavelmente não alcançável — o sistema não renderiza Markdown de usuário).
  - `npm audit --omit=dev`: `source-map-js` (alta; só no build).
  - Dart: 17 dependências presas a majors antigos (`flutter pub outdated`); sem varredura de CVE.
- **Impacto:** negação de serviço remota sem login; risco crescente por não haver varredura automática.

### 5.2 Média

#### S-07 — IDOR em `GET /clientes/{cliente}/resumo`
- CVSS 4.3 (`AV:N/AC:L/PR:L/UI:N/S:U/C:L/I:N/A:N`) · API1, A01.
- `routes/web.php:122` (sem `can:`); `app/Http/Controllers/ClientesController.php:170-184` (sem `visivelPara`). Qualquer usuário logado lê nome, contato principal, telefone e e-mail de qualquer cliente por ID — inclusive um Vendedor sobre clientes de outros vendedores.

#### S-08 — Cadastro inicial do MFA só com senha; e-mail e senha alteráveis sem a senha atual; política de senha fraca
- CVSS 5.9 (`AV:N/AC:H/PR:N/UI:N/S:U/C:H/I:N/A:N`) · A07.
- `AuthController.php:100-112`: usuário sem MFA entra com a senha e cadastra o autenticador (`MfaController.php:46+`) — quem tiver a senha de uma conta ainda sem MFA fica com o segundo fator. `MinhaContaRequest.php` não pede senha atual para trocar e-mail ou senha (uma sessão sequestrada vira tomada de conta). Senha mínima de 6. Sem recuperação de senha self-service (lacuna operacional: só o administrador redefine).

#### S-09 — Vídeo não confiável processado pelo ffmpeg; uploads de até 512 MB sem cota
- CVSS 5.0 (`AV:N/AC:H/PR:L/UI:N/S:U/C:H/I:N/A:N`) · A10 (SSRF/leitura de arquivo), A05.
- `app/Services/MediaService.php:144-176` chama `ffmpeg -i <upload>` (argumentos escapados, sem injeção de comando), mas sem restringir demuxer/protocolo: arquivos com playlist HLS embutida são um vetor conhecido de leitura de arquivo local, cujo conteúdo pode aparecer na miniatura. `app/Http/Requests/Fae/UploadAnexosRequest.php:22-24` aceita vídeos/arquivos de 512 MB; `public/.htaccess:4-15` e `public/.user.ini` sobem os limites do PHP; com `QUEUE_CONNECTION=sync` o processamento roda dentro da requisição.

#### S-10 — Backups em SQL texto claro no próprio servidor, disparados por requisição web
- Sem CVSS (risco operacional/LGPD) · A02, A05.
- `app/Http/Middleware/BackupAutomatico.php:43-62` roda `backup:run` ao fim de uma requisição de administrador; `app/Console/Commands/BackupDatabase.php:23,36,60` grava até 120 dumps em `storage/app/backup`, carregando cada tabela inteira em memória. Os dumps contêm CPF, contatos, hashes de senha, sessões e segredos MFA (cifrados com a `APP_KEY`, que fica no mesmo servidor). As mídias não entram no backup; não há cópia externa nem teste de restauração.

#### S-11 — Trilha de auditoria incompleta
- A09, API10.
- `AuditService::log` é chamado em só 5 controllers web: login/MFA, perfis, atendimentos e status de relatório. Nenhuma chamada em `app/Http/Controllers/Api/*` — aprovações e assinaturas feitas pelo app não deixam trilha. CRM, clientes, contatos e usuários não são auditados; não há registro de leitura de dados pessoais; a tabela `logs_auditoria` é alterável pela aplicação e por quem acessa o banco.

#### S-12 — Conta "usuário protegido" do fornecedor com acesso total e oculta do cliente
- A01 / governança LGPD (operador).
- `app/Models/Usuario.php:55-65` (acesso total por flag e por e-mail fixo), `app/Http/Controllers/UsuariosController.php:42-46` (não aparece na listagem para ninguém) e `:74-76` (não editável pelos administradores do cliente). Os administradores da FAÉ não enxergam nem controlam uma conta com acesso irrestrito aos dados deles, que também pode usar a API sem MFA (S-05).

#### S-13 — App Android: identidade, assinatura e armazenamento local
- MASVS-STORAGE-1, MASVS-RESILIENCE-1/3, MASVS-PLATFORM, MASVS-CODE-1.
- `android/app/build.gradle.kts:44`: `applicationId = "com.example.mo_control"`, **o mesmo do app da MCL** (`app.buildflow/android/app/build.gradle:44`) — instalar um substitui o outro no mesmo aparelho e as atualizações se misturam.
- `android/app/build.gradle.kts:14-27, 60-75`: sem `key.properties` (o arquivo não existe), o release é assinado com a chave de debug da máquina.
- `tool/build_publish_apk.ps1:35`: build sem `--obfuscate --split-debug-info`.
- `lib/core/database/local_db.dart:36-75`: SQLite sem criptografia com relatórios em JSON — incluindo CPF de assinante e observação interna (`Api/Fae/RelatoriosController.php:240`, `:301-307`) — e a fila de pendências.
- `lib/core/auth/auth_provider.dart:101-113`: o logout preserva as pendências no aparelho; não há expiração do acesso offline nem wipe remoto quando o usuário é desativado.
- `android/app/src/main/AndroidManifest.xml:7`: `REQUEST_INSTALL_PACKAGES`, com APK distribuído por URL configurável (`MOBILE_APK_URL`, hoje `http://` no `.env` local).
- `lib/core/constants/app_config.dart:15`: URL de produção ainda é placeholder. Sem certificate pinning (aceitável se justificado; hoje não há justificativa documentada para a FAÉ).

#### S-14 — Sincronização: duplicidade, perda silenciosa e conflitos
- A04, API6 · ver também o bloco de sincronização (§5.4).
- `lib/core/services/sync_service.dart:419-460`: criação de relatório sem chave de idempotência — se a resposta se perder depois de o servidor criar, a retentativa cria outro (o servidor aceita só `aten_rel_data`: `app/Http/Requests/Fae/StoreRelatorioRequest.php`).
- `sync_service.dart:448-456`, `:542-548`, `:637-640`: respostas 4xx (inclusive 403/409/422) **removem o item da fila**; o técnico só vê isso na tela técnica de log.
- `ClienteRepository.php:51-76, 117-165`: o PUT do cliente grava `null` em todo campo ausente e apaga/recria contatos, equipamentos e localizações — a última escrita vence, sem detecção de conflito (a sessão em andamento no repositório acabou de contornar isso para dois campos).
- A versão mínima do app é só informada (`Api/Fae/AppController.php`), nunca exigida pelo servidor.

#### S-15 — CPF do assinante coletado, guardado em claro e impresso no PDF compartilhado
- LGPD (minimização/segurança) · A02.
- Migration `2026_08_20_100001` (`aten_rel_ass_cpf`), obrigatório junto da assinatura do cliente (`StoreAssinaturaRequest`), devolvido pela API (`Api/Fae/RelatoriosController.php:301-307`), cacheado no app e impresso no PDF (`pdf.blade.php:621-622`), que é compartilhado por WhatsApp/Drive. O CPF não consta do inventário de dados do escopo.

#### S-16 — Resquícios de outro cliente (MCL) na branch da FAÉ
- A11 / integridade documental.
- `resources/views/atendimentos-relatorios/pdf.blade.php:565`: o PDF da FAÉ diz "**A MCLvale**, neste ato representado pelo técnico abaixo assinado…" no bloco Entrega Técnica — documento assinado pelo cliente da FAÉ cita outra empresa.
- Código, rotas, requests, docs e testes da MCL ativos (`routes/api.php:92-158`, `app/Http/Controllers/Api/Mcl/*`, `app/Http/Requests/Mcl/*`, `docs/api-mcl.md`, `docs/postman-mcl.json`, `tests/Feature/*Mcl*`); hostname de produção da MCL citado no app (`lib/core/api/api_service.dart:43-45`); `applicationId` compartilhado (S-13); banco de teste compartilhado (`CLAUDE.md`). Não foram encontrados dados pessoais nem segredos da MCL.

#### S-17 — Comprovante de envio (BF07/RNF06) não é prova criptográfica
- A08 (integridade).
- `Api/Fae/RelatoriosController.php:389-405` e `AtendimentosRelatoriosController.php:894-905`: hash = SHA-256 de `id|timestamp|aleatório`. Não está vinculado ao conteúdo do PDF enviado, não é assinado (HMAC/assinatura digital), o registro é editável no banco e é criado quando o app *pede* — não prova que o envio aconteceu nem qual versão foi enviada. O documento pede 10 caracteres; o sistema gera 64.

### 5.3 Baixa

| ID | Achado | Evidência | Mapeamento |
|---|---|---|---|
| S-18 | CSP com `'unsafe-inline'` e `'unsafe-eval'`; HSTS sem `includeSubDomains`; cookie `secure` depende de `SESSION_SECURE_COOKIE` (sem padrão) | `app/Http/Middleware/CabecalhosDeSeguranca.php:42-44, 50-62`; `config/session.php:172` | A05 |
| S-19 | Rotas de manutenção por GET com efeito colateral (`?fix=1` reescreve arquivos de código/mídia no servidor), sem CSRF | `routes/web.php:274-296` | A01/A05 |
| S-20 | Fotos guardadas sem remover EXIF (GPS do técnico) e com o nome original | `Api/Fae/RelatorioFormularioController.php:144-148`; `RelatorioMclService.php:73-88`; `AtendimentosController.php:244` | LGPD / A04 |
| S-21 | Assinatura base64 sem limite de tamanho (`imagecreatefromstring` → consumo de memória) | `app/Http/Requests/Fae/StoreAssinaturaRequest.php:22-23`; `RelatorioMclService.php:22-39` | API4 |
| S-22 | Regras do Configurador só na tela: a resposta aceita pergunta fora do modelo e foto mesmo sem "permite anexo" | `Api/Fae/RelatorioFormularioController.php:111-158` | API3 |
| S-23 | Rate limit da API cai no ramo por IP (o usuário ainda não foi resolvido antes do `auth:sanctum` — inferido, não testado); `TrustProxies` vazio: atrás da Cloudflare todos os acessos parecem vir dos IPs dela | `app/Providers/RouteServiceProvider.php:44-51`; `app/Http/Middleware/TrustProxies.php:15` | API4 |
| S-24 | Sem `.env.example` e README padrão do Laravel (configuração segura não documentada) | raiz do repositório web | A05 |

### 5.4 Bloco: sincronização offline/online

| Pergunta | Resposta | Evidência |
|---|---|---|
| Dados offline protegidos? | **Não.** SQLite sem criptografia; token em `SharedPreferences`; fotos em arquivos do app. `allowBackup=false` ajuda contra extração por backup. | `local_db.dart:36-75`; `api_service.dart:176-187`; `AndroidManifest.xml:17` |
| Aparelho perdido / usuário desligado | Desativar o usuário revoga os tokens (`SessaoService::encerrarOutrosAcessos`), mas os dados locais continuam legíveis; não há expiração do acesso offline nem wipe remoto. | `app/Services/SessaoService.php`; `auth_provider.dart:101-113` |
| O servidor revalida permissão e posse de cada registro sincronizado? | **Sim, na `fae/v1`**: `can:` por tela + `AtendimentoPolicy` em cada relatório; Vendedor restrito em clientes/orçamentos/roteiros. **Não** nas APIs legadas (permissão) e não no `/midia` (posse). | `routes/api.php:165+`; S-01, S-04 |
| Um usuário grava/lê dados de outro técnico/vendedor? | Ler mídia de outros: **sim** (S-04). Escrever em relatório de outro técnico: não. Ler contatos comerciais de outros vendedores: **sim**, para quem tem `clientes.ver` (S-03). | — |
| Idempotência / duplicidade | **Ausente.** Sem UUID do cliente nem chave de idempotência; retentativa após timeout duplica relatório/anexo. | S-14 |
| Fila persistente | **Sim** (`pending_writes`), sem limite de tentativas, retentativa a cada 2 min, sem backoff exponencial. | `sync_service.dart:181-185, 419-430` |
| Falha parcial em lote | Itens independentes; 4xx é descartado da fila (perda silenciosa). | S-14 |
| Conflitos web × app e app × app | **Sem estratégia**: última escrita vence; listas do cliente são substituídas por inteiro. | `ClienteRepository.php:117-165` |
| Relógio do aparelho | A data do relatório vem do aparelho (aceita qualquer data passada); aprovação (`aprovado_em`), comprovante e assinatura usam a hora do servidor ✅. | `StoreRelatorioRequest.php`; `Api/Fae/RelatoriosController.php:396, 687-693` |
| Relatório aprovado e assinatura bloqueados no servidor, inclusive contra sync atrasado? | **Não** (S-02). Um payload atrasado altera um relatório já aprovado. | S-02 |
| Revisão do modelo do Configurador | **Não existe**; um relatório offline preenchido com o modelo antigo é gravado contra o modelo atual. | `Api/Fae/RelatoriosController.php:153`; G-02 |
| Fotos: tipo/tamanho, storage privado, acesso autenticado | Tipo (`mimes`) e tamanho (10 MB por foto) ✅; storage fora de `public/` ✅; acesso autenticado ✅, mas sem posse (S-04); sem URL assinada; sem compressão no servidor (compressão no app não verificada). | `UploadAnexosRequest.php:18`; `StorageController.php` |
| Versão do app | O servidor **não** rejeita cliente antigo; contrato `fae/v1` versionado no prefixo, sem política de depreciação. | `Api/Fae/AppController.php` |
| Minimização | `/me` devolve só as permissões do usuário ✅; listas paginadas ✅; o detalhe do cliente devolve todos os contatos, inclusive comerciais, e o relatório devolve CPF ❌. | S-03, S-15 |

### 5.5 Bloco LGPD (dentro de A4)

| Tema | Situação | Natureza |
|---|---|---|
| Mapeamento de dados pessoais (ROPA) | **Ausente.** Inventário mínimo levantado nesta auditoria: usuários (nome, e-mail, IP em logs, segredo MFA), contatos de clientes (nome, cargo, telefone, e-mail), geolocalização de clientes, fotos (podem conter pessoas e GPS do técnico no EXIF), assinatura manuscrita, **nome e CPF do assinante**, observações internas, comentários, notificações, comprovantes, logs de auditoria, backups. | Técnico + jurídico |
| Base legal por finalidade | Não documentada. Sugestão a validar: assistência técnica → execução de contrato (art. 7º, V); CRM/prospecção B2B → legítimo interesse (art. 7º, IX), com LIA documentado; geolocalização de clientes PJ → legítimo interesse; CPF do assinante → execução de contrato/exercício regular de direitos (avaliar a necessidade). | Jurídico |
| Minimização | CPF completo no PDF; contatos comerciais expostos à assistência; EXIF não removido. | Técnico |
| Consentimento | Provavelmente não aplicável (B2B); avaliar se vier a ser coletada a geolocalização do próprio usuário. | Jurídico |
| Política de privacidade / termos | **Ausentes** (web e app; o app instalado por APK também não mostra aviso). | Jurídico + técnico (exibir) |
| Direitos do titular (acesso, correção, exclusão/anonimização, portabilidade) | Correção pelo cadastro ✅; **sem exclusão/anonimização** de cliente, contato ou usuário (só desativação); sem exportação. | Técnico |
| Retenção e descarte | **Ausente** (fotos, contatos, logs, 120 backups, cache no aparelho). | O jurídico define prazos; o técnico implementa |
| Controle de acesso por perfil | Parcial (S-03, S-04, S-07, S-12). | Técnico |
| Criptografia | TLS a definir na hospedagem; segredo MFA cifrado ✅; banco, backups e SQLite do app em claro. | Técnico |
| Logs de acesso a dados pessoais | Ausentes (S-11). | Técnico |
| Resposta a incidentes | Sem plano (prazos de comunicação à ANPD e aos titulares — Resolução CD/ANPD nº 15/2024). | Organizacional |
| Operadores e transferências | Fornecedor (3io Digital) com conta de acesso total (S-12); OpenStreetMap (tiles; recebe IP e origem); Cloudflare (planejada; proxy de todo o tráfego, transferência internacional); WhatsApp/Drive/OneDrive/Telegram (compartilhamento do PDF com CPF e fotos); Google Maps (links); push (FCM) futuro. Nenhum contrato/cláusula documentado. | Jurídico + técnico (inventário) |

---

## 6. Plano de correção

Cada item traz ID, critério, correção, aceite verificável, esforço (P/M/G) e dependências. Severidade, CVSS, impacto e evidência de cada S-xx estão na seção 5. A nota esperada por critério após cada fase está em 6.6.

### 6.1 Fase 0 — Contenção imediata (bloqueia a aprovação)

| ID | Crit. | Sev. | Correção recomendada | Critério de aceite verificável | Esf. | Dep. |
|---|---|---|---|---|---|---|
| S-01 | A2, A7, A11, B10 | Alta | Remover os grupos `Route::prefix('v1')` e `Route::prefix('mcl/v1')` do `routes/api.php` da branch FAÉ; apagar `Api/Mcl/*`, `Api/{Atendimentos,Relatorios,Catalogo}Controller`, `Requests/Mcl/*` e os testes `*Mcl*` (portando para a `fae/v1` o que ainda valer). | `php artisan route:list --path=api` lista só `api/fae/v1/*`; teste novo: `PUT /api/mcl/v1/relatorios/1/status` e `GET /api/v1/relatorios` → 404. | P | — |
| S-02 | A2, B10 | Alta | Guarda única (ex.: `AtendimentoRelatorio::garantirEditavel()` ou middleware `relatorio.editavel`) aplicada a **todas** as rotas de escrita de relatório (web e `fae/v1`), devolvendo 423 quando `Aprovado`. Assinatura do cliente só com status Aprovado e uma única vez; cada assinatura em arquivo novo e imutável (nome com hash), guardando o SHA-256 do PDF no momento da assinatura. Status e assinatura na mesma transação. | Testes: toda rota de escrita em relatório Aprovado → 423 (web e API); assinatura do cliente com status ≠ Aprovado → 422; segunda assinatura do cliente → 409; `Revisar` volta a permitir edição. | M | D-03 |
| S-03 | A2, A4, B10 | Alta | Adicionar `cli_cont_usuario_id` (autor) em `clientes_contatos`; escopo `contatosVisiveisPara($usuario)`: contato Comercial visível só ao autor/vendedor responsável e a quem tem acesso total; aplicar no formulário web, no `show` da API, no histórico e no resumo. Trocar o "apaga e recria" por sincronização por ID, preservando contatos ocultos a quem edita. | Teste: Assistência abre cliente com contato Comercial de outro vendedor → contato ausente no HTML e no JSON; editar e salvar o cliente não remove esse contato. | M | D-01 |
| S-04 | A2, A4 | Alta | No `StorageController`, mapear o prefixo do caminho para o registro dono (`atendimentos/{id}` e `atendimentos_relatorios/{id}` → `garantirPosse`) e negar o resto a quem não tem acesso total; ou servir só por URL assinada temporária (`URL::temporarySignedRoute`) emitida após a checagem. Novos arquivos com nome aleatório (UUID). | Teste: técnico B recebe 403 em foto/assinatura do atendimento do técnico A; Vendedor recebe 403 em `atendimentos_relatorios/*`; administrador recebe 200. | M | — |
| S-05 (parte 1) | A1, A7, A9 | Alta | API: o mesmo bloqueio por conta do web (chave `login.senha.<sha1(email)>`); expiração dos tokens (`sanctum.expiration` ≈ 30 dias + 7 dias de inatividade via `last_used_at`); habilidade restrita (`fae-app`); política de senha `Password::min(10)->letters()->numbers()->uncompromised()`. App: token em `flutter_secure_storage` (Keystore). | Testes: 6ª senha errada na API → bloqueio de 15 min mesmo de outro IP; token com mais de 30 dias → 401; senha "123456" rejeitada; `shared_preferences` sem token. | M | — |
| S-06 | A6 | Alta | `composer update` dentro de `^13` (≥ 13.40, Symfony ≥ 8.1.12, commonmark corrigido) e `npm audit fix`; rodar a suíte. | `composer audit --locked` sem avisos (ou exceções justificadas por escrito); `npm audit --omit=dev` limpo. | P | — |
| S-07 | A2 | Média | `resumo`: `abort_unless($cliente->visivelPara($usuario))` e exigir `atendimentos.editar` ou `clientes.ver`. | Teste: Vendedor A → 403 no resumo de cliente do Vendedor B. | P | — |
| S-16 (texto) | A11, B10 | Média | Trocar "A MCLvale" pelo texto aprovado pela FAÉ em `pdf.blade.php:565`. | O HTML do PDF não contém "MCL". | P | Texto do cliente |

### 6.2 Fase 1 — Segurança restante e sincronização segura

| ID | Crit. | Sev. | Correção recomendada | Critério de aceite | Esf. | Dep. |
|---|---|---|---|---|---|---|
| S-05 (parte 2) | A1, A9 | Alta | MFA na API: `POST /login` devolve um desafio para quem tem MFA; `POST /login/mfa` troca o código pelo token. Obrigatório ao menos para Administrador, Assistência e usuário protegido (ou para todos, como no web). | Login de administrador no app sem código → sem token; com código → token. | M | S-05 p1 |
| S-08 | A1 | Média | Cadastro do MFA por código de ativação de uso único gerado pelo administrador (ou exigir o cadastro em até 24 h da criação da conta, senão bloquear); senha atual + código MFA para trocar e-mail/senha; recuperação de senha com código de uso único. | Conta sem MFA não cadastra autenticador só com a senha; troca de e-mail sem senha atual → 422. | M | — |
| S-09 | A3, B7 | Média | Validar o vídeo com `ffprobe` (só mp4/mov/webm, codecs esperados), chamar o ffmpeg com demuxer explícito, sem HLS/concat, em fila (`QUEUE_CONNECTION=database`) com usuário sem acesso aos segredos; reduzir o limite para menos de 100 MB (limite da Cloudflare) ou upload em partes; cota por relatório. | Upload de AVI com playlist HLS → rejeitado; miniatura gerada fora da requisição. | M | Hospedagem |
| S-14 | B5, B11 | Média | Chave de idempotência (`Idempotency-Key` ou `client_uuid` único por relatório/anexo/resposta) gravada no servidor; 409/422/423 viram "pendência com erro" visível ao técnico, com ação de reenviar/descartar, nunca descarte automático; backoff exponencial com jitter; versão (`updated_at` + `If-Match`) para detectar conflito; PATCH parcial no cliente; cabeçalho `X-App-Version` com rejeição (426) abaixo da mínima. | Rede simulada: timeout após o 201 não duplica; item recusado aparece na tela de pendências; edição concorrente devolve 409. | G | S-02 |
| S-11 | A8 | Média | `AuditService` em todos os endpoints de escrita (web e API) via observer/middleware; trilha de leitura de cliente/contato/relatório; menu de auditoria visível ao administrador; tabela só de inserção (usuário de banco da aplicação sem UPDATE/DELETE nela) e encadeamento por hash. | Aprovação pelo app gera linha com usuário, IP, antes/depois; `UPDATE logs_auditoria` falha com o usuário da aplicação. | M | — |
| S-12 | A2, A4 | Média | Conta do fornecedor visível (só leitura) aos administradores da FAÉ, com MFA, sem acesso à API, toda ação auditada e uso formalizado em contrato (procedimento *break-glass*). | Administrador da FAÉ vê a conta e o último acesso; login dela na API → 403. | P | D-06 |
| S-13 | A9, A11 | Média | `applicationId` próprio (ex.: `br.com.fae.buildflow`); keystore de release fora do repositório; `--obfuscate --split-debug-info`; SQLite com SQLCipher (`sqflite_sqlcipher`) e chave no Keystore; expiração do acesso offline (ex.: 7 dias sem contato com o servidor → bloqueia e apaga o cache); wipe ao receber 401 de usuário desativado; APK só por HTTPS com SHA-256 conferido antes de instalar; URL de produção real; pinning por chave pública ou justificativa escrita. | `aapt dump badging` mostra o novo pacote; `apksigner verify --print-certs` mostra o certificado de release; arquivo `.db` ilegível sem a chave; app sem rede por 7 dias pede login. | G | Hospedagem |
| S-17 | B10 | Média | Comprovante = HMAC-SHA256 (chave só no servidor) sobre `relatório + SHA-256 do PDF + usuário + carimbo do servidor`; exibir 10 caracteres (base32) e guardar o completo; tabela só de inserção; rota de verificação do código. | Alterar uma linha do comprovante no banco faz a verificação falhar; o mesmo PDF em dois envios gera códigos diferentes. | M | D-04 |
| S-18 | A5 | Baixa | Nonce na CSP e remoção de `unsafe-inline` (scripts inline para módulos Vite) e de `unsafe-eval` (build CSP do Alpine); `includeSubDomains`; `SESSION_SECURE_COOKIE=true` por padrão em produção. | CSP sem `unsafe-*`; telas funcionando nos testes de navegação. | M | — |
| S-19 | A5 | Baixa | Remover as rotas de manutenção em produção (VPS com SSH) ou trocá-las por POST + CSRF + confirmação. | `GET /admin/codigo/remover-bom?fix=1` → 404/405. | P | — |
| S-21, S-22 | A3 | Baixa | Limite de tamanho da string base64 (ex.: 1 MB) e das dimensões; validar que a pergunta pertence ao modelo do relatório e que a foto só é aceita com "permite anexo". | Testes 422 para os três casos. | P | — |
| S-23 | A7, B7 | Baixa | Limitador da API pela identidade do token e `TrustProxies` com as faixas da Cloudflare. | Dois técnicos no mesmo IP não dividem o limite; `request()->ip()` mostra o IP real atrás da Cloudflare. | P | Hospedagem |

### 6.3 Fase 2 — Adequação LGPD (técnica) e decisões para o cliente/jurídico

| ID | Crit. | Correção técnica | Aceite | Esf. | Dep. |
|---|---|---|---|---|---|
| L-01 | A4 | Inventário de dados (ROPA) versionado em `docs/lgpd/`, por tabela/campo, com finalidade, perfil com acesso e retenção. | Documento aprovado pelo responsável LGPD. | P | Jurídico |
| L-02 (S-15) | A4 | CPF: tornar opcional ou guardar só o necessário; mascarar no PDF (`***.456.789-**`); cifrar em repouso (cast `encrypted`) se mantido. | PDF sem CPF completo; coluna cifrada. | P | D-05 |
| L-03 (S-20) | A4 | Remover o EXIF de todas as imagens no upload e renomear com UUID. | Foto baixada sem metadados GPS. | P | — |
| L-04 | A4 | Anonimização/exclusão de cliente, contato e usuário (preservando o que a lei obriga, ex.: relatório assinado) e exportação dos dados de um titular (JSON/CSV). | Comando/tela de anonimização com teste; exportação gera arquivo completo. | M | Jurídico |
| L-05 | A4 | Rotinas de retenção (comando agendado): fotos, logs, notificações, backups e cache do app conforme os prazos definidos. | O comando apaga o que venceu e registra na auditoria. | M | D-05 |
| L-06 (S-10) | A4, A10, B5 | `mysqldump --single-transaction` agendado no servidor, cifrado (age/GPG), cópia externa + mídias, retenção alinhada a L-05, teste de restauração trimestral; remover `BackupAutomatico`. | Restauração documentada e testada; nenhum `.sql` em claro no servidor. | M | Hospedagem |
| L-07 | A4 | Tela de política de privacidade e aviso no primeiro acesso do app e do web (texto do jurídico). | Aviso exibido e ciência registrada. | P | Jurídico |
| L-08 | A4, A8 | Trilha de leitura de dados pessoais (S-11) e relatório de acessos por titular. | "Quem viu o contato X" respondido pela auditoria. | M | S-11 |

**Decisões para o cliente / jurídico (não técnicas):** base legal por finalidade e LIA do CRM; prazos de retenção; necessidade do CPF do assinante; texto da política de privacidade; plano de resposta a incidentes (responsável, prazos, modelo de comunicação); contratos com operadores (3io Digital, hospedagem, Cloudflare) e avaliação de transferência internacional; regra de uso do WhatsApp/Drive para PDFs com dados pessoais; nomeação do encarregado (DPO) ou enquadramento na dispensa da Resolução CD/ANPD nº 2/2022, se aplicável.

### 6.4 Fase 3 — Padrões e arquitetura até 9 (B1–B11)

| ID | Crit. | Correção | Aceite | Esf. | Dep. |
|---|---|---|---|---|---|
| G-01 | B1, B2, B3 | Policies para Cliente, Orçamento, Roteiro e Relatório (no lugar dos `abort_unless` espalhados); API Resources em todas as respostas da `fae/v1`; regras de relatório (status, edição, assinatura) num Service/Action único usado por web e API; dividir o `AtendimentosRelatoriosController` (1.249 linhas) por sub-recurso; Pint + Larastan nível ≥ 6 sem erros. | `vendor/bin/pint --test` e `phpstan analyse` limpos; nenhum controller com mais de 400 linhas; uma única implementação de troca de status. | G | S-01, S-02 |
| G-02 | B4, B10 | Revisão do modelo (NC02): tabela `config_modelos_revisoes` (snapshot das perguntas/opções), relatório e orçamento guardam `revisao_id`; nova revisão quando o modelo já tem uso; o app envia a revisão usada e o servidor aceita respostas da revisão do relatório. | Os 3 critérios de aceite de revisão do NC02 passam em teste automatizado. | G | D-02 |
| G-03 | B10, B11 | Offline do formulário dinâmico, do NC03 e do BF11 no app, sobre a fila com idempotência (S-14); tela de pendências com erro. | Os critérios "offline" de NC02, NC03, BF05 e RNF01 passam em teste de integração com a rede desligada. | G | S-14, G-02 |
| G-04 | B6 | Corrigir ou remover as 6 falhas conhecidas; banco de teste exclusivo da FAÉ (`buildflow_fae_test`); teste para cada critério de aceite do escopo e para cada achado S-xx; testes de widget/integração do sync no app. | Suíte verde no CI; cobertura ≥ 70% em `app/Http`, `app/Models` e `app/Services`. | G | G-09 |
| G-05 | B8 | Endpoint `/up` (health), log JSON com `request_id`, envio a um coletor (ex.: Sentry ou Loki), alertas de 5xx, fila parada e falha de backup; métricas de sync enviadas pelo app. | Alerta disparado em teste de falha simulada. | M | Hospedagem |
| G-06 | B9, A7 | README próprio (setup, deploy, rollback), `.env.example` comentado (resolve S-24), contrato OpenAPI 3.1 da `fae/v1` gerado e validado em teste (ex.: Scribe), runbook de incidentes. | `openapi.yaml` validado no CI; um dev novo sobe o ambiente só com o README. | M | — |
| G-07 | B7 | Agrupamento de marcadores (Leaflet.markercluster) e limite por área visível; PDF com miniaturas redimensionadas e cache; backup fora do PHP; miniaturas em fila. | PDF com 200 fotos gerado em menos de 30 s e 256 MB; mapa com 5.000 pontos navegável. | M | S-09 |
| G-08 | B10 | Itens parciais do escopo: BF09 "levada"; BF11 fotos; CRM03 comentários no cliente; CRM08 filtro de vendedor; BF01 regra de perfil; formalizar as divergências aceitas (NC01 pré-cadastro, NC04 só fotos, status do BF10). | Matriz da seção 4 sem 🟡 nem ↔ sem registro formal. | M | D-07 |
| S-16 (resto) | A11 | Eliminar os artefatos MCL restantes (docs, Postman, testes, comentários de infraestrutura MCL no app). | `git grep -i mcl` só em histórico/CHANGELOG. | P | S-01 |

### 6.5 Fase 4 — Sustentação

| ID | Crit. | Ação | Aceite | Esf. |
|---|---|---|---|---|
| G-09 | B6, A6 | CI (ex.: GitHub Actions) na `feature/fae` e no app: Pint, Larastan, PHPUnit, `composer audit`, `npm audit`, `flutter analyze`, `flutter test`, varredura de segredos (gitleaks) e OSV-Scanner para o `pubspec.lock`. | Pull request bloqueado quando qualquer etapa falha. | M |
| G-10 | A6 | Dependabot/Renovate para Composer, npm, Gradle e pub; janela mensal de atualização; política de suporte (Laravel/PHP/Flutter). | PRs de atualização automáticos; nenhum aviso Alto aberto há mais de 7 dias. | P |
| G-11 | A10 | Checklist de hardening da hospedagem (Apêndice A) executado e evidenciado antes do go-live. | Relatório de verificação com evidências (prints/comandos). | M |
| G-12 | A1–A11 | Teste de intrusão externo (web + API + APK) antes do go-live e anual. | Relatório sem Alto/Crítico aberto. | M |

### 6.6 Ordem, caminho crítico, riscos de regressão e projeção de notas

**Ordem:** S-06 e S-01 (horas, sem dependência) → S-07 e texto do S-16 → S-04 → S-02 (depende de D-03) → S-03 (depende de D-01) → S-05 parte 1 → **reavaliação parcial** → Fase 1 → Fase 2 (em paralelo com as decisões jurídicas) → Fase 3 → Fase 4. O CI do G-09 pode começar já na Fase 0 para travar regressões.

**Caminho crítico:** D-03 → S-02 → S-14 (idempotência e tratamento de 423 na fila) → G-02 (revisão) → G-03 (formulário offline) → G-04 (testes). É a sequência mais longa e a que mais move B5, B10 e B11.

**Riscos de regressão:**
- **S-01:** o app publicado (v1.3.0+8) já usa só `fae/v1`; confirmar nos logs de acesso que nenhum aparelho antigo chama `/api/mcl/v1` ou `/api/v1` antes de remover.
- **S-02:** o app grava seções depois da aprovação em alguns fluxos (ex.: assinatura do cliente) — alinhar a regra com D-03 e tratar 423 na fila; sem o S-14, a fila descarta o item em silêncio.
- **S-03:** o "apaga e recria" de contatos apagaria os contatos ocultos a quem edita — a sincronização por ID precisa entrar **junto**.
- **S-04:** as telas usam `asset('midia/...')`; o dono precisa continuar recebendo 200. O PDF lê do disco e não é afetado.
- **S-05:** os tokens atuais, hoje sem expiração, passarão a expirar — comunicar e forçar novo login.
- **S-06:** atualização do Symfony dentro do mesmo major; rodar a suíte inteira.
- **S-13:** um novo `applicationId` é outro app para o Android (o antigo precisa ser desinstalado); sincronizar as pendências offline do app antigo antes da troca.

| Crit. | Atual | Fase 0 | Fase 1 | Fase 2 | Fase 3 | Fase 4 |
|---|---|---|---|---|---|---|
| A1 | 5 | 7 | 9 | 9 | 9 | 9 |
| A2 | 4 | 7 | 8 | 9 | 9 | 9 |
| A3 | 7 | 7 | 9 | 9 | 9 | 9 |
| A4 | 3 | 4 | 5 | 8 | 8 | 9 |
| A5 | 7 | 7 | 9 | 9 | 9 | 9 |
| A6 | 5 | 8 | 8 | 8 | 8 | 9 |
| A7 | 4 | 6 | 8 | 8 | 9 | 9 |
| A8 | 5 | 5 | 8 | 9 | 9 | 9 |
| A9 | 4 | 5 | 8 | 9 | 9 | 9 |
| A10 | 4 | 4 | 6 | 7 | 7 | 9 |
| A11 | 5 | 6 | 8 | 8 | 9 | 9 |
| B1 | 6 | 6 | 6 | 6 | 9 | 9 |
| B2 | 6 | 6 | 6 | 6 | 9 | 9 |
| B3 | 6 | 6 | 6 | 6 | 9 | 9 |
| B4 | 6 | 6 | 6 | 6 | 9 | 9 |
| B5 | 5 | 5 | 8 | 8 | 9 | 9 |
| B6 | 5 | 5 | 6 | 6 | 8 | 9 |
| B7 | 6 | 6 | 7 | 7 | 9 | 9 |
| B8 | 4 | 4 | 5 | 5 | 8 | 9 |
| B9 | 6 | 6 | 6 | 7 | 9 | 9 |
| B10 | 5 | 6 | 7 | 7 | 9 | 9 |
| B11 | 6 | 6 | 7 | 7 | 9 | 9 |

A projeção supõe as decisões D-01..D-07 tomadas a tempo; A4 e A10 dependem de ações fora do código (jurídico e hospedagem).

**Plano de reavaliação:** (1) reavaliação parcial ao fim da Fase 0 — só os achados Altos, com os testes de aceite rodando; (2) reavaliação completa ao fim da Fase 3, usando o checklist da seção 7; (3) reavaliação final ao fim da Fase 4, com o resultado do teste de intrusão. **Condição de saída: todos os critérios ≥ 9 e nenhum achado Alto/Crítico aberto.**

### 6.7 Decisões de escopo pendentes com o cliente × correções puramente técnicas

**Decisões do cliente (bloqueiam ou mudam a correção):**

| ID | Decisão | Afeta |
|---|---|---|
| D-01 | Quem é o "dono" de um contato Comercial: quem cadastrou, o vendedor responsável do cliente ou ambos? O perfil Comercial (não Vendedor) vê contatos comerciais de todos? | S-03 |
| D-02 | Revisão do modelo: snapshot por revisão (recomendado) ou clonagem do modelo; o que conta como "alteração relevante". | G-02 |
| D-03 | O que pode mudar depois de Aprovado: só a assinatura do cliente (uma vez)? Observação interna? Compartilhamentos? Quem pode devolver para Revisar um relatório já assinado pelo cliente? | S-02 |
| D-04 | Comprovante: exibir 10 caracteres (guardando o completo) atende? O orçamento terá PDF e comprovante (§4 × §8 do documento)? | S-17, BF07 |
| D-05 | Prazos de retenção (fotos, contatos de prospecção, logs, backups) e necessidade do CPF. | L-02, L-05, L-06 |
| D-06 | Acesso do fornecedor: manter a conta de acesso total? Em que condições (contrato, *break-glass*)? | S-12 |
| D-07 | Formalizar as divergências já decididas: pré-cadastro removido (NC01), anexos gerais só fotos (NC04), nomes de status do BF10, geolocalização por permissão (BF01), BF12 fora desta fase. | B10 |
| D-08 | MFA no app: obrigatório para todos (como no web) ou só para Administrador, Assistência e usuário protegido? | S-05 (parte 2) |

**Correções puramente técnicas (sem decisão):** S-01, S-04, S-05, S-06, S-07, S-08, S-09, S-10 (exceto prazos de retenção), S-11, S-13, S-14, S-16 (exceto o texto jurídico), S-18 a S-24, G-01, G-04 a G-07, G-09 a G-12.

---

## 7. Checklist de reavaliação

Cada item precisa de evidência anexada (teste automatizado, saída de comando ou print).

**Autenticação e autorização**
- [ ] `route:list --path=api` mostra só `api/fae/v1/*`; testes de 404 para `/api/v1` e `/api/mcl/v1`.
- [ ] Login da API: bloqueio por conta após 5 erros; MFA exigido para os perfis definidos; token expira (absoluto e por inatividade); habilidade restrita.
- [ ] Política de senha ≥ 10 caracteres com checagem de vazamento; troca de e-mail/senha exige a senha atual.
- [ ] O cadastro do MFA não é possível só com a senha.
- [ ] Teste de matriz: para cada perfil × cada rota (web e API), o resultado bate com "Perfis de Acesso".
- [ ] `/midia`: 403 para arquivo de atendimento de outro técnico; 200 para o dono e para acesso total.
- [ ] `clientes/{id}/resumo` respeita `visivelPara`.
- [ ] Contato Comercial não aparece (HTML/JSON/PDF/histórico) para a Assistência nem para outro vendedor; editar o cliente não apaga contatos ocultos.

**Regras de negócio críticas**
- [ ] Toda rota de escrita em relatório Aprovado devolve 423 (web e API), inclusive para payload atrasado da fila do app.
- [ ] Assinatura do cliente só após a aprovação, uma única vez, em arquivo imutável e com o hash do PDF guardado.
- [ ] Comprovante verificável (HMAC) e tabela só de inserção.
- [ ] Revisão de modelo: os três critérios de aceite do NC02 passam.
- [ ] Nenhum texto de outro cliente nos PDFs.

**Sincronização**
- [ ] Retentativa após timeout não duplica relatório, anexo ou resposta (teste com rede simulada).
- [ ] Item recusado (4xx) fica visível ao técnico com ação; nada é descartado em silêncio.
- [ ] Conflito de edição web × app detectado (409).
- [ ] O servidor rejeita app abaixo da versão mínima (426).
- [ ] Formulário dinâmico, foto por pergunta e observação interna funcionam offline e sincronizam.

**App**
- [ ] `applicationId` exclusivo da FAÉ; APK assinado com chave de release; build ofuscado.
- [ ] Token no Keystore; SQLite cifrado; acesso offline expira; dados apagados ao detectar usuário desativado.
- [ ] URL de produção real em HTTPS; APK de atualização por HTTPS com hash conferido.

**Dados e LGPD**
- [ ] ROPA aprovado; política de privacidade publicada e exibida.
- [ ] CPF mascarado no PDF (ou removido); EXIF removido das fotos.
- [ ] Anonimização/exclusão e exportação por titular testadas.
- [ ] Rotina de retenção ativa; backups cifrados, externos, com restauração testada.
- [ ] Trilha de auditoria cobre escrita (web e API) e leitura de dados pessoais; tabela não alterável pela aplicação.

**Sustentação**
- [ ] CI verde com Pint, Larastan, PHPUnit (sem falhas conhecidas), `composer audit`, `npm audit`, `flutter analyze/test`, gitleaks e OSV-Scanner.
- [ ] `composer audit --locked` sem avisos abertos.
- [ ] README, `.env.example`, OpenAPI e runbook atualizados.
- [ ] Checklist do Apêndice A evidenciado no ambiente de produção.
- [ ] Teste de intrusão externo sem Alto/Crítico.

---

## Apêndice A — Requisitos mínimos de hardening para a futura hospedagem (A10)

Complementa o documento *Requisitos de Infraestrutura* (24/09/2026), que cobre só a parte funcional.

**Rede e borda**
- TLS 1.2+ (preferir 1.3) com certificado válido; Cloudflare em modo *Full (strict)*; HSTS com `includeSubDomains` depois de validar todos os subdomínios.
- WAF (regras gerenciadas da Cloudflare ou ModSecurity com OWASP CRS) e limite de taxa na borda para `/login`, `/api/fae/v1/login` e uploads.
- Firewall do servidor (ufw/nftables): só 80/443 abertas ao público; SSH só por chave, sem root, de IPs administrativos ou por VPN; a origem aceita HTTP só das faixas da Cloudflare.
- `TrustProxies` configurado com as faixas da Cloudflare.

**Servidor e aplicação**
- Ubuntu LTS com atualizações automáticas de segurança; PHP 8.4 no último patch; `expose_php=Off`; `shell_exec` desabilitado se a miniatura de vídeo for movida para um serviço isolado.
- Usuário do PHP-FPM sem permissão de escrita fora de `storage/` e `bootstrap/cache/`; código somente leitura.
- `.env` de produção com `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` própria, `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true` e `QUEUE_CONNECTION=database` com worker supervisionado.
- Ambientes segregados: produção, homologação e desenvolvimento com bancos, chaves, domínios e usuários diferentes; nenhum dado real fora da produção sem anonimização.

**Banco de dados**
- MySQL sem porta exposta à internet (escutando só em `127.0.0.1` ou rede privada); usuário da aplicação com privilégios mínimos (sem `DROP`/`GRANT`; sem `UPDATE/DELETE` em `logs_auditoria`); usuário separado para as migrations.
- Criptografia em repouso do volume (LUKS ou recurso do provedor).

**Armazenamento de arquivos**
- Fotos, assinaturas e anexos fora da raiz web (já é assim), entregues só pela aplicação com checagem de posse ou por URL assinada; cota de disco e alerta de uso.

**Backups e continuidade**
- Backup diário do banco (`mysqldump --single-transaction`) e das mídias, cifrado, enviado para fora do servidor (outro provedor ou região), com retenção definida em D-05 e teste de restauração trimestral registrado.
- RPO e RTO definidos com o cliente.

**Segredos e acesso**
- Segredos fora do repositório, com acesso restrito e registro de quem acessa; rotação da `APP_KEY` (com recriptografia dos campos `encrypted`), da senha do banco e das chaves de terceiros ao menos uma vez por ano e após qualquer desligamento de pessoa com acesso.
- MFA em todas as contas administrativas de infraestrutura (provedor, Cloudflare, DNS, repositório).

**Monitoramento**
- Health check externo; alertas de 5xx, de fila parada, de falha de backup, de certificado perto de vencer e de disco; logs centralizados com retenção definida.
