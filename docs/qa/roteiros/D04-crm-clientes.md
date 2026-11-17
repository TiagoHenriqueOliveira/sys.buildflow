# D4 — CRM: Clientes

[← Índice](../00-indice.md) · Anterior: [D3](D03-configurador.md) · Próximo: [D5 — Orçamentos e Roteiros](D05-crm-orcamentos-roteiros.md)

| | |
|---|---|
| **Objetivo** | Validar o cadastro de clientes (NC01/BF01): abas, validações, listas repetíveis, geolocalização, histórico, filtros e acesso por perfil. Deixar prontos os clientes C1 a C4 |
| **Por que agora** | Cliente é obrigatório em Orçamento, Roteiro e Atendimento. Coordenadas e vendedor do cliente alimentam os mapas e o alerta de recontato |
| **Duração** | ~6 h |
| **Perfis** | `qa.comercial` (principal), `qa.tecnico1`, `qa.assistencia`, `qa.vendedor` |
| **Pré-condições** | D2 e D3 concluídos; Chrome com **DevTools › Sensores** disponível (para simular a localização) |

## Simular a localização (para os mapas terem pontos distintos)

O cadastro **não** tem mais campo de latitude/longitude nem mapa para escolher o ponto: só o botão **"Usar minha localização"** (GPS do navegador). Para cada cliente ficar num lugar diferente:

1. `F12` › menu ⋮ › **More tools › Sensors**.
2. Em **Location**, escolha uma cidade ou "Other…" e informe latitude e longitude.
3. Clique em "Usar minha localização" no cliente e permita o acesso.

| Cliente | Nome | Cidade/UF | Localização simulada | Vendedor | Segmento | Classificação | Outros |
|---|---|---|---|---|---|---|---|
| C1 | `DEMO Usina Alfa` | Ribeirão Preto / SP | -21.1775, -47.8103 | qa.comercial | DEMO Sucroenergético | DEMO A | Recontato 1 dia; caso de sucesso |
| C2 | `DEMO Laticínio Beta` | Belo Horizonte / MG | -19.9167, -43.9345 | qa.comercial | DEMO Alimentos | DEMO B | — |
| C3 | `DEMO Cerâmica Gama` | Curitiba / PR | **sem localização** | — | — | — | — |
| C4 | `DEMO Destilaria Delta` | Goiânia / GO | -16.6869, -49.2648 | qa.comercial | DEMO Sucroenergético | DEMO A | Fica **inativo** |

Anote os CNPJs fictícios usados (gerador com dígito verificador válido) no Registro de execução.

## Vídeos

| Vídeo | Casos |
|---|---|
| `D04_CT-CLI-001-007_clientes-cadastro-validacoes_...mp4` | Cadastro do C1 e validações dos Dados |
| `D04_CT-CLI-008-017_clientes-abas_...mp4` | Contatos, Histórico/Equipamentos, Geolocalização, edição, C2–C4 |
| `D04_CT-CLI-018-021_clientes-filtros-permissoes_...mp4` | Filtros, segmento inativo, permissões |

---

## Bloco 1 — Cadastro e validações (aba Dados)

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-CLI-001 | Cadastro completo | Clientes › Cadastrar › C1 com CNPJ **com máscara**, Cidade, UF, Vendedor responsável, Telefone, E-mail, Segmento, Classificação, "Alerta de recontato (dias)" = 1 › Salvar | Volta para a **lista** com mensagem de sucesso; C1 aparece ativo | Positivo | P1 |
| CT-CLI-002 | Obrigatórios | Salvar com Nome, CNPJ, Cidade e UF vazios | Erro em cada um dos 4 campos; nada é criado | Negativo | P1 |
| CT-CLI-003 | CNPJ duplicado | Cadastrar outro cliente com o CNPJ do C1, digitado **sem** máscara | Erro de CNPJ já cadastrado | Negativo | P1 |
| CT-CLI-004 | CNPJ incompleto | CNPJ com 13 dígitos | Erro de tamanho (14 dígitos) | Negativo | P2 |
| CT-CLI-005 | CNPJ com dígito verificador inválido (**R19**) | CNPJ `11.222.333/0001-00` (14 dígitos, DV errado) | **Esperado:** recusar. **Hipótese R19:** aceita. Registrar | Negativo | P2 |
| CT-CLI-006 | UF (**R19**) | 1) UF `sp` 2) UF `XX` | 1) Salva como `SP` 2) **Esperado:** recusar UF inexistente. **Hipótese:** aceita | Borda | P3 |
| CT-CLI-007 | Telefone e e-mail | Telefone com 9 dígitos; e-mail `abc@` | Erros de formato; 10 ou 11 dígitos são aceitos | Negativo | P3 |

