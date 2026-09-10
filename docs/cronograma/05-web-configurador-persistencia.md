# Sessão Web — Bloco 2 (Configurador) · Etapa 2: Persistência

Ver [00-indice.md](00-indice.md). Pré-requisito: [04-web-configurador-telas.md](04-web-configurador-telas.md).

## Escopo: NC02, NC03, NC04, BF04

## Entregáveis

### NC02 — schema
- `perguntas` (texto, tipo_resposta, permite_anexo_imagem bool)
- `opcoes_resposta` (pergunta_id, label, ordem) — usada quando tipo é múltipla/única escolha
- `modelos` (nome, setor)
- `modelo_pergunta` (pivot: modelo_id, pergunta_id)
- `modelo_revisoes` — **cada alteração relevante em um modelo já utilizado gera uma nova revisão**, preservando a anterior; relatórios/orçamentos já preenchidos ficam vinculados à revisão vigente no momento do preenchimento. Modelar como: `modelos` guarda o registro "canônico", `modelo_revisoes` guarda snapshots versionados (ou uma tabela de revisão com FK para o modelo original + número da revisão + conjunto de perguntas daquela revisão).
- `vinculo_modelo_contexto` (modelo_revisao_id, tipo_contexto [atendimento/orcamento], contexto_id) — usada tanto por BF04 quanto por CRM01.
- `respostas` (pergunta_id, modelo_revisao_id, relatorio_id ou orcamento_id, valor)

### NC03
- Campo de anexo (path/URL) + campo de comentário na tabela `respostas`, preenchido só quando a pergunta correspondente permite anexo.

### NC04
- Tabela `anexos_gerais_relatorio` (relatorio_id, path, tipo, data_criacao) — sem campo de comentário, independente de `respostas`.

### BF04
- Reaproveita `vinculo_modelo_contexto` (não cria tabela nova): associa tipo de atendimento a uma revisão de modelo.

## Regra de negócio central (a mais delicada deste bloco)

Versionamento de modelo: ao editar um modelo já em uso (ex.: adicionar/remover pergunta), criar uma nova revisão em vez de alterar a existente. Relatórios/orçamentos referenciam a revisão específica usada no preenchimento — uma atualização posterior do modelo **não** deve alterar retroativamente dados já preenchidos.

## Testes

- Pergunta de múltipla escolha com 3 opções → aparece como checkbox.
- Pergunta de escolha única → radiobutton. Texto livre → campo de texto.
- Modelo sem pergunta → bloqueado.
- Alterar modelo já utilizado → nova revisão criada; relatório antigo continua na revisão antiga; relatório novo usa a revisão mais recente.
- Anexo de imagem offline (mobile virá depois, mas o endpoint web/API deve suportar o fluxo) + comentário associado aparecem juntos no relatório final.

## Ao finalizar

- Bump MINOR + revisão de segurança das rotas novas (`perguntas`, `modelos`, `respostas`, `anexos-gerais`).

## Próxima sessão

[06-web-crm-telas.md](06-web-crm-telas.md) — **Bloco 3, CRM Comercial, priorizado.**
