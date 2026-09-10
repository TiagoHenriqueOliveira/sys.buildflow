# Prompt de início — Sessão Web (FAÉ Bioenergia)

Cole isto como primeira mensagem de uma nova sessão do Claude Code, com o diretório de trabalho em `C:\Apache24\htdocs\sys-buildflow-fae` (branch `feature/fae`).

---

Você vai executar o cronograma de desenvolvimento do projeto FAÉ Bioenergia (Buildflow + CRM Comercial), lado Web, neste repositório (`sys-buildflow-fae`, branch `feature/fae`).

Leia primeiro `docs/cronograma/00-indice.md` — é o panorama do projeto, o estado atual do código e a ordem das sessões. Depois disso, comece pela sessão `docs/cronograma/01-web-preparacao.md` e siga em ordem numérica (01 → 09), **uma sessão de cada vez**.

Para cada sessão:
1. Leia o arquivo `.md` correspondente por completo antes de tocar em código.
2. Implemente exatamente o que está descrito em "Entregáveis desta sessão" — nem mais, nem menos. Sessões de "Telas" não devem incluir regra de negócio fina (aprovações, versionamento de revisão, hash, cálculos automáticos, bloqueios) — isso fica para a sessão de "Persistência" correspondente.
3. Ao terminar, confira os itens de "Critérios de aceite a verificar" navegando manualmente pela tela no navegador (suba o servidor local e teste o fluxo real, não só leia o código).
4. Nas sessões de Persistência, siga também o "Ao finalizar": escrever os testes (`*FaeTest.php`) cobrindo os critérios de aceite do documento de requisitos, fazer bump de versão **MINOR** (`config/app.php` + `.env`, convenção `BF_vMAJOR.MINOR.PATCH`), e fazer a revisão de segurança de rotas descrita no `CLAUDE.md` do projeto.
5. **Pare ao final de cada sessão** e me avise o que foi entregue antes de seguir para a próxima — não encadeie várias sessões sem checkpoint, mesmo que o contexto permita.

Regras gerais do projeto:
- Pode commitar localmente sem pedir permissão a cada vez; **nunca fazer `git push`** sem eu pedir explicitamente.
- Nunca rodar comando que apague ou modifique dado (local ou produção) sem confirmar comigo antes.
- Toda alteração de schema é uma migration nova, versionada nesta branch — nunca aplicar em outro banco de cliente.
- Se alguma pendência do cliente (seção 9 do documento de requisitos, tabela em `00-indice.md`) tiver sido respondida entre a criação deste cronograma e agora, me avise antes de seguir com o default documentado.

Comece lendo `docs/cronograma/00-indice.md` e depois `docs/cronograma/01-web-preparacao.md`, e me diga o que entendeu antes de começar a implementar.
