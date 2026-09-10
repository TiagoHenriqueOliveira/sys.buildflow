# Sessão Android — Bloco 3 (CRM Comercial, CRM09) · Etapa 1: Telas — PRIORIDADE

Ver [00-indice.md](00-indice.md). Pré-requisito: [14-android-configurador-persistencia.md](14-android-configurador-persistencia.md) **e** todo o Bloco 3 do Web concluído ([07-web-crm-persistencia.md](07-web-crm-persistencia.md)) — CRM09 reaproveita integralmente os campos já especificados em CRM01/05/06/07 no Web.

Este bloco foi **priorizado pelo usuário** — vem antes do Bloco 4 (incrementos de Atendimento/Relatório mobile), mesmo sendo o maior bloco novo do app.

## Escopo: CRM09 (telas de orçamento, roteiro de viagem e mapa no app)

## Estado atual

Zero telas/rotas CRM no app — greenfield total. Nenhum arquivo relacionado a cliente comercial/orçamento/roteiro/mapa existe em `lib/`.

## Entregáveis

- Novas rotas no `go_router` (`lib/app/router.dart`), dentro do shell existente ou um shell comercial próprio: tela de orçamento (reaproveita o formulário dinâmico do BF05/NC02, setor Comercial), tela de roteiro de viagem (saída/retorno), tela de mapa de relações de clientes.
- Navegação e itens de menu exclusivos ao perfil comercial (usa o gating implementado no Bloco 1, Sessão 11).
- Tela de orçamento: reutilizar a engine de formulário dinâmico construída em BF05 (Sessão 13) — a diferença é só o setor do modelo consumido (Comercial em vez de Assistência).
- Tela de mapa: usa `google_maps_flutter`/`geolocator` adicionados na Sessão 10.

## Não faz parte desta sessão

Sincronização offline das novas entidades (orçamento, roteiro, comentários) — isso é a Sessão 16, que é o maior esforço de sync do app inteiro dado o volume de entidades novas.

## Critérios de aceite a verificar

- Usuário com perfil comercial acessa as telas de orçamento, roteiro de viagem e mapa.
- Usuário sem perfil comercial não vê essas telas nem os itens de menu correspondentes.
- Orçamento preenchido no app usa as perguntas do modelo (setor Comercial) vinculado ao tipo de sistema selecionado.

## Próxima sessão

[16-android-crm-persistencia.md](16-android-crm-persistencia.md)
