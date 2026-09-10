# Sessão Web — Bloco 1 (Núcleo) · Etapa 1: Telas/Navegação

Ver [00-indice.md](00-indice.md) para contexto completo. Pré-requisito: [01-web-preparacao.md](01-web-preparacao.md) (defaults das pendências de NC01 fechados).

## Escopo: NC01, BF01, BF02, BF03

## Estado atual (ponto de partida)

- `clientes`: migration `database/migrations/2026_08_20_090001_create_clientes_table.php` só tem nome/cnpj/cidade/uf/telefone/email/ativo. `ClientesController` é CRUD simples. Telas já em sbadmin: `resources/views/clientes/index.blade.php`, `modal.blade.php` — **tela de referência do padrão sbadmin**, usar como base de componentes.
- `usuarios`: migration `2026_08_20_090000_create_usuarios_table.php`, campo `user_nivel_acesso` (0-Administrador, 1-Técnico). Middleware `app/Http/Middleware/SomenteAdministrador.php` checa `!== 0`. Tela já em sbadmin.
- `atendimentos`: já tem `aten_contato` e join com `clientes`; exibido em `atendimentos/index` e nas tabs de `atendimentos-relatorios`.

## Entregáveis desta sessão

### NC01 — tela de cadastro de cliente unificado
- Expandir `resources/views/clientes/modal.blade.php` (ou tela dedicada, se o volume de campos não couber num modal) com os campos definidos em [01-web-preparacao.md](01-web-preparacao.md): Nome, Contato principal, Vendedor responsável (select de usuários), CNPJ, Inscrição Estadual, Segmento (texto), Classificação (select, pode vir vazio), Alerta de recontato (número, dias), Status (exibição, não editável manualmente).
- Lista repetível de **contatos adicionais**: Nome, Cargo, Telefone, E-mail, Tipo (Técnico/Comercial) — componente de lista dinâmica (Alpine.js, seguindo o padrão do restante do app).
- Bloco "Histórico consolidado" como aba/seção somente leitura (pode ser placeholder vazio nesta etapa — a query real vem na Etapa 2).
- Filtros de listagem: Segmento, Localização (estado/cidade), Classificação — usar `<x-sbadmin::table>` já em uso.
- **Não implementar ainda:** fluxo de aprovação de pré-cadastro, regra de visibilidade por vendedor, alerta de recontato disparando de fato — isso é Etapa 2.

### BF01 — geolocalização no cadastro
- Campo de coordenadas no cadastro de cliente (exibição/edição manual nesta etapa — captura via GPS é majoritariamente mobile, mas o botão "Atribuir localização" e "abrir no Google Maps" devem existir na tela web também).
- Visibilidade do botão condicionada a perfil comercial (a checagem de perfil real vem de BF02 nesta mesma sessão).

### BF02 — perfil comercial
- Adicionar opção "Comercial" ao select de perfil de acesso em `resources/views/usuarios/modal.blade.php`.

### BF03 — exibição do cliente no atendimento
- Confirmar/ajustar que a tela de atendimento (`atendimentos/index` e tabs relacionadas) exibe os dados do cliente vinculado sem navegação adicional — provavelmente já parcialmente coberto, validar contra os campos novos de NC01.

## Critérios de aceite a verificar nesta etapa (visual/navegação, não regra de negócio)

- Cadastrar um cliente preenchendo todos os campos novos e múltiplos contatos.
- Selecionar perfil "Comercial" num usuário e ver o botão de geolocalização aparecer no cadastro de cliente.
- Abrir um atendimento e ver os dados do cliente sem navegação extra.

## Próxima sessão

[03-web-nucleo-persistencia.md](03-web-nucleo-persistencia.md)
