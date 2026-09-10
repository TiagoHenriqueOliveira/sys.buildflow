# Sessão Web — Bloco 3 (CRM Comercial) · Etapa 1: Telas — PRIORIDADE

Ver [00-indice.md](00-indice.md). Pré-requisito: [05-web-configurador-persistencia.md](05-web-configurador-persistencia.md) (precisa de NC01 e NC02 prontos).

Este bloco foi **priorizado pelo usuário** — vem antes do Bloco 4 (incrementos de Atendimento/Relatório da assistência técnica), mesmo sendo o maior bloco novo do projeto. Se a sessão ficar grande demais para revisar de uma vez, dividir em duas: **06a** (CRM01-04: orçamento/vínculo/comentários/indicação conjunta) e **06b** (CRM05-08: roteiro de viagem/mapa/indicadores).

## Escopo: CRM01, CRM02, CRM03, CRM04, CRM05, CRM06, CRM07, CRM08

## Estado atual

Zero código. Nenhuma tabela, controller, rota ou view de orçamento/comercial existe no repositório — bloco inteiramente greenfield. Reaproveitar o padrão de tela já estabelecido (`packages/sbadmin`, `<x-sbadmin::table>` com paginação nativa do Laravel, `<x-sbadmin::form.*>`) e a estrutura do Configurador (Bloco 2) para o formulário de orçamento em si (as perguntas do orçamento vêm de NC02, setor "Comercial").

## Entregáveis desta sessão

- **CRM01** *(Baixa)*: tela de vínculo entre tipo de sistema do orçamento (Genérico, Sistema de Deságue de Lodo, ETE Nova Residencial, ETE Nova Industrial, ETE Melhoria Industrial) e modelo do Configurador (setor Comercial).
- **CRM02** *(Baixa)*: campos de nível do orçamento (Simples/Médio/Complexo) e prazo de envio (cálculo automático mockado nesta etapa — a fórmula real vem na Etapa 2), editável manualmente.
- **CRM03** *(Baixa)*: tela de comentários no orçamento/cliente, com autor/data/hora e opção de disparar alerta para outro usuário.
- **CRM04** *(Baixa)*: seleção de vendedores adicionais no orçamento (indicação conjunta, **sem** cálculo de comissão).
- **CRM05/06** *(Baixa)*: telas de roteiro de viagem — saída (vendedor, período, clientes a visitar) e retorno (resultado por cliente do roteiro: Visitado/Não realizado/Reagendado + observação).
- **CRM07** *(Média)*: tela de mapa de relações de clientes — filtros por Vendedor/Estado/Segmento/Classificação, exibindo equipamento vendido e caso de sucesso por cliente.
- **CRM08** *(Média)*: painel de indicadores comerciais (propostas levantadas/fechadas, taxa de conversão por vendedor, clientes visitados por período) — dados mockados nesta etapa.

## Fora de escopo (explicitamente, conforme o documento)

- Cálculo/divisão de comissão entre vendedores (CRM04).
- Linha do tempo detalhada de cada solicitação de orçamento (fica para uma fase futura, junto de CRM08 — pendência #8).
- Integração com CRM de terceiros ou ferramentas de automação de marketing.

## Critérios de aceite a verificar nesta etapa

- Vincular modelo a tipo de sistema e ver as perguntas certas ao abrir um novo orçamento.
- Classificar orçamento e ver o prazo sugerido (mesmo que mockado).
- Registrar comentário com alerta.
- Associar dois vendedores a um orçamento.
- Cadastrar roteiro de saída com lista de clientes; registrar retorno vinculado.
- Visualizar mapa filtrado por vendedor/estado/segmento.

## Próxima sessão

[07-web-crm-persistencia.md](07-web-crm-persistencia.md)
