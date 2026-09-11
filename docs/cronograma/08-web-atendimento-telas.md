# Sessão Web — Bloco 4 (Atendimento/Relatório, incrementos) · Etapa 1: Telas

Ver [00-indice.md](00-indice.md). Pré-requisito: [07-web-crm-persistencia.md](07-web-crm-persistencia.md).

Este é o **último bloco do Web** — os incrementos da assistência técnica ficaram para depois do CRM por prioridade do usuário.

> **Concluída** (`BF_v1.7.0`): esta sessão acabou sendo uma reformulação maior do que o "ajustar abas existentes" que o texto abaixo originalmente descrevia — ver a decisão registrada em `project_fae_bioenergia` (memória, 2026-09-10): o Configurador (NC02) substitui `modelos_relatorios` de verdade. `ConfigModelo` ganhou flags de seção (`cfg_mod_usa_horarios/clima/servicos/pecas/ocorrencias/observacoes`) que substituem as flags da tabela legada; `atendimentos_relatorios.aten_rel_config_modelo_id` liga cada relatório ao modelo do Configurador da sua natureza; um backfill migrou todo modelo/natureza já existente sem quebrar relatórios antigos. Dados/Horários/Assinatura/Anexos continuam estruturais (não viram pergunta) por decisão explícita do usuário nesta sessão; Anexos passou a aceitar somente fotos (pedido do cliente). Nova aba "Perguntas" renderiza dinamicamente as perguntas do modelo (NC02/NC03, com foto+comentário por pergunta quando `cfg_perg_permite_anexo`).

## Escopo: BF06 (ajuste), BF07, BF08, BF09, BF10, BF11

## Estado atual (ponto de partida)

- **BF06**: já implementado (`AtendimentosRelatoriosController::pdf()`, view `atendimentos-relatorios/pdf.blade.php` — intencionalmente fora do sbadmin, é template de impressão). Só precisa do ajuste de layout.
- **BF07**: nada existe.
- **BF08**: nada existe (depende de BF01, já pronto desde o Bloco 1).
- **BF09**: tabela `atendimentos_relatorios_pecas` existe, mas só com `aten_rel_peca_descricao` (texto livre) — falta o checklist booleano.
- **BF10**: `atendimentos_relatorios.aten_rel_status` já existe (0-preenchendo/1-revisar/2-aprovado), usado em `AtendimentosRelatoriosController`/`updateStatus` — falta aprovador/data e bloqueio de edição.
- **BF11**: `AtendimentosController::getObservacoes/updateObservacoes` (rotas em `routes/web.php:73-74`) — existe no nível do **atendimento**; o documento pede nível **relatório**. Verificar com o time se vale reaproveitar a estrutura existente adaptando o nível, ou se são dois conceitos que devem coexistir.

## Entregáveis desta sessão

- **BF06**: ajustar `pdf.blade.php` para 1 imagem por linha com o comentário (de NC03) ao lado; só perguntas respondidas aparecem.
- **BF07**: tela de histórico/comprovante de compartilhamento no painel (data/hora + hash) — o disparo do compartilhamento em si é majoritariamente mobile (WhatsApp/menu nativo), mas o painel web deve permitir consultar o comprovante gerado.
- **BF08**: tela de mapa de demandas por atendimento, com marcação colorida por status (Pendente de Start / Visita técnica agendada / Manutenção agendada) e popup com OSV/pedido/empresa ao passar o mouse.
- **BF09**: checklist de peças levadas/trocadas na tela de atendimento/relatório.
- **BF10**: ajustar a tela de aprovação: status Pendente/Aprovado/Revisar + campo de observação do supervisor; bloquear visualmente edição de relatório já aprovado (leitura apenas).
- **BF11**: mover/ajustar a tela de observações internas para o nível de relatório, confirmando que não aparecerá no PDF assinado (regra de negócio real vem na Etapa 2).

## Critérios de aceite a verificar nesta etapa

- PDF de relatório parcialmente preenchido mostra só as perguntas respondidas.
- Mapa exibe pontos coloridos por status com popup correto.
- Checklist de peças aparece na tela e reflete no relatório final.
- Tentar editar relatório aprovado → bloqueado (leitura).
- Observação interna não aparece na prévia do relatório assinado.

## Próxima sessão

[09-web-atendimento-persistencia.md](09-web-atendimento-persistencia.md) — última sessão do Web antes de começar o Android ([10-android-preparacao.md](10-android-preparacao.md)).
