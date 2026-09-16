# Prompt de início — Sessão Android (FAÉ Bioenergia)

Cole isto como primeira mensagem de uma nova sessão do Claude Code, com o diretório de trabalho em `C:\Users\tiago\StudioProjects\app_buildflow_fae` (branch `feature/fae`, Flutter).

---

Você vai executar o cronograma de desenvolvimento do projeto FAÉ Bioenergia (Buildflow + CRM Comercial), lado Android/mobile, com o objetivo de ter **todas as telas navegáveis, com dado real, para apresentar ao cliente** — antes de implementar as regras de negócio mais pesadas (persistência/sync completo).

## Escopo desta leva de sessões (decisão do usuário, 14/09/2026)

- **Dado real desde já**: as telas consomem endpoints REST de verdade, gravando no mesmo banco (`buildflow_fae`) que o Web usa — nada de mock/fixture local.
- **Os endpoints novos são criados JUNTO com cada tela**, na mesma sessão — este trabalho é **cross-repositório**: você vai precisar ler e editar tanto `C:\Users\tiago\StudioProjects\app_buildflow_fae` (Flutter, diretório de trabalho principal) quanto `C:\Apache24\htdocs\sys-buildflow-fae` (Laravel, onde os endpoints `fae/v1` novos entram em `routes/api.php` + controllers `Api\Fae\*`). Leia o `CLAUDE.md` de cada um dos dois projetos antes de mexer neles.
- **Fica de fora desta leva**: as sessões de Persistência/Sync (12, 14, 16, 18 no índice) — fórmulas automáticas, hash mais fino, aprovação com histórico completo, e principalmente a fila de sincronização offline para as entidades novas. Não iniciar essas sessões sem eu pedir explicitamente.

## Por onde começar

Todo o cronograma (Web + Android) está em `C:\Apache24\htdocs\sys-buildflow-fae\docs\cronograma\` (sim, é no repositório do Web, mesmo você trabalhando no do Flutter — é onde ficam todos os arquivos do plano). Leia nesta ordem:

1. `docs/cronograma/00-indice.md` — panorama geral e **estado atual real do código** (atualizado em 14/09/2026, direto do Web já concluído — não confie em uma leitura anterior sua se já tiver contexto antigo sobre este projeto).
2. `docs/cronograma/10-android-preparacao.md`
3. `docs/cronograma/11-android-nucleo-telas.md` (Cliente + endpoints)
4. `docs/cronograma/13-android-configurador-telas.md` (motor de formulário dinâmico + endpoints — o maior/mais complexo)
5. `docs/cronograma/15-android-crm-telas.md` (Orçamento/Roteiro/Mapa/Indicadores + endpoints — priorizado pelo usuário)
6. `docs/cronograma/17-android-atendimento-telas.md` (incrementos BF07-11 + endpoints)

**Pule as sessões 12/14/16/18** (Persistência) — não fazem parte desta leva.

## Para cada sessão

1. Leia o arquivo `.md` correspondente por completo antes de tocar em código — cada um já tem a Parte A (endpoints a criar em `sys-buildflow-fae`) e a Parte B (telas a criar em `app_buildflow_fae`) detalhadas, com referência aos controllers/models/rotas reais do Web a reaproveitar (não reimplementar regra que já existe lá — expor como JSON).
2. Implemente exatamente o que está descrito em "Entregáveis" — nem mais, nem menos.
3. Ao terminar a Parte A (endpoints), rode a suíte de testes PHP (`php artisan test`, em `sys-buildflow-fae`) e confirme que não introduziu regressão na baseline conhecida (6 falhas pré-existentes em `AtendimentoRelatorioTest`, inalteradas há várias sessões — se esse número mudar, investigue antes de seguir).
4. Ao terminar a Parte B (telas), confira os itens de "Critérios de aceite a verificar" rodando o app de verdade (emulador ou aparelho físico) contra o backend local — teste o fluxo completo, não só leia o código.
5. Onde a sessão pedir uma decisão explícita (ex.: mapa embutido vs. lista simples no Mapa de Relações/Mapa de Demandas; o que fazer com `sections/*` legado; se implementa `aten_rel_peca_levada`), **pare e me pergunte antes de decidir sozinho** — são pontos que o próprio arquivo da sessão sinaliza como em aberto.
6. **Pare ao final de cada sessão** e me avise o que foi entregue (nos dois repositórios) antes de seguir para a próxima.

## Regras gerais dos dois projetos

- Pode commitar localmente em cada repositório sem pedir permissão a cada vez; **nunca fazer `git push`** sem eu pedir explicitamente, em nenhum dos dois.
- Nunca rodar comando que apague ou modifique dado (local ou produção) sem confirmar comigo antes.
- Toda alteração de schema no `sys-buildflow-fae` é uma migration nova, versionada em `feature/fae` — nunca aplicar em banco de outro cliente.
- Convenção de commit `BF_vMAJOR.MINOR.PATCH` nos dois repositórios (o Flutter usa o build number do `pubspec.yaml`, formato `X.Y.Z+N`, mesma convenção). A cada bump de **MINOR**, fazer a revisão de segurança descrita nos dois `CLAUDE.md` (rotas sem `auth`/`sanctum`).
- Se ao editar arquivos em `sys-buildflow-fae` as ferramentas de Edit/Write reclamarem de um worktree incompatível (bug já visto nesse projeto), o fallback é PowerShell (`[System.IO.File]::WriteAllText`/`ReadAllText`, não `Set-Content`/`Get-Content`, que corrompem acentuação) — não insista tentando Edit/Write repetidamente se isso acontecer.
- Responda sempre em português do Brasil (pedido explícito do cliente, vale para as respostas em texto/chat nos dois projetos).

Comece lendo `docs/cronograma/00-indice.md` e depois `docs/cronograma/10-android-preparacao.md`, e me diga o que entendeu (inclusive as decisões em aberto que já conseguir identificar) antes de começar a implementar.