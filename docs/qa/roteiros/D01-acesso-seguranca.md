# D1 — Acesso, usuários e Perfis de Acesso

[← Índice](../00-indice.md) · Anterior: [D0 — Preparação](../01-preparacao.md) · Próximo: [D2 — Cadastros de apoio](D02-cadastros-apoio.md)

| | |
|---|---|
| **Objetivo** | Validar login, sessão, cadastro de usuários e a nova matriz de **Perfis de Acesso** (BF_v1.14.0), e deixar prontos os usuários QA e as permissões de toda a rodada |
| **Por que primeiro** | Todas as telas dependem de permissão. A tabela de perfis **nasce vazia**: sem configurá-la, nenhum perfil além do Administrador consegue testar nada |
| **Duração** | ~6 h |
| **Perfis** | Administrador, Técnico, Comercial, Assistência, Vendedor |
| **Pré-condições** | D0 concluído; uma conta Administrador existente; janela anônima do Chrome disponível (para dois usuários ao mesmo tempo) |

## Ordem de execução e vídeos

| Ordem | Vídeo | Casos |
|---|---|---|
| 1 | `D01_CT-LOG-001-004_login_...mp4` | Login básico com o administrador existente |
| 2 | `D01_CT-USR-001-008_usuarios_...mp4` | Cria os usuários QA |
| 3 | `D01_CT-PRF-001-012_perfis-menu-403_...mp4` | Configura a matriz e valida menu e 403 |
| 4 | `D01_CT-LOG-005-008_CT-CTA_CT-USR-009-011_sessao-conta_...mp4` | Inativo, limite de tentativas, Minha conta, regras de edição de usuário |

---

## Bloco 1 — Login

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-LOG-001 | Login válido | 1) Abrir a URL 2) Informar e-mail e senha do Administrador 3) Entrar | Redireciona para o Dashboard; nome do usuário na barra superior | Positivo | P1 |
| CT-LOG-002 | Senha errada | Informar um e-mail válido com a senha errada | Mensagem **"E-mail ou senha inválidos."**; continua no login | Negativo | P1 |
| CT-LOG-003 | E-mail inexistente | Informar `naoexiste@fae.local` | **Mesma** mensagem do CT-LOG-002 (não revela se o e-mail existe) | Negativo | P2 |
| CT-LOG-004 | Campos vazios e e-mail mal formatado | 1) Entrar com tudo vazio 2) Entrar com `abc` no e-mail | Mensagens de campo obrigatório e de formato de e-mail; nada é enviado | Negativo | P2 |

## Bloco 2 — Usuários (Configurações › Usuários)

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-USR-001 | Criar Administrador QA | Cadastrar `qa.admin@fae.local`, perfil Administrador, senha e confirmação iguais | Usuário aparece na lista como ativo | Positivo | P1 |
| CT-USR-002 | Criar os demais usuários QA | Cadastrar Técnico 1, Técnico 2, Comercial, Assistência, Vendedor e Inativo ([lista no D0](../01-preparacao.md#4-usuários-qa)) | Todos aparecem na lista com o perfil certo | Positivo | P1 |
| CT-USR-003 | E-mail duplicado | Cadastrar outro usuário com `qa.comercial@fae.local` | Erro de e-mail já cadastrado; nada é criado | Negativo | P1 |
| CT-USR-004 | Senha curta e confirmação divergente | 1) Senha de 5 caracteres 2) Senha ≠ confirmação | Erros de mínimo de 6 caracteres e de confirmação | Negativo | P2 |
| CT-USR-005 | Botão "Sugerir senha" | Clicar em "Sugerir senha" no modal | Senha de 10 caracteres preenchida também na confirmação | Positivo | P3 |
| CT-USR-006 | Editar sem trocar a senha | Editar o nome do Técnico 2 deixando a senha vazia e salvar; depois logar com a senha antiga | Nome alterado; senha antiga continua valendo | Positivo | P2 |
| CT-USR-007 | Inativar usuário | Editar `qa.inativo`, desligar "Ativo" e salvar | Usuário fica inativo na lista | Positivo | P1 |
| CT-USR-008 | Usuário master oculto | Procurar o administrador master (conta protegida) na lista e no filtro de e-mail | Não aparece para ninguém | Permissão | P2 |

