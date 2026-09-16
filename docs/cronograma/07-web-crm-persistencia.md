# Sessão Web — Bloco 3 (CRM Comercial) · Etapa 2: Persistência — PRIORIDADE

Ver [00-indice.md](00-indice.md). Pré-requisito: [05-web-configurador-persistencia.md](05-web-configurador-persistencia.md) (orçamento referencia modelo via `crm_tp_orc_config_modelo_id` → `ConfigModelo`, então a decisão de versionamento do bloco anterior afeta como um orçamento antigo resolve suas perguntas).

**Reescrito em 16/09/2026** com base em auditoria direta do código atual (`BF_v1.8.5`). Não confiar em versão anterior deste documento — CRM03/04/05/06/07 já estavam completos e corretos há várias sessões, e a versão antiga (09/09) descrevia tabelas que nunca existiram (`comentarios` polimórfica, `roteiro_viagem_saida/retorno` separadas, `equipamentos_vendidos`/`casos_sucesso` — o schema real já resolveu tudo isso de outra forma, mais simples).

## Escopo: CRM02 (prazo configurável) + CRM08 (status de fechamento do orçamento)

**CRM01, CRM03, CRM04, CRM05, CRM06, CRM07 já estão completos** — vínculo modelo↔tipo de orçamento, comentários com alerta e exclusão, vendedores adicionais sem comissão, roteiro de viagem saída/retorno, mapa de relações (equipamento vendido/caso de sucesso já são colunas de `clientes`). Não fazem parte desta sessão.

## Estado atual (auditado direto no código)

- **CRM02**: `NivelOrcamento::prazoEmDiasMockado()` (`app/Enums/NivelOrcamento.php`) já calcula um prazo — 3/7/15 dias corridos por nível (Simples/Médio/Complexo) — mas é **hardcoded no Enum**, com comentário próprio dizendo "fórmula real fica pra sessão de persistência". `OrcamentoRepository::create()`/`update()` já chamam esse método quando `orc_prazo_envio` não é preenchido manualmente.
- **CRM08**: `IndicadoresComerciaisController::index()` calcula "propostas levantadas" e "clientes visitados" com dado real, mas **"propostas fechadas"/"taxa de conversão" usam uma taxa fixa de 70% mockada** — comentário no código confirma: "não há status de fechamento ainda". Não existe nenhuma coluna de status de fechamento em `orcamentos`.

## Entregáveis

### 1. CRM02 — prazo configurável sem deploy
- Nova tabela `crm_config_prazos_nivel` (`nivel` int, `dias` int) OU, mais simples ainda, uma tela de configuração que edita direto os 3 valores associados a cada `NivelOrcamento` (decidir a forma mais simples que atenda "ajustável sem deploy" — não precisa ser genérico, só editável pelo Administrador sem mexer em código).
- `OrcamentoRepository`: trocar a chamada a `prazoEmDiasMockado()` por uma leitura dessa configuração (cache simples, já que muda raramente).
- Tela de configuração (novo CRUD simples, admin-only) pra editar os 3 valores.
- Remover a palavra "mockada"/o comentário de pendência do Enum e do texto de ajuda em `orcamentos/form.blade.php` (`help="Deixe em branco para usar a sugestão automática a partir do nível."` já está neutro desde `BF_v1.8.3` — só confirmar que continua batendo com a realidade depois da mudança).

### 2. CRM08 — status de fechamento real
- Migration: `orc_status_fechamento` (tinyint, default 0) em `orcamentos` + enum `App\Enums\StatusFechamentoOrcamento` (`Aberto=0, Ganho=1, Perdido=2`).
- Tela de Orçamento: campo/ação pra marcar Ganho/Perdido (aba Dados, ou botão de ação rápida na listagem — decidir com base na UX das outras telas já aprovadas).
- `IndicadoresComerciaisController::index()`: substituir a taxa de 70% mockada pelo cálculo real (`Ganho / (Ganho + Perdido)` no período filtrado, ou incluir "Aberto" como não contabilizado ainda — definir o critério e documentar no código).

## ⚠️ Ponto a confirmar com o usuário
CRM08 pede "linha do tempo do orçamento" como evolução futura (pendência #8 do cliente, ainda sem resposta) — o status de fechamento acima **não é** essa linha do tempo (é só um campo final, não um histórico de mudanças). Não expandir escopo pra histórico de status sem pedido explícito.

## Testes

Criar/estender `tests/Feature/CrmPersistenciaFaeTest.php`:
- Alterar a configuração de prazo por nível reflete no próximo orçamento criado sem prazo manual.
- Orçamento existente com prazo já preenchido manualmente não é afetado pela mudança de configuração.
- Marcar orçamento como Ganho/Perdido reflete nos indicadores comerciais (taxa de conversão real, não mais 70% fixo).
- Orçamento "Aberto" não conta nem como ganho nem como perdido no cálculo.

## Ao finalizar

- Bump de versão **MINOR**.
- Revisão de segurança: rota nova de configuração de prazo é admin-only; nenhuma rota de orçamento ficou sem `auth`.

## Próxima sessão

[09-web-atendimento-persistencia.md](09-web-atendimento-persistencia.md)