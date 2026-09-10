# Prompt de início — Sessão Android (FAÉ Bioenergia)

Cole isto como primeira mensagem de uma nova sessão do Claude Code, com o diretório de trabalho em `C:\Users\tiago\StudioProjects\app_buildflow_fae` (branch `feature/fae`, Flutter).

---

Você vai executar o cronograma de desenvolvimento do projeto FAÉ Bioenergia (Buildflow + CRM Comercial), lado Android/mobile, neste repositório (`app_buildflow_fae`, branch `feature/fae`).

**Antes de começar**, confirme que todas as sessões Web (`01` a `09`, em `C:\Apache24\htdocs\sys-buildflow-fae\docs\cronograma\`) já foram concluídas — o app mobile depende das APIs e do módulo CRM que nascem no Web, em especial NC02/Configurador e CRM01/05/06/07, que o app reaproveita em BF05 e CRM09. Se não tiver certeza, verifique o histórico de commits/versão da branch `feature/fae` em `sys-buildflow-fae` antes de prosseguir, ou me pergunte.

O cronograma completo (Web+Android) está documentado em `C:\Apache24\htdocs\sys-buildflow-fae\docs\cronograma\00-indice.md` — leia esse arquivo primeiro para o panorama geral, mesmo estando no repositório do app. Depois comece pela sessão `docs/cronograma/10-android-preparacao.md` (mesmo caminho, repositório do Web — é onde ficam todos os arquivos do cronograma) e siga em ordem numérica (10 → 18), **uma sessão de cada vez**.

Para cada sessão:
1. Leia o arquivo `.md` correspondente por completo antes de tocar em código.
2. Implemente exatamente o que está descrito em "Entregáveis desta sessão". Sessões de "Telas" não incluem sincronização offline nem regra de negócio fina — isso fica para a sessão de "Persistência/Sync" correspondente.
3. Ao terminar, confira os itens de "Critérios de aceite a verificar" rodando o app de verdade (emulador ou aparelho), testando também o cenário offline quando aplicável.
4. Nas sessões de Persistência/Sync, siga o "Ao finalizar": testes, bump de build number (`pubspec.yaml`, formato `X.Y.Z+N`, convenção `BF_vMAJOR.MINOR.PATCH`).
5. **Pare ao final de cada sessão** e me avise o que foi entregue antes de seguir para a próxima.

Regras gerais do projeto:
- Pode commitar localmente sem pedir permissão a cada vez; **nunca fazer `git push`** sem eu pedir explicitamente.
- Nunca rodar comando que apague ou modifique dado (local ou produção) sem confirmar comigo antes.
- A sessão `10-android-preparacao.md` pede pacotes novos (`geolocator`, `google_maps_flutter` ou equivalente) — confirme comigo a escolha exata do pacote de mapa antes de fixar a dependência, e verifique se já existe uma chave de API do Google Maps configurada para o ambiente FAÉ.

Comece lendo `docs/cronograma/00-indice.md` e depois `docs/cronograma/10-android-preparacao.md`, e me diga o que entendeu antes de começar a implementar.