## Bloco 3 — Perfis de Acesso, menu e página 403

> Se a tabela de perfis do banco local **não** estiver vazia no início, registre um print do estado encontrado no CT-PRF-001 e siga a partir do CT-PRF-002.

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-PRF-001 | Estado inicial (perfis vazios) | Na janela anônima, logar como `qa.tecnico1`; tentar abrir `/atendimentos` pela barra de endereço | Menu só com **Dashboard**; a URL abre a página **"Você não tem acesso a esta página"** | Permissão | P1 |
| CT-PRF-002 | Configurar a matriz da rodada | Como `qa.admin`, abrir Configurações › Perfis de Acesso; marcar a [matriz do D0](../01-preparacao.md#5-matriz-de-perfis-desta-rodada); Salvar | Aviso **"Perfis de acesso atualizados."**; ao recarregar, as marcações continuam | Positivo | P1 |
| CT-PRF-003 | Editar implica Ver | Marcar "Editar" de uma tela com "Ver" desmarcado; depois desmarcar "Ver" | Marcar Editar marca Ver junto; desmarcar Ver desmarca Editar | Positivo | P2 |
| CT-PRF-004 | Telas só de leitura | Olhar Mapa de demandas, Mapa de relações e Indicadores na matriz | Coluna Editar mostra "—" | Positivo | P3 |
| CT-PRF-005 | Menu do Técnico | Logar como `qa.tecnico1` | Menu: Dashboard, Atendimentos, Relatórios, Mapa de Demandas; **sem** Configurações e sem CRM | Permissão | P1 |
| CT-PRF-006 | Menu do Comercial | Logar como `qa.comercial` | Menu do Técnico + Clientes, Orçamentos, Roteiro de Viagem, Mapa de Relações, Indicadores Comerciais | Permissão | P1 |
| CT-PRF-007 | Menu da Assistência e do Vendedor | Logar com cada um | Menu do Técnico + Clientes; sem Orçamentos, Roteiros, Mapas comerciais e Indicadores | Permissão | P1 |
| CT-PRF-008 | Perfis de Acesso só para Administrador | Como `qa.comercial`, abrir `/perfis-acesso` pela URL | Item não aparece no menu; a URL dá a página 403 | Permissão | P1 |
| CT-PRF-009 | Comportamento da página 403 | Em qualquer 403, observar a página por 15 s; repetir clicando no botão | Título "Você não tem acesso a esta página"; contagem regressiva de 15 s até o Dashboard; o botão "Ir para o Dashboard agora" redireciona na hora | Positivo | P1 |
| CT-PRF-010 | "Ver" sem "Editar" | Dar ao perfil Vendedor só **Orçamentos › Ver**; logar como `qa.vendedor`; abrir Orçamentos e depois `/orcamentos/create` | Lista abre **sem** botões Cadastrar/Editar; a URL de cadastro dá 403. **Restaurar:** remover Orçamentos do Vendedor | Permissão | P1 |
| CT-PRF-011 | Papel "Atua como vendedor" | Marcar o papel no perfil Vendedor; como `qa.comercial`, abrir Clientes › Cadastrar › "Vendedor responsável"; depois desmarcar o papel | `qa.vendedor` aparece no combo só enquanto o papel está marcado; o Administrador não aparece por padrão | Positivo | P2 |
| CT-PRF-012 | Mudança vale na hora | Com `qa.tecnico1` logado na janela anônima, o admin remove "Mapa de demandas" do Técnico; o técnico recarrega a página. Depois **restaurar** | O item some do menu no recarregamento, sem novo login; a URL dá 403 | Permissão | P2 |

## Bloco 4 — Sessão, Minha conta e regras de edição de usuário

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-LOG-005 | Usuário inativo não entra | Logar como `qa.inativo` com a senha correta | **"E-mail ou senha inválidos."** | Negativo | P1 |
| CT-LOG-006 | Limite de tentativas | Errar a senha do mesmo e-mail 6 vezes em menos de 1 minuto; esperar 1 minuto e tentar com a senha certa | A partir da 6ª tentativa o login é recusado por excesso de tentativas (página padrão "429 Too Many Requests", sem layout do sistema; anotar como observação de UX); após 1 minuto entra normalmente | Negativo | P2 |
| CT-LOG-007 | Sessão e redirecionamentos | 1) Logado, abrir `/` 2) Fazer logout e abrir `/dashboard` | 1) Vai para o Dashboard 2) Vai para o login | Positivo | P1 |
| CT-LOG-008 | Recuperação de senha (observação) | Procurar "Esqueci minha senha" no login | Não existe. Registrar como **observação de UX**, não como bug | Borda | P3 |
| CT-CTA-001 | Alterar o próprio nome | Menu do usuário › Minha conta › alterar o nome › Salvar | Nome novo na barra superior | Positivo | P2 |
| CT-CTA-002 | Perfil não editável | Em Minha conta, observar o campo Perfil | Somente leitura | Permissão | P2 |
| CT-CTA-003 | Trocar a própria senha | Informar nova senha (≥ 6) e confirmação; sair e entrar com a nova | Login com a nova senha funciona; a antiga não | Positivo | P1 |
| CT-CTA-004 | Confirmação divergente | Nova senha ≠ confirmação | Erro de confirmação; senha não muda | Negativo | P2 |
| CT-CTA-005 | E-mail de outro usuário | Trocar o próprio e-mail para `qa.comercial@fae.local` | Erro de e-mail já em uso | Negativo | P2 |
| CT-USR-009 | Desativar usuário com sessão aberta (**R14**) | Na janela anônima, `qa.vendedor` logado. O admin desativa `qa.vendedor`. O vendedor navega para outra tela. **Reativar** depois | **Esperado:** sessão encerrada ou bloqueada. **Hipótese R14:** continua navegando. Registrar o que acontecer | Permissão | P1 |
| CT-USR-010 | Gestor de usuários que não é Administrador | Dar ao perfil Assistência **Usuários › Ver + Editar**; logar como `qa.assistencia`; abrir o cadastro de usuário; tentar editar `qa.admin`. **Restaurar** depois | O combo de perfil **não** oferece Administrador; o lápis de `qa.admin` não aparece; editar pela URL dá 403 "Somente administradores podem alterar um usuário Administrador." | Permissão | P1 |
| CT-USR-011 | Filtros da lista | Filtrar por parte do nome e por parte do e-mail; sair e voltar à tela | Filtro "contém" funciona; ao voltar, o filtro continua aplicado (filtro persistente) | Positivo | P3 |

