# Sessão Android — Bloco 3 (CRM Comercial, CRM09) · Etapa 1: Telas + Endpoints — PRIORIDADE

Ver [00-indice.md](00-indice.md). Pré-requisito: [13-android-configurador-telas.md](13-android-configurador-telas.md) — a tela de Orçamento reaproveita o motor de formulário dinâmico construído lá.

Bloco priorizado pelo usuário — vem antes do Bloco 4 (sessão 17), mesmo sendo o maior bloco novo do app.

## Escopo: CRM09 (telas de orçamento, roteiro de viagem, mapa de relações e indicadores) — **com os endpoints REST novos**

Repositórios envolvidos: `app_buildflow_fae` (telas) **e** `sys-buildflow-fae` (endpoints `fae/v1`).

## Estado atual
Zero telas/rotas CRM no app — greenfield total. No Web, os 4 itens abaixo já estão completos e aprovados pelo cliente (`orcamentos/*`, `roteiros-viagem/*`, `mapa-relacoes/*`, `indicadores-comerciais/*` em `sys-buildflow-fae`) — usar como referência funcional direta.

## Parte A — Endpoints novos em `sys-buildflow-fae` (`routes/api.php`, grupo `fae/v1`)

Reaproveitar os models/Requests/Repositories já existentes (`Orcamento`, `OrcamentoRequest`, `OrcamentoRepository`, `RoteiroViagem`, `RoteiroViagemRequest`, etc.) — só expor como JSON, não duplicar regra:

- **Orçamentos**: `GET/POST /fae/v1/orcamentos`, `GET/PUT /fae/v1/orcamentos/{id}`. Campos principais: `orc_cliente_id`, `orc_vendedor_id`, `orc_tipo_orcamento_id`, `orc_nivel`, `orc_prazo_envio` (manual — **fórmula automática de CRM02 não existe ainda, nem no Web**, não inventar cálculo aqui), `vendedores_adicionais[]` (CRM04). Respostas às perguntas do modelo (setor Comercial) reaproveitam os MESMOS endpoints de formulário dinâmico da sessão 13 (`/relatorios/.../formulario` é específico de atendimento — para orçamento, expor o equivalente: `GET /fae/v1/orcamentos/{id}/formulario`, `POST /fae/v1/orcamentos/{id}/respostas`, mesma estrutura de dados).
  - `POST /fae/v1/orcamentos/{id}/comentarios`, `DELETE /fae/v1/orcamentos/{id}/comentarios/{comentarioId}` — CRM03 (comentário com alerta opcional a outro usuário; exclusão foi adicionada por pedido do cliente, `BF_v1.8.3`).
- **Roteiro de Viagem**: `GET/POST /fae/v1/roteiros-viagem`, `GET/PUT /fae/v1/roteiros-viagem/{id}`. Campos: `crm_rot_vendedor_id`, `crm_rot_periodo_inicio`/`fim`, `crm_rot_link_mapa` (link colado, sem picker — mesmo padrão do Cliente), `crm_rot_status`, lista de clientes a visitar (conferir `RoteiroViagemRequest`/model pra estrutura exata da lista).
- **Mapa de Relações** (CRM07): `GET /fae/v1/mapa-relacoes` — lista de clientes com coordenada (`cli_latitude`/`cli_longitude`) + indicação de caso de sucesso, mesmo dado que o `MapaRelacoesController::index()` monta pro Leaflet do Web. **Só leitura.**
- **Indicadores Comerciais** (CRM08): `GET /fae/v1/indicadores-comerciais` — mesmos números que `IndicadoresComerciaisController::index()` calcula no Web (básico, sem linha do tempo — fora de escopo confirmado).

Middleware: `auth:sanctum` + perfil Comercial (mesma checagem do middleware `comercial` do web) em todos os endpoints deste bloco, exceto leitura quando fizer sentido (avaliar caso a caso, seguindo o padrão do web).

**Ao terminar a Parte A:** revisão de segurança do MINOR, testes `tests/Feature/Api/CrmApiFaeTest.php` cobrindo pelo menos criar orçamento, comentar, excluir comentário, criar roteiro, listar mapa de relações.

## Parte B — Telas no `app_buildflow_fae`

- Novas rotas no `go_router`, visíveis só a perfil Comercial (gating da sessão 10/11):
  - **Orçamento**: lista + formulário com abas Dados / Perguntas (reaproveita o motor de formulário dinâmico da sessão 13, trocando só o endpoint e o setor do modelo pra Comercial) / Vendedores Adicionais / Comentários (com opção de excluir, igual ao Web).
  - **Roteiro de Viagem**: lista + formulário (saída/retorno, período, link do Maps colado + abrir externo via `url_launcher` — **sem mapa embutido aqui**, mesma decisão do Cliente).
  - **Mapa de Relações**: tela só-leitura com os pontos de cliente. **Decisão a confirmar com o usuário nesta sessão**: exibir num mapa embutido (precisa `google_maps_flutter`/`flutter_map`, chave de API, configuração Android extra) ou como lista/lista+link externo por cliente (mais simples, sem dependência nova). Não fixar a dependência de mapa sem essa confirmação.
  - **Indicadores Comerciais**: tela de dashboard simples com os números do endpoint.

## Não faz parte desta sessão
Sincronização offline das entidades novas (orçamento, roteiro, comentários) — isso é a sessão 16, o maior esforço de sync do app dado o volume de entidades.

## Critérios de aceite a verificar
- Usuário Comercial acessa Orçamento/Roteiro/Mapa/Indicadores; usuário Técnico não vê nenhum desses itens de menu.
- Orçamento criado no app aparece no Web (`orcamentos.index`) e vice-versa.
- Excluir um comentário de orçamento no app reflete no Web.

## Próxima sessão
[17-android-atendimento-telas.md](17-android-atendimento-telas.md)