# D10 — Transversais e segurança

[← Índice](../00-indice.md) · Anterior: [D9](D09-relatorios-aprovacao-pdf.md) · Próximo: [D11 — Reteste, regressão e fechamento](D11-regressao-fechamento.md)

| | |
|---|---|
| **Objetivo** | Validar o que atravessa todos os módulos: permissão por URL direta, isolamento entre técnicos (IDOR), arquivos em `/midia`, gravação em relatório aprovado, XSS, filtros por usuário, auditoria, integridade de respostas, tema, celular, navegadores e download do app |
| **Por que agora** | Esses testes precisam de **toda** a massa criada (relatório aprovado, atendimentos de dois técnicos, respostas gravadas). Feitos antes, metade não teria o que atacar |
| **Duração** | ~7 h |
| **Perfis** | Todos |
| **Pré-condições** | D1 a D9 concluídos; DevTools (`F12`) aberto na aba **Network** nos casos marcados com 🔧 |

## Como reenviar uma requisição pelo DevTools (🔧)

Alguns casos testam o **servidor** sem passar pelos bloqueios da tela:

1. Faça a ação **permitida** pela tela, por exemplo salvar as Observações Gerais de um relatório que **não** está aprovado.
2. Na aba Network, clique com o botão direito na requisição › **Copy › Copy as fetch**.
3. Na aba **Console**, cole, troque o ID na URL pelo alvo do teste e tecle Enter.
4. Clique na nova requisição na aba Network e veja o **Status** (200, 403, 422…) e a resposta.

O token CSRF vai junto na cópia, então o teste vale para a sessão atual. **Grave o DevTools aberto** nesses casos: o código HTTP é a evidência.

## Vídeos

| Vídeo | Casos |
|---|---|
| `D10_CT-SEC-001-003_permissoes-url_...mp4` | Permissão por URL |
| `D10_CT-SEC-004-010_idor-midia-aprovado-xss_...mp4` | Ataques ao servidor (🔧) |
| `D10_CT-FIL-001-004_CT-AUD-001-004_filtros-auditoria_...mp4` | Filtros e auditoria |
| `D10_CT-INT-001_integridade-respostas_...mp4` | Integridade de respostas (R08) |
| `D10_CT-UXR-001-005_CT-APK-001_ux-celular-apk_...mp4` | Tema, celular, Edge, APK |

---

