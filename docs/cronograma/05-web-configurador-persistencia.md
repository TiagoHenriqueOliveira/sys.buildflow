# Sessão Web — Bloco 2 (Configurador) · Etapa 2: Persistência

Ver [00-indice.md](00-indice.md). Pré-requisito: [03-web-nucleo-persistencia.md](03-web-nucleo-persistencia.md).

**Reescrito em 16/09/2026** com base em auditoria direta do código atual (`BF_v1.8.5`). Não confiar em versão anterior deste documento (09/09/2026) — o schema real de `ConfigPergunta`/`ConfigModelo` é bem diferente do que ela descrevia (inclusive o mecanismo de "Sessão", que nem existia na época).

## Escopo: NC02 (versionamento de modelo) — item único e central deste bloco

**NC03, NC04 e BF04 já estão completos** — anexo de imagem + comentário por pergunta, anexos gerais do relatório e vínculo modelo↔natureza de atendimento já funcionam de ponta a ponta. Não fazem parte desta sessão.

## Estado atual (auditado direto no código)

- `ConfigPergunta`/`ConfigModelo` (schema real: `config_perguntas`, `config_modelos`, pivot `config_modelos_perguntas` com `cfg_mod_perg_ordem` + `cfg_mod_perg_sessao_id`) **editam em lugar** — não existe snapshot/revisão nenhuma.
- Isso foi uma decisão deliberada das sessões de Telas ("sem revisão versionada — fora de escopo desta fase"), não um esquecimento.
- **Consequência prática hoje**: editar um modelo já usado por atendimentos/orçamentos antigos altera retroativamente a estrutura que aparece nesses registros antigos (ex.: remover uma pergunta do modelo faz ela sumir até de relatórios já preenchidos e aprovados). É o principal risco de dado a resolver nesta sessão.

## ⚠️ Decisão de arquitetura a confirmar antes de implementar

Duas formas de resolver, com trade-offs bem diferentes:

**Opção A — Clonagem do modelo inteiro (recomendada, menor risco/esforço).** Ao editar um modelo que já tem pelo menos um atendimento/relatório ou orçamento vinculado, em vez de alterar as linhas existentes, criar um **novo registro** em `config_modelos` (clone com as perguntas/pivot copiadas), marcar o antigo como inativo (`cfg_mod_ativo=false`) e apontar `NaturezaAtendimento`/`CrmTipoOrcamento` pro novo. Vantagem: reaproveita 100% da estrutura já existente (nenhuma tabela nova, `ConfigModelo`/`perguntasAgrupadasPorSessao()` continuam funcionando sem mudança), registros antigos continuam apontando pro modelo antigo automaticamente. Desvantagem: gera modelos "órfãos" acumulados ao longo do tempo (mitigável escondendo inativos da listagem por padrão, já existe o filtro de status).

**Opção B — Tabela de revisões dedicada (`config_modelos_revisoes`).** Mais fiel à ideia original do documento de requisitos, mas exige uma tabela nova + reescrever `perguntasAgrupadasPorSessao()`/`getRespostas()` pra sempre resolver "qual revisão está vigente pra este registro específico" em vez de "as perguntas do modelo atual". Mais trabalho, mais uma camada de indireção pra manter.

**Recomendo a Opção A** pelo menor raio de mudança e reaproveitamento total do que já existe — mas é uma decisão do usuário, não decidir sozinho na hora de implementar.

## Entregáveis (assumindo Opção A confirmada)

- `ConfigModelo` ganha `cfg_mod_revisao_de` (nullable, FK pra `config_modelos.cfg_mod_id`) — aponta pro modelo "raiz" da família de revisões, útil pra listar o histórico de versões de um mesmo modelo na tela.
- `ConfiguradorModeloRepository::update()`: se `$modelo->temUsoVinculado()` (novo método: existe `AtendimentoRelatorio`/`Orcamento` referenciando esse modelo, direta ou indiretamente via `NaturezaAtendimento`/`CrmTipoOrcamento`), clonar em vez de atualizar in-place. Se não tem uso vinculado ainda (modelo criado e nunca usado), pode continuar editando in-place normalmente — não precisa clonar modelo que ninguém usou ainda.
- Atualizar `NaturezaAtendimento`/`CrmTipoOrcamento` (o que apontava pro modelo antigo) pra apontar pro clone novo.
- Tela `configurador/modelos/index.blade.php`: indicar visualmente quando um modelo é uma revisão de outro (ex.: "Revisão de: <nome> — v2").

## Testes

Criar/estender `tests/Feature/ConfiguradorPersistenciaFaeTest.php`:
- Editar modelo sem uso vinculado → altera in-place (comportamento atual, preservado).
- Editar modelo já usado por um relatório existente → cria clone; o relatório antigo continua mostrando a estrutura de perguntas de antes da edição; um relatório novo (criado depois da edição) usa a estrutura nova.
- Mesmo teste para Orçamento (setor Comercial).
- `NaturezaAtendimento`/`CrmTipoOrcamento` passam a apontar pro modelo clonado após a edição.

## Ao finalizar

- Bump de versão **MINOR**.
- Revisão de segurança: nenhuma rota nova fica sem `auth`/gate de admin (o Configurador já é admin-only, confirmar que a rota de "ver revisões" segue a mesma regra).

## Próxima sessão

[07-web-crm-persistencia.md](07-web-crm-persistencia.md) — **Bloco 3, CRM Comercial, priorizado.**