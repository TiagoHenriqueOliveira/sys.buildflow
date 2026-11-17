# D0 — Preparação do ambiente e da rodada

[← Índice](00-indice.md) · Próximo: [D1 — Acesso e segurança](roteiros/D01-acesso-seguranca.md)

**Objetivo:** deixar o ambiente estável, o banco protegido, as ferramentas de gravação prontas e confirmar com um smoke que a build pode ser testada.
**Duração:** ~4 h
**Perfil:** Administrador (qualquer conta administradora já existente)

---

## 1. Ambiente

| Item | Valor esperado |
|---|---|
| URL | `https://sys-buildflow-fae.local` |
| Acesso pelo celular (D10) | `http://<IP-da-máquina>:8083` |
| Banco | `buildflow_fae` (MySQL local) |
| Versão no rodapé | `v1.14.0` |
| Navegador principal | Google Chrome atualizado, perfil exclusivo "QA" (sem extensões e sem senhas salvas) |
| Navegador secundário (D10) | Microsoft Edge |

**Atenção ao HTTPS:** o botão "Usar minha localização" do Cliente só funciona em conexão segura. Abra a URL com `https://` e confirme que o cadeado não mostra "Não seguro". Com certificado local não confiável, a geolocalização falha, e isso não é bug do sistema. Nesse caso, registre como impedimento de ambiente.

**Antes de começar, limpe o cache de configuração:**

```bash
php artisan config:clear
```

Esse comando roda na pasta `C:\Apache24\htdocs\sys-buildflow-fae`. Cache de configuração velho já foi causa de "Server Error" neste projeto.

---

## 2. Backup do banco (obrigatório)

A rodada cria e altera muitos registros em `buildflow_fae`, que também é o banco de desenvolvimento. Faça um dump **antes** do D1 e guarde fora do repositório:

```bash
mysqldump -u root -p --routines --single-transaction buildflow_fae > C:\backups\buildflow_fae_antes_qa_AAAAMMDD.sql
```

Para voltar ao estado anterior no fim da rodada, **só com o aval do responsável pelo ambiente**:

```bash
mysql -u root -p buildflow_fae < C:\backups\buildflow_fae_antes_qa_AAAAMMDD.sql
```

---

## 3. Suíte automatizada (critério de entrada)

```bash
php artisan test
```

- A suíte usa o banco `buildflow_test` (`.env.testing`), não o `buildflow_fae`. Pode rodar sem medo.
- **Baseline conhecida:** 6 falhas em `AtendimentoRelatorioTest`, que já existiam antes desta versão.
- **Qualquer outra falha bloqueia o início da rodada.** Abra bug com a saída do terminal.
- Registre no Registro de execução abaixo: total de testes, quantos passaram e quantos falharam.

---

## 4. Usuários QA

Os usuários são **criados no D1**, pela própria tela de Usuários (isso já é teste). Use sempre estes, nunca a conta pessoal de um desenvolvedor nem o usuário administrador master.

| Usuário | Perfil | Para que serve |
|---|---|---|
| `qa.admin@fae.local` | Administrador | Configurações, perfis, auditoria |
| `qa.tecnico1@fae.local` | Técnico | Dono dos atendimentos A1/A3/A4 e do relatório DEMO |
| `qa.tecnico2@fae.local` | Técnico | Dono do atendimento A2; testes de posse e IDOR |
| `qa.comercial@fae.local` | Comercial | CRM inteiro; vendedor responsável |
| `qa.assistencia@fae.local` | Assistência | Aprova relatórios; recebe a notificação de "liberado" |
| `qa.vendedor@fae.local` | Vendedor | Perfil com acesso parcial; papel "Atua como vendedor" |
| `qa.inativo@fae.local` | Técnico (inativo) | Login de usuário inativo |

**Senhas:** ficam no gerenciador de senhas da equipe, nunca neste repositório, em planilha ou no vídeo. Digite a senha com a gravação **pausada**.

---

## 5. Matriz de perfis desta rodada

A tabela `perfis_permissoes` **nasce vazia**: perfis não-administradores começam sem acesso a nada além do Dashboard. No D1 o QA configura a matriz abaixo **pela tela Perfis de Acesso**, o que já é parte do teste.

Esta matriz reproduz o acesso anterior à v1.14 (`PermissoesPadraoSeeder`). **Não rode o seeder:** ele substitui as permissões sem passar pela tela, e a tela é justamente o que se quer testar.

| Tela / papel | Técnico | Comercial | Assistência | Vendedor |
|---|---|---|---|---|
| Atendimentos | Ver | Ver | Ver | Ver |
| Relatórios de atendimento | Ver + Editar | Ver + Editar | Ver + Editar | Ver + Editar |
| Mapa de demandas | Ver | Ver | Ver | Ver |
| Clientes | — | Ver + Editar | Ver + Editar | Ver + Editar |
| Orçamentos | — | Ver + Editar | — | — |
| Roteiro de viagem | — | Ver + Editar | — | — |
| Mapa de relações | — | Ver | — | — |
| Indicadores comerciais | — | Ver | — | — |
| Configurações (todas) | — | — | — | — |
| Papel "Atua como vendedor" | — | ✔ | — | — |

O D1 altera essa matriz temporariamente em alguns casos (ex.: dar só "Ver" de Orçamentos ao Vendedor). Cada caso diz como restaurar.