## Bloco 1 — Permissões e isolamento

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-SEC-001 | Tela sem "Ver" pela URL | Para cada perfil, abrir pela barra de endereço as telas que **não** estão no menu dele (use a matriz do D0). Mínimo: Técnico → `/clientes`, `/orcamentos`, `/usuarios`, `/configurador/perguntas`; Assistência → `/orcamentos`, `/indicadores-comerciais`; Vendedor → `/roteiros-viagem`, `/mapa-relacoes` | Todas dão a página 403 | Permissão | P1 |
| CT-SEC-002 | Cadastro sem "Editar" pela URL | Técnico → `/atendimentos/create`; Assistência → `/atendimentos/create`, `/atendimentos/{A1}/edit` | 403 | Permissão | P1 |
| CT-SEC-003 | Botão de cadastro sem "Editar" (**R06**) | Tirar temporariamente "Relatórios › Editar" do perfil Vendedor; como `qa.vendedor`, abrir Relatórios › Cadastrar e tentar salvar; **restaurar** | **Esperado:** botão Cadastrar oculto. **Hipótese R06:** o botão aparece e o salvar dá 403 | Permissão | P2 |
| CT-SEC-004 | 🔧 Gravar atendimento de outro técnico (**R03**) | Dar temporariamente "Atendimentos › Editar" ao Técnico. Como `qa.tecnico1`, salvar o próprio A1 e copiar a requisição `PUT /atendimentos/{A1}`; reenviar trocando para o A2 (do técnico 2); **restaurar** a matriz e o A2 | **Esperado:** 403. **Hipótese R03:** 200, e o A2 é alterado | Permissão | P1 |
| CT-SEC-005 | Arquivo de outro técnico via `/midia` (**R02**) | Como `qa.tecnico2`, abrir `/midia/atendimentos_relatorios/{id do relatório do A1}/assinaturas/responsavel.png` | **Esperado:** 403 ou 404. **Hipótese R02:** a imagem da assinatura do técnico 1 abre | Permissão | P1 |
| CT-SEC-006 | 🔧 Gravar em relatório aprovado (**R01**) | Como `qa.tecnico1`, salvar Observações Gerais num relatório **não aprovado** (crie um novo para o A1, se precisar); reenviar a requisição trocando o ID pelo do relatório **aprovado** do A1; recarregar o aprovado | **Esperado:** 403 ou 422 e nada muda. **Hipótese R01:** 200 e o texto do relatório aprovado muda. Bug **Crítico** se confirmar | Permissão | P1 |
| CT-SEC-007 | Relatório de outro técnico pela URL | Como `qa.tecnico2`, abrir `/atendimentos-relatorios/{id do relatório do A1}` | 403 "Você não tem acesso a este relatório." | Permissão | P1 |
| CT-SEC-008 | 🔧 Dados de relatório de outro técnico | Como `qa.tecnico2`, abrir `/atendimentos-relatorios/{id do A1}/respostas` e `/anexos` | 403 nos dois | Permissão | P1 |
| CT-SEC-009 | XSS em campos de texto | Gravar `<img src=x onerror=alert(1)>` em: nome de contato do cliente, comentário de orçamento, observações do atendimento, resposta de texto livre do relatório; reabrir cada tela, a lista e o PDF | Texto exibido **literalmente**; nenhum alerta do navegador | Permissão | P1 |
| CT-SEC-010 | Sessão perdida ao salvar | Com um formulário aberto, apagar os cookies do site (DevTools › Application › Cookies) e clicar em Salvar | Página "419 Page Expired" ou volta para o login; **nunca** erro 500. Anotar a experiência (observação de UX) | Borda | P2 |

## Bloco 2 — Filtros persistentes e auditoria

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-FIL-001 | Filtro é por usuário | `qa.comercial` filtra Clientes por UF `SP`; `qa.assistencia` abre Clientes | A Assistência vê a lista **sem** o filtro do Comercial | Positivo | P2 |
| CT-FIL-002 | Filtro sobrevive ao logout | `qa.comercial` sai, entra de novo, abre Clientes | Filtro UF `SP` continua aplicado | Positivo | P2 |
| CT-FIL-003 | "Limpar" grava o vazio | Em Clientes, clicar em Limpar; sair e voltar | Lista sem filtro e continua sem filtro ao voltar | Positivo | P2 |
| CT-FIL-004 | "Limpar" nas telas de mapa e indicadores (**R11**) | Aplicar um filtro em Indicadores, Mapa de Relações e Mapa de Demandas; clicar em Limpar em cada um | **Esperado:** filtros limpos. **Hipótese R11:** os filtros salvos voltam | Negativo | P2 |
| CT-AUD-001 | Acesso aos Logs de Auditoria | `qa.comercial` abre `/logs-auditoria`; `qa.admin` abre a mesma URL (não há item no menu) | Comercial: 403. Admin: a tela abre | Permissão | P1 |
| CT-AUD-002 | Registros gravados | Como `qa.admin`, procurar os registros da rodada | Existem: Atendimentos CRIAR/EDITAR, Relatorios CRIAR/APROVAR, PerfisAcesso ATUALIZAR, com dados antes/depois | Positivo | P2 |
| CT-AUD-003 | Opções do filtro de ação (**R18**) | Abrir o filtro "Ação" | **Hipótese R18:** oferece INATIVAR e CONCLUIR (nunca gravadas) e **não** oferece ATUALIZAR (gravada pelos Perfis de Acesso) | Borda | P3 |
| CT-AUD-004 | Lista de erros | Rolar até os últimos erros registrados | Lista dos 100 últimos erros (cruzar com os erros 500 encontrados na rodada) | Positivo | P3 |