## Bloco 2 — Abas Contatos, Geolocalização e Histórico

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-CLI-008 | Contatos | Editar C1 › Contatos › "Adicionar contato" 2× (um Técnico, um Comercial) › adicionar uma 3ª linha vazia › Salvar | Erro **"Informe o nome do contato."**; removendo a linha vazia, salva os 2 | Negativo | P2 |
| CT-CLI-009 | Equipamentos | Aba Histórico › "Adicionar equipamento" 2× com descrição; uma 3ª linha vazia › Salvar | Linha vazia dá erro de descrição obrigatória; sem ela, salva os 2 | Negativo | P2 |
| CT-CLI-010 | Edição fica na tela e na aba | Editar C1 estando na aba Contatos › Salvar | Continua na **edição**, na aba **Contatos**, com mensagem de sucesso (cadastro novo volta para a lista; edição não) | Positivo | P2 |
| CT-CLI-011 | Usar minha localização | Simular Ribeirão Preto nos Sensores › aba Geolocalização › "Usar minha localização" › Permitir › Salvar | Mensagem de localização capturada; salvar grava (conferido no Mapa de Relações, D6) | Positivo | P2 |
| CT-CLI-012 | Localização negada | No cadeado do navegador, bloquear "Localização" para o site › clicar no botão | Mensagem clara de **permissão negada**, diferente de "indisponível" | Negativo | P3 |
| CT-CLI-013 | Link do Google Maps | Colar um link do Maps no campo "Link do Google Maps" › clicar no botão do mapa ao lado; depois apagar o link | Abre o link em nova aba; com o campo vazio, o botão fica desabilitado | Positivo | P3 |
| CT-CLI-014 | Outras localizações | "Adicionar localização" com descrição e link; outra linha sem descrição › Salvar | A linha com descrição é salva; a linha sem descrição é **descartada sem erro** | Borda | P3 |
| CT-CLI-015 | Caso de sucesso e histórico vazio | Aba Histórico de C1: marcar "Caso de sucesso" e preencher a descrição | Salva; a área de histórico mostra "Nenhum atendimento, relatório ou orçamento registrado para este cliente ainda." | Positivo | P3 |
| CT-CLI-016 | Cadastrar C2 e C3 | C2 com localização de Belo Horizonte; C3 sem localização e sem vendedor | Ambos salvos | Positivo | P1 |
| CT-CLI-017 | Cadastrar C4 e inativar | C4 com localização de Goiânia; depois editar › Inativo › Salvar | C4 aparece **em vermelho** na lista; não aparece no autocomplete de cliente do Orçamento (conferir no D5) | Positivo | P2 |

## Bloco 3 — Filtros, segmento inativo e permissões

| ID | O que validar | Passos | Resultado esperado | Tipo | Prio |
|---|---|---|---|---|---|
| CT-CLI-018 | Filtros | Filtrar por nome, CNPJ só com dígitos, **CNPJ com máscara**, cidade, UF, segmento, classificação, status; sair e voltar | Cada filtro funciona; persiste ao voltar. **Hipótese:** CNPJ digitado com máscara não encontra (registrar como bug Baixo se confirmado) | Positivo | P2 |
| CT-CLI-019 | Segmento já usado e depois inativado (**R16**) | Como `qa.admin`, inativar `DEMO Alimentos` (usado pelo C2); como `qa.comercial`, abrir C2 › Salvar sem mexer; depois **reativar** o segmento | O campo mostra "DEMO Alimentos (não cadastrado)" e o valor é mantido ao salvar. **Hipótese R16:** o valor é trocado sem aviso | Borda | P3 |
| CT-CLI-020 | Acesso por perfil | 1) `qa.tecnico1`: procurar Clientes no menu e abrir `/clientes` 2) `qa.assistencia` e `qa.vendedor`: abrir e editar C3 | 1) Sem item no menu; URL dá 403 2) Ambos abrem e salvam | Permissão | P1 |
| CT-CLI-021 | Classificação inativa | Abrir o select de Classificação | `DEMO Inativa` não aparece | Positivo | P3 |

---

## Pontos de atenção

- **R19** (CT-CLI-005/006): validações fracas de CNPJ e UF.
- **R16** (CT-CLI-019): valor legado em select.
- O histórico do cliente (atendimentos e orçamentos) é revisitado depois que eles existirem (CT-ATE-019, D7).
- Contatos, equipamentos e localizações são **apagados e recriados** a cada salvamento. Se notar ids ou ordem mudando, é esperado.

---

## Registro de execução — D4

CNPJs usados: C1 `__________` · C2 `__________` · C3 `__________` · C4 `__________`

| ID | Status | Vídeo + tempo | Bug | Data / QA |
|---|---|---|---|---|
| CT-CLI-001 | | | | |
| CT-CLI-002 | | | | |
| CT-CLI-003 | | | | |
| CT-CLI-004 | | | | |
| CT-CLI-005 | | | | |
| CT-CLI-006 | | | | |
| CT-CLI-007 | | | | |
| CT-CLI-008 | | | | |
| CT-CLI-009 | | | | |
| CT-CLI-010 | | | | |
| CT-CLI-011 | | | | |
| CT-CLI-012 | | | | |
| CT-CLI-013 | | | | |
| CT-CLI-014 | | | | |
| CT-CLI-015 | | | | |
| CT-CLI-016 | | | | |
| CT-CLI-017 | | | | |
| CT-CLI-018 | | | | |
| CT-CLI-019 | | | | |
| CT-CLI-020 | | | | |
| CT-CLI-021 | | | | |