---

## 6. Massa de dados DEMO

Todo registro criado na rodada começa com **`DEMO`** no nome. Assim fica fácil filtrar, achar no vídeo e limpar depois.

| Criado em | Registros | Usado em |
|---|---|---|
| D1 | Usuários QA (seção 4) e matriz de perfis (seção 5) | Todos os dias |
| D2 | Segmentos `DEMO Sucroenergético` e `DEMO Alimentos`; classificações `DEMO A` e `DEMO B`; ocorrência `DEMO Chuva forte` | D4, D6 |
| D3 | Perguntas de Assistência (texto com anexo, escolha única, múltipla escolha, repetível, sessão `DEMO Inspeção Elétrica`); perguntas Comerciais; modelos `DEMO Modelo Assistência` e `DEMO Modelo Comercial`; natureza `DEMO Manutenção corretiva`; tipo de orçamento `DEMO Caldeira` | D5, D7, D8 |
| D4 | Clientes C1 a C4 (detalhe no roteiro D4) | D5 a D9 |
| D5 | Orçamentos O1 a O5 com resultados diferentes; roteiro `DEMO Roteiro Norte` | D6 |
| D7 | Atendimentos A1 a A4 (Técnico 1 e Técnico 2, status variados) | D8, D9, D10 |
| D8 | Relatório DEMO do A1 | D9, D10 |

**Documentos fictícios:**
- CNPJ e CPF: use um gerador de documentos para testes (dígito verificador válido) e anote os números usados no Registro de execução.
- Nunca use dados de clientes reais, nem do dump de produção.

---

## 7. Arquivos de teste

Crie a pasta `C:\QA-arquivos\` fora do repositório com:

| Arquivo | Características | Usado em |
|---|---|---|
| `foto_ok.jpg` | Foto comum, < 2 MB | D3, D7, D8 |
| `foto_ok.png`, `foto.webp`, `foto.gif` | Formatos aceitos em fotos | D8 |
| `foto_15mb.jpg` | Entre 10 MB e 20 MB | D7 (aceita), D8 (rejeita) |
| `arquivo_25mb.pdf` | Maior que 20 MB | D7 (rejeita) |
| `documento.pdf`, `planilha.xlsx`, `video_curto.mp4` | Tipos aceitos nos anexos do Atendimento | D7 |
| `compactado.zip` | Tipo não aceito | D7, D8 |
| `falso.jpg` | Arquivo de texto renomeado para `.jpg` | D8 |

---

## 8. Ferramentas de gravação

Instale e configure tudo conforme o [02-padrao-gravacao.md](02-padrao-gravacao.md): OBS Studio, PowerToys (Realce do Mouse), Clipchamp, a pasta compartilhada da rodada e o Modo Não Perturbe do Windows.

---

## 9. Smoke test

**Vídeo:** `D00_CT-SMK-001-008_smoke_v1.14.0_<RESULTADO>.mp4`

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-SMK-001 | Login do administrador | Abrir a URL, entrar com uma conta Administrador | Cai no Dashboard com "Bem-vindo(a) ao Buildflow" | Positivo | P1 |
| CT-SMK-002 | Versão da build | Olhar o rodapé | Mostra `v1.14.0` | Positivo | P1 |
| CT-SMK-003 | Menus do CRM abrem | Abrir Clientes, Orçamentos, Roteiro de Viagem, Mapa de Relações, Indicadores Comerciais | Todas as telas carregam, sem erro 500 e sem página em branco | Positivo | P1 |
| CT-SMK-004 | Menus da Assistência abrem | Abrir Atendimentos, Relatórios, Mapa de Demandas | Carregam sem erro | Positivo | P1 |
| CT-SMK-005 | Menus de Configurações abrem | Abrir cada item de Configurações: Tipos de Orçamento, Configurador › Perguntas, Configurador › Modelos, Naturezas, Classificações, Segmentos, Ocorrências, Usuários, Perfis de Acesso | Carregam sem erro | Positivo | P1 |
| CT-SMK-006 | Sino de notificações | Clicar no sino da barra superior | Abre a lista (pode estar vazia), sem erro no console (F12) | Positivo | P1 |
| CT-SMK-007 | Mapas carregam o fundo | Em Mapa de Relações e Mapa de Demandas, aguardar o mapa | Mapa do OpenStreetMap aparece (mesmo sem pontos) | Positivo | P1 |
| CT-SMK-008 | Logout | Menu do usuário › Sair › confirmar | Volta para o login; o botão "voltar" do navegador não reabre o painel | Positivo | P1 |

---

## Registro de execução — D0

| Item | Resultado | Evidência | Data / QA |
|---|---|---|---|
| `config:clear` executado | | | |
| Backup do banco (nome do arquivo) | | | |
| `php artisan test` (total / passou / falhou) | | print do terminal | |
| Certificado HTTPS confiável | | | |
| Ferramentas de gravação prontas | | | |

| ID | Status | Vídeo + tempo | Bug | Data / QA |
|---|---|---|---|---|
| CT-SMK-001 | | | | |
| CT-SMK-002 | | | | |
| CT-SMK-003 | | | | |
| CT-SMK-004 | | | | |
| CT-SMK-005 | | | | |
| CT-SMK-006 | | | | |
| CT-SMK-007 | | | | |
| CT-SMK-008 | | | | |