## Bloco 3 — Integridade de respostas (Configurador sem versionamento)

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-INT-001 | Editar opções de pergunta já respondida (**R08**) | Anotar (print) as respostas atuais de `DEMO Equipamento operando?` no relatório aprovado do A1, no PDF e de `DEMO Prazo de instalação` no O1. Como `qa.admin`, editar **só o texto** de uma opção de cada pergunta (ex.: "Sim" → "Sim, operando") e salvar. Reabrir o relatório, o PDF e o O1 | **Esperado:** respostas antigas continuam aparecendo. **Hipótese R08:** a resposta some ou fica sem correspondência, porque as opções são recriadas com IDs novos. Bug **Alto** se confirmar (afeta relatório já aprovado) | Negativo | P1 |

## Bloco 4 — Usabilidade, celular, navegadores e APK

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-UXR-001 | Tema escuro | Alternar o tema e percorrer Dashboard, listas, formulário de Cliente, relatório (abas) e modais | Textos legíveis, sem campo "sumido" ou sem contraste | Positivo | P3 |
| CT-UXR-002 | Celular | No celular, na mesma rede, abrir `http://<IP>:8083`; logar como `qa.tecnico1`; abrir Atendimentos e o relatório do A1 | Layout sem rolagem horizontal; menu recolhe; abas e botões utilizáveis com o dedo. **Grave a tela do celular** com o gravador nativo do aparelho | Positivo | P2 |
| CT-UXR-003 | Microsoft Edge | Repetir o smoke do D0 no Edge | Mesmo comportamento do Chrome | Positivo | P3 |
| CT-UXR-004 | Menu recolhido e zoom do Windows | Escala do Windows em 125%; recolher o menu lateral; passar o mouse em Configurações | O submenu flutuante não sai da tela | Positivo | P3 |
| CT-UXR-005 | Mensagem de sucesso | Salvar um Cliente, um Orçamento e um Roteiro | Cada um mostra a mensagem de sucesso no topo | Positivo | P3 |
| CT-APK-001 | Download do app | Abrir `/apk/buildflow.apk` sem estar logado; depois `/apk/..%2F.env` | Se houver APK publicado em `storage/app/apks`, o download começa; se não houver, 404. O segundo endereço **sempre** dá 404 (não sai da pasta) | Permissão | P2 |

---

## Pontos de atenção

- Riscos verificados neste dia: **R01, R02, R03, R06, R08, R11, R18**.
- **Restaure** toda alteração temporária de matriz (CT-SEC-003, CT-SEC-004) no mesmo vídeo.
- Se R01, R02 ou R03 se confirmarem: abra como **Crítico** (exposição ou alteração de dado de outro usuário, ou de documento aprovado) e avise o responsável **no mesmo dia**. Não espere o relatório final.

---

## Registro de execução — D10

| ID | Status | Vídeo + tempo | Bug | Data / QA |
|---|---|---|---|---|
| CT-SEC-001 | | | | |
| CT-SEC-002 | | | | |
| CT-SEC-003 | | | | |
| CT-SEC-004 | | | | |
| CT-SEC-005 | | | | |
| CT-SEC-006 | | | | |
| CT-SEC-007 | | | | |
| CT-SEC-008 | | | | |
| CT-SEC-009 | | | | |
| CT-SEC-010 | | | | |
| CT-FIL-001 | | | | |
| CT-FIL-002 | | | | |
| CT-FIL-003 | | | | |
| CT-FIL-004 | | | | |
| CT-AUD-001 | | | | |
| CT-AUD-002 | | | | |
| CT-AUD-003 | | | | |
| CT-AUD-004 | | | | |
| CT-INT-001 | | | | |
| CT-UXR-001 | | | | |
| CT-UXR-002 | | | | |
| CT-UXR-003 | | | | |
| CT-UXR-004 | | | | |
| CT-UXR-005 | | | | |
| CT-APK-001 | | | | |
