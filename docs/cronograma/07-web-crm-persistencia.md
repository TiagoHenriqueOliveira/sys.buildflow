# Sessão Web — Bloco 3 (CRM Comercial) · Etapa 2: Persistência — PRIORIDADE

Ver [00-indice.md](00-indice.md). Pré-requisito: [06-web-crm-telas.md](06-web-crm-telas.md). Mesma nota de possível divisão em duas sessões (07a: CRM01-04, 07b: CRM05-08).

## Escopo: CRM01, CRM02, CRM03, CRM04, CRM05, CRM06, CRM07, CRM08

## Entregáveis — schema

- **CRM01**: reaproveita `vinculo_modelo_contexto` (criada no Bloco 2) — associa tipo de sistema de orçamento a uma revisão de modelo setor Comercial.
- **CRM02**: colunas em `orcamentos`: nivel, prazo_envio, prazo_editado_manualmente (bool).
- **CRM03**: tabela `comentarios` (polimórfica ou com `orcamento_id`/`cliente_id` nulável): autor_id, texto, data_hora, disparar_alerta, destinatario_alerta_id.
- **CRM04**: tabela pivot `orcamento_vendedores` (orcamento_id, usuario_id) — permite mais de um vendedor por orçamento, sem campo de comissão.
- **CRM05**: tabela `roteiro_viagem_saida` (vendedor_id, data_inicio, data_fim) + pivot `roteiro_viagem_clientes`.
- **CRM06**: tabela `roteiro_viagem_retorno` (roteiro_saida_id, cliente_id, resultado [visitado/nao_realizado/reagendado], observacao).
- **CRM07**: reaproveita geolocalização de `clientes` (BF01); tabelas `equipamentos_vendidos` (cliente_id, descricao) e `casos_sucesso` (cliente_id, texto).
- **CRM08**: **sem tabela nova** — consultas agregadas sobre `orcamentos`, `roteiro_viagem_saida/retorno` e `clientes`.

## Regras de negócio

- **CRM02**: cálculo automático do prazo de envio conforme o nível (Simples/Médio/Complexo) — definir a fórmula/regra de dias por nível (não especificada em detalhe no documento; usar um valor configurável por nível, ajustável sem deploy). Edição manual sempre permitida, com flag indicando que foi editado.
- **CRM01**: um novo orçamento do tipo vinculado usa as perguntas da revisão de modelo vigente (mesma regra de versionamento de NC02).
- **Geração de PDF do orçamento**: reaproveitar o padrão de BF06 (DomPDF) para gerar o PDF do orçamento a partir das respostas — não é um item numerado à parte no documento, mas é implícito ao CRM (orçamentos também compartilham a opção de compartilhamento com hash de BF07, que só é implementada no Bloco 4; aqui já deixar o PDF gerável).

## Testes

- Vincular modelo a tipo de sistema → orçamento novo usa as perguntas certas.
- Classificar orçamento → prazo calculado corretamente; editar manualmente → edição preservada.
- Comentário com alerta → destinatário notificado.
- Dois vendedores no mesmo orçamento, sem cálculo de comissão.
- Roteiro de saída + retorno vinculados corretamente.
- Indicador de conversão por vendedor reflete propostas fechadas no período.

## Ao finalizar

- Bump MINOR + revisão de segurança das rotas novas do grupo `fae/v1` relacionadas a CRM.

## Próxima sessão

[08-web-atendimento-telas.md](08-web-atendimento-telas.md)
