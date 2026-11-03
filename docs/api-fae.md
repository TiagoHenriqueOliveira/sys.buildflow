# API FAE Bioenergia — Documentação

**Base URL:** `https://{dominio}/api/fae/v1`  
**Autenticação:** Bearer Token (Laravel Sanctum)  
**Content-Type:** `application/json` (exceto uploads: `multipart/form-data`)

---

## Autenticação

### POST /login

Realiza login e retorna o token de acesso.

**Body:**
```json
{
  "email": "tecnico@exemplo.com",
  "password": "senha123"
}
```

**Resposta 200:**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 5,
    "nome": "João Silva",
    "email": "tecnico@exemplo.com",
    "nivel_acesso": 1
  }
}
```

> Use o `token` no header `Authorization: Bearer {token}` em todas as rotas protegidas.

---

### POST /logout

Revoga o token atual.

**Header:** `Authorization: Bearer {token}`

**Resposta 200:**
```json
{ "message": "Logout realizado com sucesso." }
```

---

### GET /me

Retorna os dados do usuário autenticado.

**Resposta 200:**
```json
{
  "id": 5,
  "nome": "João Silva",
  "email": "tecnico@exemplo.com",
  "nivel_acesso": 1
}
```

---

## Catálogos

### GET /catalogos/ocorrencias

Lista todas as ocorrências disponíveis para vincular ao relatório.

**Resposta 200:**
```json
{
  "data": [
    { "id": 1, "descricao": "Equipamento danificado" },
    { "id": 2, "descricao": "Falta de energia" }
  ]
}
```

---

## Atendimentos

### GET /atendimentos

Lista os atendimentos do técnico autenticado.  
Administradores veem todos os atendimentos.

**Query params opcionais:**

| Parâmetro | Tipo   | Descrição                       |
|-----------|--------|---------------------------------|
| `status`  | int    | Filtrar por status (0, 1, 2, 3) |
| `search`  | string | Busca pelo nome do cliente      |

**Resposta 200:**
```json
{
  "data": [
    {
      "id": 10,
      "responsavel": "Carlos Souza",
      "telefone": "(11) 99999-0000",
      "endereco": "Rua das Flores, 100",
      "nr_proposta": "2024/001",
      "entrega_tecnica": false,
      "status": 1,
      "status_label": "Em Andamento",
      "dt_inicio": "2024-06-01",
      "dt_fim": "2024-06-30",
      "natureza": { "id": 2, "descricao": "Manutenção Preventiva" },
      "cliente": { "id": 3, "nome": "Empresa ABC" },
      "tecnico": { "id": 5, "nome": "João Silva" }
    }
  ]
}
```

---

### GET /atendimentos/{id}

Detalhe completo de um atendimento, incluindo equipamentos e observações.

**Resposta 200:**
```json
{
  "data": {
    "id": 10,
    "responsavel": "Carlos Souza",
    "telefone": "(11) 99999-0000",
    "endereco": "Rua das Flores, 100",
    "nr_proposta": "2024/001",
    "entrega_tecnica": true,
    "status": 1,
    "status_label": "Em Andamento",
    "dt_inicio": "2024-06-01",
    "dt_fim": "2024-06-30",
    "obs_cliente": "Equipamento barulhento",
    "obs_tecnica": "Verificar rolamentos",
    "obs_manutencao": "Lubrificação mensal",
    "natureza": { "id": 2, "descricao": "Manutenção Preventiva" },
    "cliente": { "id": 3, "nome": "Empresa ABC" },
    "tecnico": { "id": 5, "nome": "João Silva" },
    "equipamentos": [
      { "id": 1, "descricao": "Compressor 10HP", "observacoes": "Unidade 1" }
    ]
  }
}
```

---

## Relatórios

### GET /atendimentos/{aten_id}/relatorios

Lista os relatórios de um atendimento.

**Resposta 200:**
```json
{
  "data": [
    {
      "id": 7,
      "data": "2024-06-10",
      "status": 0,
      "status_label": "Preenchendo"
    }
  ]
}
```

---

### POST /atendimentos/{aten_id}/relatorios

Cria um novo relatório para o atendimento.

**Body:**
```json
{
  "aten_rel_data": "2024-06-10"
}
```

> `aten_rel_data` é opcional; padrão: data atual.

**Resposta 201:**
```json
{
  "message": "Relatório criado.",
  "data": { "id": 7 }
}
```

---

### GET /relatorios/{id}

Retorna todos os dados de um relatório.

**Resposta 200:**
```json
{
  "data": {
    "id": 7,
    "data": "2024-06-10",
    "status": 0,
    "status_label": "Preenchendo",
    "descricao": "Manutenção realizada nos rolamentos.",
    "informacoes_adicionais": "Próxima revisão em 6 meses.",
    "prazo": {
      "prazo_total": 29,
      "prazo_decorrido": 9,
      "prazo_a_vencer": 20
    },
    "atendimento": {
      "id": 10,
      "responsavel": "Carlos Souza",
      "telefone": "(11) 99999-0000",
      "endereco": "Rua das Flores, 100",
      "entrega_tecnica": true,
      "obs_cliente": "Equipamento barulhento",
      "obs_tecnica": "Verificar rolamentos",
      "obs_manutencao": "Lubrificação mensal",
      "dt_inicio": "2024-06-01",
      "dt_fim": "2024-06-30",
      "cliente": "Empresa ABC",
      "natureza": "Manutenção Preventiva",
      "equipamentos": [
        { "id": 1, "descricao": "Compressor 10HP" }
      ]
    },
    "horarios": {
      "entrada": "08:00",
      "inicio_intervalo": "12:00",
      "fim_intervalo": "13:00",
      "saida": "17:00"
    },
    "horarios_dias": [
      { "data": "2024-06-03", "dia_semana": "Segunda-feira", "entrada": "08:00", "inicio_intervalo": "12:00", "fim_intervalo": "13:00", "saida": "17:00" },
      { "data": "2024-06-04", "dia_semana": "Terça-feira", "entrada": "07:30", "inicio_intervalo": "", "fim_intervalo": "", "saida": "16:00" }
    ],
    "pode_aprovar": false,
    "status_permitidos": [
      { "value": 0, "label": "Preenchendo" },
      { "value": 1, "label": "Revisar" },
      { "value": 3, "label": "Liberado para aprovação" }
    ],
    "clima": {
      "manha": "Ensolarado",
      "tarde": "Nublado",
      "noite": null
    },
    "servicos": [
      { "id": 1, "descricao": "Troca de rolamento" }
    ],
    "pecas": [
      { "id": 1, "descricao": "Rolamento 6205" }
    ],
    "ocorrencias": [
      { "ocorrencia_id": 3, "descricao": "Falta de energia", "observacao": "Durou 2h" }
    ],
    "assinaturas": {
      "tecnico": "https://dominio/storage/atendimentos_relatorios/7/assinaturas/responsavel.png",
      "cliente": null
    }
  }
}
```

---

### PUT /relatorios/{id}/descricao

Atualiza a descrição do relatório.

**Body:**
```json
{
  "descricao": "Manutenção realizada nos rolamentos e correia."
}
```

**Resposta 200:**
```json
{ "message": "Descrição atualizada." }
```

---

### PUT /relatorios/{id}/informacoes-adicionais

Atualiza as informações adicionais.

**Body:**
```json
{
  "informacoes_adicionais": "Próxima revisão em 6 meses."
}
```

**Resposta 200:**
```json
{ "message": "Informações adicionais atualizadas." }
```

---

### PUT /relatorios/{id}/horarios

Grava os horários **por dia** do relatório (desde 03/11/2026 um relatório pode cobrir o período todo do atendimento). A lista enviada **substitui** todos os dias já gravados.

**Body:**
```json
{
  "dias": [
    { "data": "2024-06-03", "entrada": "08:00", "inicio_intervalo": "12:00", "fim_intervalo": "13:00", "saida": "17:30" },
    { "data": "2024-06-04", "entrada": "07:30", "saida": "16:00" }
  ]
}
```

> `data` obrigatória (`YYYY-MM-DD`, não futura, sem repetir). Horas em `HH:MM`, todas opcionais na API — dia sem entrada ou saída é ignorado. Saída ≥ entrada e intervalo dentro do expediente.
>
> **Compatibilidade:** o formato antigo (objeto único `{ entrada, inicio_intervalo, fim_intervalo, saida }`, sem `dias`) continua aceito e vira um dia na data do relatório. No `GET /relatorios/{id}`, `horarios` (objeto único = 1º dia) continua vindo; o formato novo é `horarios_dias`.

**Resposta 200:**
```json
{
  "message": "Horários atualizados.",
  "horarios_dias": [
    { "data": "2024-06-03", "dia_semana": "Segunda-feira", "entrada": "08:00", "inicio_intervalo": "12:00", "fim_intervalo": "13:00", "saida": "17:30" }
  ]
}
```

**Resposta 422:** erro por dia, ex. `errors["dias.1.saida"]`.

---

### PUT /relatorios/{id}/clima

Atualiza as condições climáticas do dia.

**Body:**
```json
{
  "manha": "ensolarado",
  "tarde": "nublado",
  "noite": "chuvoso"
}
```

> Valores aceitos: `ensolarado`, `nublado`, `chuvoso`. Todos opcionais.

**Resposta 200:**
```json
{ "message": "Clima atualizado." }
```

---

### PUT /relatorios/{id}/status

Altera o status do relatório.

**Body:**
```json
{
  "status": 1
}
```

| Valor | Significado  |
|-------|--------------|
| `0`   | Preenchendo  |
| `1`   | Revisar      |
| `2`   | Aprovado     |
| `3`   | Liberado para aprovação |

**Regras (desde 03/11/2026):**
- Técnico pode escolher Preenchendo, Revisar ou **Liberado para aprovação**. Liberar exige a assinatura do técnico já gravada (`POST /relatorios/{id}/assinaturas` antes).
- **Aprovado** só por usuário de perfil Assistência ou Administrador, e só a partir de Liberado. O `GET /relatorios/{id}` informa `pode_aprovar` e `status_permitidos` para o app montar os botões.
- Relatório aprovado só volta a outro status pela Assistência/Administrador.
- Ao liberar, os usuários de perfil Assistência recebem notificação (`GET /notificacoes`).
- Mudança não permitida devolve **422** com a mensagem — não existe mais o rebaixamento silencioso de Aprovado → Revisar (`rebaixado` vem sempre `false`, mantido só por compatibilidade).

**Resposta 200:**
```json
{ "message": "Status alterado para: Liberado para aprovação.", "status": 3, "rebaixado": false }
```

**Resposta 422:**
```json
{ "message": "Para liberar para aprovação, registre antes a assinatura do técnico.", "status": 0 }
```

---

## Serviços

### POST /relatorios/{id}/servicos

Adiciona um serviço prestado ao relatório.

**Body:**
```json
{
  "descricao": "Troca de correia dentada"
}
```

**Resposta 201:**
```json
{
  "message": "Serviço adicionado.",
  "data": { "id": 4, "descricao": "Troca de correia dentada" }
}
```

---

### DELETE /relatorios/{id}/servicos/{serv_id}

Remove um serviço do relatório.

**Resposta 200:**
```json
{ "message": "Serviço removido." }
```

---

## Peças

### POST /relatorios/{id}/pecas

Adiciona uma peça substituída ao relatório.

**Body:**
```json
{
  "descricao": "Correia dentada HTD 3M"
}
```

**Resposta 201:**
```json
{
  "message": "Peça adicionada.",
  "data": { "id": 3, "descricao": "Correia dentada HTD 3M" }
}
```

---

### DELETE /relatorios/{id}/pecas/{peca_id}

Remove uma peça do relatório.

**Resposta 200:**
```json
{ "message": "Peça removida." }
```

---

## Ocorrências

### POST /relatorios/{id}/ocorrencias

Vincula uma ocorrência ao relatório.

**Body:**
```json
{
  "ocorrencia_id": 3,
  "observacao": "Durou aproximadamente 2 horas"
}
```

> `observacao` é opcional.

**Resposta 201:**
```json
{
  "message": "Ocorrência adicionada.",
  "data": {
    "ocorrencia_id": 3,
    "descricao": "Falta de energia",
    "observacao": "Durou aproximadamente 2 horas"
  }
}
```

---

### DELETE /relatorios/{id}/ocorrencias/{ocorrencia_id}

Remove uma ocorrência do relatório.

**Resposta 200:**
```json
{ "message": "Ocorrência removida." }
```

---

## Assinaturas

### POST /relatorios/{id}/assinaturas

Salva a assinatura do técnico e/ou do cliente.  
As imagens devem ser enviadas em Base64 (PNG ou JPEG).

**Body:**
```json
{
  "tecnico": "data:image/png;base64,iVBORw0KGgo...",
  "cliente": "data:image/png;base64,iVBORw0KGgo..."
}
```

> Ambos os campos são opcionais; envie apenas o(s) que tiver disponível.

**Resposta 200:**
```json
{
  "message": "Assinatura(s) salva(s).",
  "data": {
    "tecnico": "https://dominio/storage/atendimentos_relatorios/7/assinaturas/responsavel.png",
    "cliente": "https://dominio/storage/atendimentos_relatorios/7/assinaturas/cliente.png"
  }
}
```

---

## Fotos, Vídeos e Arquivos

### GET /relatorios/{id}/anexos

Lista todos os anexos do relatório.

**Resposta 200:**
```json
{
  "data": {
    "fotos": [
      { "id": 1, "url": "https://dominio/storage/...", "legenda": "Motor dianteiro" }
    ],
    "videos": [
      { "id": 1, "url": "https://dominio/storage/..." }
    ],
    "arquivos": [
      { "id": 1, "url": "https://dominio/storage/..." }
    ]
  }
}
```

---

### POST /relatorios/{id}/anexos

Faz upload de fotos, vídeos e/ou arquivos.

**Content-Type:** `multipart/form-data`

**Campos:**

| Campo        | Tipo     | Formatos aceitos                    | Limite  |
|--------------|----------|-------------------------------------|---------|
| `fotos[]`    | arquivo  | jpg, jpeg, png, webp                | 10 MB   |
| `legendas[]` | string   | Legenda por índice da foto          | —       |
| `videos[]`   | arquivo  | mp4, mov, avi, mkv, webm            | 200 MB  |
| `arquivos[]` | arquivo  | pdf, doc, docx, xls, xlsx, txt, csv | 20 MB   |

> `legendas[0]` corresponde a `fotos[0]`, `legendas[1]` a `fotos[1]`, e assim por diante.

**Resposta 200:**
```json
{
  "message": "Upload realizado.",
  "data": {
    "fotos": [
      { "id": 5, "url": "https://dominio/storage/...", "legenda": "Motor dianteiro" }
    ],
    "videos": [],
    "arquivos": []
  }
}
```

---

### DELETE /relatorios/{id}/anexos/{tipo}/{item_id}

Remove um anexo específico.

| Parâmetro | Valores válidos          |
|-----------|--------------------------|
| `tipo`    | `foto`, `video`, `arquivo` |
| `item_id` | ID retornado no upload   |

**Resposta 200:**
```json
{ "message": "Anexo removido." }
```

---

## Códigos de erro

| HTTP | Situação                                      |
|------|-----------------------------------------------|
| 401  | Token ausente ou inválido                     |
| 403  | Técnico tentando acessar dado de outro técnico |
| 404  | Recurso não encontrado                        |
| 422  | Dados inválidos (ver campo `errors`)          |
| 500  | Erro interno do servidor                      |

**Exemplo de erro 422:**
```json
{
  "message": "The descricao field is required.",
  "errors": {
    "descricao": ["The descricao field is required."]
  }
}
```
