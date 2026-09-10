# Sessão Android — Bloco 2 (Configurador) · Etapa 1: Telas

Ver [00-indice.md](00-indice.md). Pré-requisito: [12-android-nucleo-persistencia.md](12-android-nucleo-persistencia.md).

## Escopo: BF05, NC03, NC04 (mobile)

## Estado atual (ponto de partida)

- `lib/features/relatorios/relatorio_form_screen.dart` + `lib/features/relatorios/sections/*` implementam hoje um formulário **fixo** herdado do MCL (seções: clima, descricao, horarios, info_adicionais, ocorrencias, pecas, servicos, status, assinaturas, anexos) — não consome nenhum banco de perguntas dinâmico.
- Esse é o **maior item do app** (BF05, complexidade Alta): é preciso reescrever a montagem do formulário para consumir a estrutura de perguntas/modelo/revisão do Configurador (NC02, já pronto no Web desde a Sessão 05) em vez das seções fixas atuais.

## Entregáveis

- **BF05**: nova tela/engine de formulário dinâmico que monta os campos a partir das perguntas da revisão de modelo vinculada ao tipo de atendimento em uso — respeitando o componente de cada tipo de resposta (checkbox/radio/texto) e o anexo de imagem por pergunta quando habilitado.
- Avaliar se as seções fixas atuais (`sections/*`) são descontinuadas ou mantidas como um "modelo legado" — decisão técnica a tomar nesta sessão, documentando a escolha.
- **NC03**: campo de anexo de imagem por pergunta + comentário, no preenchimento mobile.
- **NC04**: área de anexos gerais do relatório no app (separada dos anexos por pergunta).

## Não faz parte desta sessão

Cache local do banco de perguntas/modelos e fila de sincronização — isso é a Sessão 14. Aqui a tela pode consumir a API `fae/v1` diretamente para montar o formulário.

## Critérios de aceite a verificar

- Preencher um relatório usando um modelo com perguntas dos 3 tipos, online.
- Formulário reflete exatamente as perguntas do modelo vinculado ao tipo de atendimento em uso.
- Campo de anexo aparece só nas perguntas habilitadas, com campo de comentário.

## Próxima sessão

[14-android-configurador-persistencia.md](14-android-configurador-persistencia.md)
