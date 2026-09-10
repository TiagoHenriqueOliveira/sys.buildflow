# Sessão Web — Bloco 2 (Configurador) · Etapa 1: Telas

Ver [00-indice.md](00-indice.md). Pré-requisito: [03-web-nucleo-persistencia.md](03-web-nucleo-persistencia.md).

## Escopo: NC02, NC03, NC04, BF04

## Estado atual (ponto de partida)

- Nenhum código de Configurador existe. `modelos_relatorios` (`2026_08_20_090002`) é uma tabela de **flags** (booleanos indicando quais seções aparecem no relatório) — não confundir com o "modelo" de NC02 (banco de perguntas reutilizável). Não reaproveitar essa tabela para NC02; são conceitos diferentes que coexistem.
- Infra genérica de anexos já existe: `atendimentos_relatorios_anexos/fotos/videos`, controller `AtendimentosRelatoriosController` (uploadAnexos/getAnexos/destroyAnexo), view `atendimentos-relatorios/tabs/anexos.blade.php` — serve de referência de padrão de upload, mas NC03/NC04 precisam de estrutura própria (anexo por pergunta vs. anexo geral são conceitos distintos no documento).

## Entregáveis desta sessão

### NC02 — Configurador (Alta complexidade, maior item deste bloco)
- Tela de **cadastro de pergunta**: texto da pergunta, tipo de resposta (Múltipla escolha / Escolha única / Texto livre — select único), toggle "permite anexo de imagem", lista repetível de opções de resposta (só visível quando tipo é múltipla/única escolha).
- Tela de **cadastro de modelo**: nome, setor (Comercial/Assistência), seleção múltipla de perguntas já cadastradas, vínculo de uso (tipo de atendimento ou tipo de sistema de orçamento — a lista de opções depende do setor escolhido).
- Bloquear/orientar na UI a criação de modelo sem nenhuma pergunta cadastrada.
- **Não implementar ainda**: lógica de revisão versionada (nova revisão a cada alteração) — isso é Etapa 2. Nesta etapa a tela de edição de modelo pode simplesmente sobrescrever.

### NC03 — anexo de imagem por pergunta
- No preenchimento (tela de relatório), exibir campo de anexo de imagem só nas perguntas com o toggle habilitado, com campo de comentário associado a cada foto.

### NC04 — anexos gerais do relatório
- Área própria de anexos do relatório, independente de pergunta, **sem** campo de comentário (diferente de NC03).

### BF04
- Tela de vínculo entre tipo de atendimento (já existente em `tipos_atendimentos`) e modelo cadastrado no Configurador (só modelos com setor "Assistência").

## Critérios de aceite a verificar nesta etapa

- Cadastrar pergunta de cada um dos 3 tipos e ver o componente certo (checkbox/radio/texto) no preenchimento.
- Tentar montar modelo sem pergunta cadastrada → sistema orienta/bloqueia.
- Montar modelo, atribuir setor, vincular a um tipo de atendimento.
- Mesma pergunta reaproveitada em mais de um modelo, sem duplicar cadastro.
- Campo de anexo aparece só nas perguntas habilitadas.

## Próxima sessão

[05-web-configurador-persistencia.md](05-web-configurador-persistencia.md)
