# Padrão de registro de bugs

[← Índice](00-indice.md)

## 1. Severidade (impacto no sistema)

| Severidade | Definição | Exemplo |
|---|---|---|
| **Crítica** | Impede o uso de um fluxo principal, perde ou corrompe dados, ou expõe dados de outro usuário | Não consegue criar relatório; técnico abre relatório de outro técnico |
| **Alta** | Regra de negócio importante quebrada, sem contorno simples | Relatório aprovado pode ser alterado; prazo calculado errado |
| **Média** | Funciona, mas com erro de validação, mensagem errada ou contorno chato | Lista de clientes some após erro de validação |
| **Baixa** | Cosmético, texto, alinhamento, usabilidade | Ícone desalinhado; texto com a marca errada |

**Prioridade** (urgência de correção): o responsável pelo produto define na triagem, entre Imediata, Próxima versão e Backlog. O QA sugere.

---

## 2. Modelo de bug

Abra cada bug como um arquivo `bugs/BUG-NNN.md` nesta pasta, ou como tarefa no gerenciador da equipe, sempre com o mesmo conteúdo:

```markdown
# BUG-NNN — <título curto: o quê + onde>

| Campo | Valor |
|---|---|
| Caso de teste | CT-XXX-NNN (roteiro DNN) |
| Risco relacionado | RNN (se houver) |
| Versão | v1.14.0 |
| Ambiente | local — https://sys-buildflow-fae.local |
| Navegador | Chrome 1xx / Edge 1xx |
| Perfil logado | Técnico (qa.tecnico1@fae.local) |
| Severidade sugerida | Crítica / Alta / Média / Baixa |
| Vídeo | [link](...) — minuto 01:42 |
| Encontrado em | DD/MM/AAAA por <QA> |

## Passos para reproduzir
1. ...
2. ...

## Resultado esperado
...

## Resultado obtido
... (mensagem exata, código HTTP, print)

## Dados usados
Cliente DEMO C1, atendimento A2 (#id), relatório #id ...

## Observações
Frequência (sempre / às vezes), contorno, logs (`storage/logs/laravel.log`, trecho)
```

**Regras:**
- **Um bug por problema.** Não junte dois defeitos no mesmo registro.
- O título diz **o que** está errado e **onde** ("Relatórios › Horário: aceita saída antes da entrada"). Não use títulos genéricos como "erro no relatório".
- Erro 500: copie a linha correspondente de `storage/logs/laravel.log` (sem dados sensíveis).
- Sempre com vídeo. Um clipe curto (até 1 min) basta.

---

## 3. Ciclo de vida

```
Aberto → Em triagem → Em correção → Corrigido (versão X) → Em reteste → Fechado
                                                              └→ Reaberto (volta para Em correção)
           └→ Não é bug / Duplicado / Aceito como está (com justificativa)
```

- **Reteste:**
  - Feito no D11, ou assim que a correção chegar.
  - Repita **exatamente** os passos do bug e grave um vídeo `RETESTE_BUG-NNN_...`.
  - Execute também os casos vizinhos do mesmo roteiro (regressão local).
- **Aceito como está:** precisa do nome de quem aceitou e do motivo no próprio registro.

---

## 4. Lista de bugs da rodada

| Bug | Título | Severidade | CT | Status | Versão corrigida | Vídeo |
|---|---|---|---|---|---|---|
| | | | | | | |
