# Sessão Web — Bloco 1 (Núcleo) · Etapa 2: Persistência/Regras de Negócio

Ver [00-indice.md](00-indice.md) para contexto completo. Pré-requisito: [02-web-nucleo-telas.md](02-web-nucleo-telas.md).

## Escopo: NC01, BF01, BF02, BF03

## Entregáveis

### NC01
- Migrations: novos campos em `clientes` (contato_principal, vendedor_responsavel_id, inscricao_estadual, segmento, classificacao_id, alerta_recontato_dias, status_aprovacao); nova tabela `contatos_cliente` (cliente_id, nome, cargo, telefone, email, tipo [tecnico/comercial]); índice **único** em CNPJ (RNF04 — critério de identificação de duplicidade).
- Regra de **pré-cadastro CRM**: vendedor cria cliente com `status_aprovacao = pendente`; administrador aprova (`aprovado`). Cadastro feito por administrador já nasce aprovado. Assistência técnica não tem a opção de pré-cadastro — só usa clientes já aprovados.
- Regra de **visibilidade**: contatos do tipo Comercial e pré-cadastros feitos por um vendedor só aparecem para o próprio vendedor e para administradores — não aparecem para usuários da assistência técnica. Implementar como scope/policy no model `Cliente`, não como filtro ad-hoc em cada controller.
- **Histórico consolidado**: consulta agregada (não tabela nova) sobre atendimentos + relatórios + (futuramente) orçamentos, ordenada cronologicamente.
- Migração segura do dado existente (RNF04): script/seed que consolida os dois cadastros anteriores sem duplicar nem perder histórico — importante revisar quais dados legados existem antes de rodar em produção.

### BF01
- Colunas `latitude`/`longitude` em `clientes`.
- Regra de visibilidade: botão/campo de geo só operante para perfil comercial (já sinalizado na tela; aqui é a validação de backend).

### BF02
- Novo valor de perfil na estrutura de usuários (`user_nivel_acesso` ou coluna dedicada — decidir mantendo compatibilidade com `SomenteAdministrador` middleware, que hoje assume `0/1`).
- Regra de menu: usuário sem perfil comercial não vê módulo CRM (isso antecipa a Sessão 06-07, mas a gate em si é implementada agora).

### BF03
- Join/query otimizado sobre a tabela de clientes já existente — sem tabela nova.

## Testes

Criar `tests/Feature/NucleoFaeTest.php` (ou dividir por item) cobrindo os critérios de aceite do documento (seção NC01/BF01/BF02/BF03):
- Cadastrar cliente com múltiplos contatos.
- Unificar cliente existente nas duas origens sem duplicar.
- Consultar histórico consolidado em ordem cronológica.
- Vendedor cria pré-cadastro → fica Pendente; administrador aprova → some a pendência.
- Assistência técnica não vê opção de pré-cadastro.
- Contato Comercial de um vendedor não aparece para usuário técnico.
- CNPJ duplicado é rejeitado/alertado.
- Filtros de Segmento/Localização/Classificação funcionam.
- Perfil comercial habilita módulo CRM no menu; sem o perfil, não aparece.

## Ao finalizar

- Bump de versão **MINOR** (`config/app.php` + `.env`, convenção `BF_vMAJOR.MINOR.PATCH`).
- Revisão de segurança: conferir que as rotas novas de clientes/contatos/aprovação exigem `auth` (sessão web) — não deveria haver rota pública nova aqui.

## Próxima sessão

[04-web-configurador-telas.md](04-web-configurador-telas.md)
