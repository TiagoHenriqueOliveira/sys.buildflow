# Sessão Web — Preparação

Ver [00-indice.md](00-indice.md) para contexto completo. Esta sessão é curta e opcional — pode ser incorporada direto na sessão [02-web-nucleo-telas.md](02-web-nucleo-telas.md) se preferir não isolá-la.

## Objetivo

Fechar, por escrito, os defaults técnicos das pendências do cliente que afetam o Bloco 1 (Núcleo), antes de começar a implementar NC01 — para não improvisar campo por campo em telas separadas.

## Entregáveis

Documento curto (pode ser um comentário no início da migration de `clientes`, ou um adendo a este arquivo) fixando:

1. **Segmento do cliente** (pendência #1) — campo de texto livre (`varchar`, nullable) por enquanto. Não criar tabela de lookup ainda; deixar o campo pronto para virar FK depois sem quebrar dado existente.
2. **Campos do cadastro de cliente** (pendência #2) — usar exatamente os campos listados na seção 2 do documento de requisitos (Nome, Contato principal, Vendedor responsável, CNPJ, Inscrição Estadual, Segmento, Classificação, Alerta de recontato, Status, Contatos adicionais, Geolocalização). Campos específicos do ERP SINPROD (fora dessa lista) ficam para um incremento futuro, fora deste cronograma.
3. **Classificação do cliente** (pendência #3) — implementar como lista configurável (nova tabela `classificacoes_cliente` ou enum extensível), inicialmente **vazia** — a UI deve suportar zero opções sem quebrar.
4. **Alerta de recontato** (pendência #4) — campo numérico "dias de inatividade" configurável por cliente ou por sistema (decidir qual granularidade ao implementar — o documento não especifica); valor default sugerido: deixar em branco/nulo até confirmação, não travar com um número arbitrário no código.

## Não faz parte desta sessão

Nenhuma migration, controller ou tela ainda — é só a decisão registrada, para servir de especificação de entrada da sessão 02.

## Próxima sessão

[02-web-nucleo-telas.md](02-web-nucleo-telas.md)
