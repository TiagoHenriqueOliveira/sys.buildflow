# Sessão Android — Bloco 2 (Configurador → motor de formulário dinâmico) · Etapa 1: Telas + Endpoints

Ver [00-indice.md](00-indice.md). Pré-requisito: [11-android-nucleo-telas.md](11-android-nucleo-telas.md).

## Escopo: BF05, NC03, NC04 (mobile) — **com os endpoints REST novos**

Repositórios envolvidos: `app_buildflow_fae` (telas) **e** `sys-buildflow-fae` (endpoints `fae/v1`).

**Importante — o que NÃO faz parte desta sessão:** o Configurador (cadastro de Perguntas/Modelos) é ferramenta **admin-only**, usada só no Web (`configurador/perguntas`, `configurador/modelos`) por quem monta os modelos. **O app mobile nunca cria/edita pergunta ou modelo** — só **consome** a estrutura já montada pra renderizar o formulário de preenchimento. Não criar tela de CRUD de perguntas/modelos no Flutter.

## Estado atual (ponto de partida)

- `lib/features/relatorios/relatorio_form_screen.dart` + `lib/features/relatorios/sections/*` implementam um formulário **fixo** herdado do MCL (seções: clima, descricao, horarios, info_adicionais, ocorrencias, pecas, servicos, status, assinaturas, anexos) — não consome nenhum banco de perguntas.
- No Web, esse mesmo formulário fixo **já foi substituído** por perguntas dinâmicas (`atendimentos-relatorios/show.blade.php`) — as abas fixas antigas só aparecem se o relatório específico já tiver dado legado; todo relatório novo usa só "Perguntas" + abas de "Sessão". **É esse o comportamento a replicar no app, não o antigo.**
- `routes/api.php` (`fae/v1`) ainda expõe os endpoints antigos e fixos (`/relatorios/{id}/horarios`, `/clima`, `/servicos`, `/pecas`, `/ocorrencias`...) — mantê-los por enquanto (compatibilidade com relatórios antigos, mesma lógica condicional do Web), mas o formulário novo não deve depender deles.

## Parte A — Endpoints novos em `sys-buildflow-fae` (`routes/api.php`, grupo `fae/v1`)

Criar em `Api\Fae\RelatoriosController` (ou um novo `Api\Fae\RelatorioFormularioController`), reaproveitando a lógica já existente em `AtendimentosRelatoriosController` (web) — **não reimplementar do zero**, extrair/expor a mesma regra:

- `GET /fae/v1/relatorios/{id}/formulario` — devolve o modelo vinculado ao relatório já agrupado: `{ genericas: [...], sessoes: [{ id, nome, perguntas: [...] }] }`. Espelha exatamente `ConfigModelo::perguntasAgrupadasPorSessao()` + o mapeamento de `AtendimentosRelatoriosController::getRespostas()` (cada pergunta com `id`, `texto`, `tipo` (0=EscolhaÚnica/1=MúltiplaEscolha/2=TextoLivre — conferir `App\Enums\TipoPergunta`), `opcoes` quando aplicável, `permite_anexo`, `repetivel`, e as `respostas` já salvas (cada uma com `id`, `valor`, `fotos: [{id,url,comentario}]`).
- `POST /fae/v1/relatorios/{id}/respostas` — salvar resposta. Campos: `pergunta_id`, `valor` (nullable), `foto` (arquivo, opcional), `foto_comentario`. Mesma regra do web (`AtendimentosRelatoriosController::storeResposta()`): pergunta repetível sempre cria uma linha nova; não-repetível atualiza a existente. **Reaproveitar o model `AtendimentoRelatorioResposta`/`AtendimentoRelatorioRespostaFoto`, não criar tabela nova.**
- `DELETE /fae/v1/relatorios/{id}/respostas/{respostaId}` — remover resposta (e fotos associadas).
- `DELETE /fae/v1/relatorios/{id}/respostas-fotos/{fotoId}` — remover só a foto de uma resposta.

Middleware: `auth:sanctum` + a mesma checagem de posse já usada nos outros endpoints de relatório do app (`relatorioComPosseGarantida()` no web — replicar a mesma regra: só o técnico dono do atendimento, ou perfil com mais acesso).

**Ao terminar a Parte A:** revisão de segurança do MINOR (rotas sem auth), testes `tests/Feature/Api/RelatorioFormularioApiFaeTest.php` cobrindo: buscar formulário com Sessão, salvar resposta simples, salvar resposta repetível (várias linhas), remover resposta, remover foto.

## Parte B — Motor de formulário dinâmico no `app_buildflow_fae`

- Novo componente/tela que substitui a montagem fixa de `relatorio_form_screen.dart`: monta os campos a partir do endpoint `/formulario` (Parte A), com uma aba por item de `sessoes` (igual à UX do Web: abas dinâmicas, uma "genérica" e uma por Sessão) e o componente certo por `tipo` de pergunta (texto livre, escolha única = radio, múltipla escolha = checkboxes).
- Suportar `permite_anexo` (campo de foto por pergunta, com `image_picker` — já é dependência do projeto) e `repetivel` (botão "adicionar outra resposta", lista de respostas já salvas com opção de remover — mesma UX do Web).
- **Decisão técnica a registrar nesta sessão:** as seções fixas atuais (`sections/*`) ficam mantidas só pra relatórios antigos que já têm dado legado (mesma checagem condicional do Web: `$temClimaLegado`, `$temServicosLegado`, etc. — replicar client-side, o endpoint `/formulario` pode devolver essas flags também) ou são descontinuadas de vez — decidir e documentar aqui antes de tocar no código, não misturar os dois formulários numa tela só sem essa decisão explícita.
- **NC03**: anexo de imagem + comentário por pergunta (parte do componente acima).
- **NC04**: anexos gerais do relatório (fotos, já existe endpoint no fluxo legado `/anexos` — só confirmar que a tela nova também usa/mostra isso, não é exclusivo do formulário fixo).

## Não faz parte desta sessão
Cache local do formulário/respostas e fila de sincronização offline — isso é a sessão 14. Aqui a tela consome a API diretamente (online).

## Critérios de aceite a verificar
- Preencher um relatório usando um modelo com pergunta de Sessão, online: aba da Sessão aparece, perguntas certas dentro dela (comparar lado a lado com o mesmo relatório aberto no Web).
- Pergunta repetível permite adicionar várias respostas com foto individual.
- Pergunta com `permite_anexo` mostra campo de foto + comentário.
- Relatório antigo (com dado legado de clima/serviços/peças) ainda abre corretamente — decisão da sessão sobre `sections/*` não quebrou relatórios existentes.

## Próxima sessão
[15-android-crm-telas.md](15-android-crm-telas.md)