---

## Pontos de atenção

- **R14** (CT-USR-009): sessão de usuário desativado.
- O login só confere se o usuário está ativo no momento do login. Anote no vídeo quanto tempo a sessão continua válida.
- Toda alteração temporária na matriz (CT-PRF-010, CT-PRF-012, CT-USR-010) tem que ser **restaurada** no mesmo vídeo. Os próximos dias assumem a matriz do D0.

---

## Registro de execução — D1

| ID | Status | Vídeo + tempo | Bug | Data / QA |
|---|---|---|---|---|
| CT-LOG-001 | | | | |
| CT-LOG-002 | | | | |
| CT-LOG-003 | | | | |
| CT-LOG-004 | | | | |
| CT-USR-001 | | | | |
| CT-USR-002 | | | | |
| CT-USR-003 | | | | |
| CT-USR-004 | | | | |
| CT-USR-005 | | | | |
| CT-USR-006 | | | | |
| CT-USR-007 | | | | |
| CT-USR-008 | | | | |
| CT-PRF-001 | | | | |
| CT-PRF-002 | | | | |
| CT-PRF-003 | | | | |
| CT-PRF-004 | | | | |
| CT-PRF-005 | | | | |
| CT-PRF-006 | | | | |
| CT-PRF-007 | | | | |
| CT-PRF-008 | | | | |
| CT-PRF-009 | | | | |
| CT-PRF-010 | | | | |
| CT-PRF-011 | | | | |
| CT-PRF-012 | | | | |
| CT-LOG-005 | | | | |
| CT-LOG-006 | | | | |
| CT-LOG-007 | | | | |
| CT-LOG-008 | | | | |
| CT-CTA-001 | | | | |
| CT-CTA-002 | | | | |
| CT-CTA-003 | | | | |
| CT-CTA-004 | | | | |
| CT-CTA-005 | | | | |
| CT-USR-009 | | | | |
| CT-USR-010 | | | | |
| CT-USR-011 | | | | |
