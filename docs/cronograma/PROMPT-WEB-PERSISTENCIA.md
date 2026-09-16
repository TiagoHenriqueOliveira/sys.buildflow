# Prompt de início — Sessão Web (Persistência) FAÉ Bioenergia

Cole isto como primeira mensagem de uma nova sessão do Claude Code, com o diretório de trabalho em `C:\Apache24\htdocs\sys-buildflow-fae` (branch `feature/fae`).

---

Você vai executar o passe de **Persistência/Regras de Negócio** do projeto FAÉ Bioenergia (Buildflow + CRM Comercial), neste repositório (`sys-buildflow-fae`, branch `feature/fae`). As Telas dos 4 blocos (Núcleo, Configurador, CRM, Atendimento) já estão concluídas e aprovadas pelo cliente (`BF_v1.8.5`) — este passe fecha as regras de negócio mais finas que ficaram deliberadamente de fora até agora.

## Por onde começar

Leia primeiro `docs/cronograma/00-indice.md` — panorama geral e estado atual real do código (atualizado em 14-16/09/2026, direto do código — não confie em uma leitura anterior sua se já tiver contexto antigo deste projeto). Depois disso, siga em ordem:

1. `docs/cronograma/03-web-nucleo-persistencia.md` — pré-cadastro/aprovação de Cliente, visibilidade por vendedor, histórico consolidado, CNPJ único.
2. `docs/cronograma/05-web-configurador-persistencia.md` — versionamento de modelo (o item mais delicado do passe inteiro).
3. `docs/cronograma/07-web-crm-persistencia.md` — prazo configurável (CRM02) e status de fechamento do orçamento (CRM08).
4. `docs/cronograma/09-web-atendimento-persistencia.md` — bloqueio de edição pós-aprovação (BF10) e checklist "peça levada" (BF09).

**Uma sessão de cada vez** — não encadear os 4 blocos sem checkpoint, mesmo que o contexto permita.

## Para cada sessão

1. Leia o arquivo `.md` correspondente por completo antes de tocar em código — cada um já tem "Estado atual" (o que já existe, não refazer), "Entregáveis" e, em vários casos, uma seção **"⚠️ Decisão a confirmar com o usuário"**.
2. **Pare e me pergunte nos pontos marcados como decisão em aberto antes de implementar** — não escolher sozinho. São decisões reais de arquitetura/regra de negócio (ex.: quem pré-cadastra e quem aprova cliente; clonar modelo vs. tabela de revisão dedicada; o que "alerta de recontato" significa na prática), não detalhes de implementação.
3. Implemente exatamente o que está descrito em "Entregáveis" — nem mais, nem menos.
4. Ao terminar, rode `php artisan test` e confirme que a baseline conhecida não mudou (6 falhas pré-existentes em `AtendimentoRelatorioTest`, sem relação com este trabalho — se esse número mudar, investigue antes de seguir) e que a suíte cresceu com os testes novos da sessão.
5. Siga o "Ao finalizar" de cada arquivo: testes cobrindo os critérios de aceite, bump de versão **MINOR** (`.env` `APP_VERSION`, convenção `BF_vMAJOR.MINOR.PATCH` — cada um destes 4 blocos fecha um item NC/BF/CRM completo, então é MINOR, não PATCH), e a revisão de segurança de rotas descrita no `CLAUDE.md`.
6. **Pare ao final de cada sessão** e me avise o que foi entregue antes de seguir para a próxima.

## Regras gerais do projeto

- Pode commitar localmente sem pedir permissão a cada vez; **nunca fazer `git push`** sem eu pedir explicitamente.
- Nunca rodar comando que apague ou modifique dado (local ou produção) sem confirmar comigo antes.
- Toda alteração de schema é uma migration nova, versionada nesta branch — nunca aplicar em banco de outro cliente.
- Alguns itens dependem de pendências do cliente ainda sem resposta (histórico completo de aprovação, tipo de resposta "Imagem", linha do tempo do orçamento — ver tabela em `00-indice.md`). Se a resposta tiver chegado entre a escrita destes arquivos e agora, me avise antes de seguir com o default documentado.
- Responda sempre em português do Brasil.

Comece lendo `docs/cronograma/00-indice.md` e depois `docs/cronograma/03-web-nucleo-persistencia.md`, e me diga o que entendeu — inclusive as decisões em aberto que já conseguir identificar — antes de começar a implementar.