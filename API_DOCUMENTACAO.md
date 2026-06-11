# BuildFlow — Documentação da API REST

> Versão: **v1**  
> Base URL: `http://SEU_DOMINIO/api/v1`  
> Autenticação: **Bearer Token** (Laravel Sanctum)  
> Content-Type: `application/json` (exceto uploads, que usam `multipart/form-data`)

---

## Convenção: campos somente leitura

Em toda a API, campos marcados com **`[somente leitura]`** são apenas para **exibição no app**. O app não envia esses valores de volta — apenas o **ID** correspondente é enviado nas operações de gravação.

**Exemplo:**
- `descricao: "Eletricista"` → **[somente leitura]** — exiba para o usuário
- `ocup_id: 2` → use este ID para adicionar ao relatório

---

## Índice

1. [Autenticação](#1-autenticação)
2. [Catálogos — Listas de seleção](#2-catálogos--listas-de-seleção)
3. [Atendimentos](#3-atendimentos)
4. [Relatórios — Geral](#4-relatórios--geral)
5. [Relatórios — Horários](#5-relatórios--horários)
6. [Relatórios — Clima](#6-relatórios--clima)
7. [Relatórios — Mão de Obra](#7-relatórios--mão-de-obra)
8. [Relatórios — Ferramentas/Equipamentos](#8-relatórios--ferramentasequipamentos)
9. [Relatórios — Atividades](#9-relatórios--atividades)
10. [Relatórios — Ocorrências](#10-relatórios--ocorrências)
11. [Relatórios — Comentários](#11-relatórios--comentários)
12. [Relatórios — Assinaturas](#12-relatórios--assinaturas)
13. [Relatórios — Anexos, Fotos e Vídeos](#13-relatórios--anexos-fotos-e-vídeos)
14. [Erros Comuns](#14-erros-comuns)
15. [Guia Flutter](#15-guia-flutter)

---

## 1. Autenticação

### POST `/login` — Login

Não requer token. Retorna o Bearer token que deve ser incluído em todas as demais requisições.

**Request:**
```json
{
  "email": "tecnico@empresa.com",
  "senha": "minha_senha",
  "device_name": "flutter_app"
}
```

**Response 200:**
```json
{
  "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz...",
  "token_type": "Bearer",
  "usuario": {
    "id": 5,
    "nome": "João Silva",
    "email": "tecnico@empresa.com",
    "nivel_acesso": 1
  }
}
```

> `nivel_acesso: 1` = Técnico — vê apenas seus próprios atendimentos e relatórios  
> `nivel_acesso: 2+` = Administrador — vê todos os dados

**Response 422 — Credenciais inválidas:**
```json
{
  "message": "The given data was invalid.",
  "errors": { "email": ["Credenciais inválidas."] }
}
```

---

### POST `/logout`

**Headers:** `Authorization: Bearer {token}`

**Response 200:**
```json
{ "message": "Logout realizado com sucesso." }
```

---

### GET `/me` — Dados do usuário logado

**Headers:** `Authorization: Bearer {token}`

**Response 200:**
```json
{
  "id": 5,
  "nome": "João Silva",
  "email": "tecnico@empresa.com",
  "nivel_acesso": 1
}
```

---

## 2. Catálogos — Listas de seleção

> Esses endpoints retornam as listas que o app usa para popular dropdowns e selects.  
> **Todos os campos de texto são somente leitura — apenas para exibição.**  
> Apenas os campos de ID devem ser enviados de volta nas operações de gravação.

---

### GET `/catalogos/mao-obra` — Lista mão de obra disponível

**Headers:** `Authorization: Bearer {token}`

**Response 200:**
```json
{
  "data": [
    {
      "ocup_id": 2,
      "descricao": "Eletricista",
      "tipo_id": 1,
      "tipo": "Mão de Obra Direta"
    },
    {
      "ocup_id": 3,
      "descricao": "Encanador",
      "tipo_id": 1,
      "tipo": "Mão de Obra Direta"
    }
  ]
}
```

| Campo      | Uso                                                        |
|------------|------------------------------------------------------------|
| `ocup_id`  | Envie este ID ao adicionar mão de obra no relatório        |
| `descricao`| **[somente leitura]** — exiba no dropdown                  |
| `tipo_id`  | Pode usar para agrupar visualmente                         |
| `tipo`     | **[somente leitura]** — exiba como cabeçalho do grupo      |

**Como usar para adicionar ao relatório:**
```
POST /relatorios/{id}/mao-obra
Body: { "ocup_id": 2, "qtd": 3 }
```

---

### GET `/catalogos/ferramentas` — Lista ferramentas disponíveis

**Response 200:**
```json
{
  "data": [
    { "equip_id": 5, "descricao": "Multímetro" },
    { "equip_id": 6, "descricao": "Furadeira" }
  ]
}
```

| Campo      | Uso                                                      |
|------------|----------------------------------------------------------|
| `equip_id` | Envie este ID ao adicionar ferramenta no relatório       |
| `descricao`| **[somente leitura]** — exiba no dropdown                |

**Como usar para adicionar ao relatório:**
```
POST /relatorios/{id}/equipamentos
Body: { "equip_id": 5, "qtd": 2 }
```

---

### GET `/catalogos/ocorrencias` — Lista ocorrências disponíveis

**Response 200:**
```json
{
  "data": [
    { "ocorrencia_id": 3, "descricao": "Falta de material" },
    { "ocorrencia_id": 4, "descricao": "Chuva / mau tempo" }
  ]
}
```

| Campo          | Uso                                                        |
|----------------|------------------------------------------------------------|
| `ocorrencia_id`| Envie este ID ao adicionar ocorrência no relatório         |
| `descricao`    | **[somente leitura]** — exiba no dropdown                  |

**Como usar para adicionar ao relatório:**
```
POST /relatorios/{id}/ocorrencias
Body: { "ocorrencia_id": 3, "observacao": "Aguardando entrega" }
```

---

## 3. Atendimentos

> Técnico vê somente seus atendimentos. Admin vê todos.

### GET `/atendimentos` — Listar

**Query params (opcionais):**
| Param    | Tipo   | Descrição                                                    |
|----------|--------|--------------------------------------------------------------|
| `status` | int    | `0`=Não iniciada `1`=Paralisada `2`=Em andamento `3`=Concluída |
| `search` | string | Busca em descrição e nome do cliente                         |

**Response 200:**
```json
{
  "data": [
    {
      "id": 12,
      "descricao": "Instalação elétrica Bloco A",
      "responsavel": "João Silva",
      "endereco": "Rua das Flores, 100",
      "nr_proposta": "PRO-2024-001",
      "status": 2,
      "status_label": "Em andamento",
      "dt_inicio": "2024-01-10",
      "dt_fim": "2024-03-10",
      "natureza":  { "id": 3, "descricao": "Elétrica" },
      "setor":     { "id": 1, "descricao": "Manutenção" },
      "cliente":   { "id": 7, "nome": "Construtora XYZ" },
      "tecnico":   { "id": 5, "nome": "João Silva" }
    }
  ]
}
```

> Todos os campos de texto aqui são **[somente leitura]** — apenas para exibição.  
> O `id` do atendimento é usado ao criar um relatório.

---

### GET `/atendimentos/{id}` — Detalhe

Inclui a lista de equipamentos do atendimento (os que foram cadastrados no atendimento, não no relatório).

**Response 200:**
```json
{
  "data": {
    "id": 12,
    "descricao": "Instalação elétrica Bloco A",
    "responsavel": "João Silva",
    "endereco": "Rua das Flores, 100",
    "nr_proposta": "PRO-2024-001",
    "status": 2,
    "status_label": "Em andamento",
    "dt_inicio": "2024-01-10",
    "dt_fim": "2024-03-10",
    "natureza":  { "id": 3, "descricao": "Elétrica" },
    "setor":     { "id": 1, "descricao": "Manutenção" },
    "cliente":   { "id": 7, "nome": "Construtora XYZ" },
    "tecnico":   { "id": 5, "nome": "João Silva" },
    "equipamentos": [
      { "id": 1, "descricao": "Furadeira", "observacoes": null }
    ]
  }
}
```

**Response 403:** Técnico tentou acessar atendimento de outro técnico.

---

## 4. Relatórios — Geral

### GET `/relatorios` — Listar

> Técnico vê somente relatórios dos seus atendimentos.

**Query params (opcionais):**
| Param            | Tipo | Descrição                                      |
|------------------|------|------------------------------------------------|
| `atendimento_id` | int  | Filtra por atendimento específico              |
| `status`         | int  | `0`=Preenchendo `1`=Revisar `2`=Aprovado       |

**Response 200:**
```json
{
  "data": [
    {
      "id": 8,
      "data": "2024-02-15",
      "status": 0,
      "status_label": "Preenchendo",
      "atendimento_id": 12,
      "obra":    "Instalação elétrica Bloco A",
      "natureza":"Elétrica",
      "setor":   "Manutenção",
      "cliente": "Construtora XYZ"
    }
  ]
}
```

---

### POST `/relatorios` — Criar novo relatório

**Body:**
```json
{
  "aten_id": 12,
  "aten_rel_data": "2024-02-15"
}
```

| Campo          | Obrigatório | Descrição                         |
|----------------|-------------|-----------------------------------|
| `aten_id`      | Sim         | ID do atendimento                 |
| `aten_rel_data`| Não         | Data do relatório (padrão: hoje)  |

**Response 201:**
```json
{
  "message": "Relatório criado com sucesso.",
  "data": { "id": 8 }
}
```

---

### GET `/relatorios/{id}` — Detalhe completo

Retorna todos os dados do relatório. Chame este endpoint ao abrir a tela de preenchimento.

**Response 200:**
```json
{
  "data": {
    "id": 8,
    "status": 0,

    "dados": {
      "aten_rel_data":   "2024-02-15",
      "prazo_total":     59,
      "prazo_decorrido": 36,
      "prazo_vencer":    23
    },

    "atendimento": {
      "id":          12,
      "descricao":   "Instalação elétrica Bloco A",
      "responsavel": "João Silva",
      "endereco":    "Rua das Flores, 100",
      "dt_inicio":   "2024-01-10",
      "dt_fim":      "2024-03-10",
      "cliente":     "Construtora XYZ",
      "natureza":    "Elétrica",
      "setor":       "Manutenção"
    },

    "horarios": {
      "entrada":          "07:00",
      "inicio_intervalo": "12:00",
      "fim_intervalo":    "13:00",
      "saida":            "17:00"
    },

    "clima": {
      "manha": "ensolarado",
      "tarde": "nublado",
      "noite": null
    },

    "mao_obra": [
      {
        "ocup_id":  2,
        "descricao":"Eletricista",
        "tipo":     "Mão de Obra Direta",
        "qtd":      3
      }
    ],

    "equipamentos": [
      { "equip_id": 5, "descricao": "Multímetro", "qtd": 2 }
    ],

    "atividades": [
      { "id": 1, "descricao": "Instalar quadro de distribuição", "status": 1 }
    ],

    "ocorrencias": [
      {
        "ocorrencia_id": 3,
        "descricao":     "Falta de material",
        "observacao":    "Aguardando entrega"
      }
    ],

    "comentarios": [
      { "id": 1, "descricao": "Trabalho iniciado conforme cronograma." }
    ],

    "assinaturas": {
      "responsavel": "https://dominio.com/storage/.../responsavel.png",
      "cliente": null
    }
  }
}
```

> Todos os campos de texto dentro de `atendimento`, `mao_obra`, `equipamentos`, `ocorrencias` são **[somente leitura]** — apenas para exibição.

---

## 5. Relatórios — Horários

### POST `/relatorios/{id}/horarios`

**Body:**
```json
{
  "entrada":          "07:00",
  "inicio_intervalo": "12:00",
  "fim_intervalo":    "13:00",
  "saida":            "17:00"
}
```

Todos os campos são opcionais. Formato obrigatório: `"HH:MM"`.

**Response 200:**
```json
{ "message": "Horários atualizados com sucesso." }
```

---

## 6. Relatórios — Clima

### POST `/relatorios/{id}/clima`

**Body:**
```json
{
  "manha": "ensolarado",
  "tarde": "nublado",
  "noite": "chuvoso"
}
```

Valores aceitos: `"ensolarado"`, `"nublado"`, `"chuvoso"`. Todos os campos são opcionais.

**Response 200:**
```json
{ "message": "Clima atualizado com sucesso." }
```

---

## 7. Relatórios — Mão de Obra

> Antes de adicionar, consulte `GET /catalogos/mao-obra` para obter os IDs disponíveis.

### POST `/relatorios/{id}/mao-obra` — Adicionar

**Body:**
```json
{ "ocup_id": 2, "qtd": 3 }
```

| Campo     | Tipo | Descrição                                              |
|-----------|------|--------------------------------------------------------|
| `ocup_id` | int  | ID obtido em `GET /catalogos/mao-obra`                 |
| `qtd`     | int  | Quantidade (mínimo 1)                                  |

**Response 200:**
```json
{
  "message": "Mão de obra adicionada!",
  "data": {
    "ocup_id":  2,
    "descricao":"Eletricista",
    "tipo":     "Mão de Obra Direta",
    "qtd":      3
  }
}
```

> `descricao` e `tipo` no response são **[somente leitura]** — apenas para confirmar visualmente o que foi adicionado.

**Response 422:** Mão de obra já adicionada neste relatório.

---

### DELETE `/relatorios/{id}/mao-obra/{ocup_id}` — Remover

**Response 200:**
```json
{ "message": "Mão de obra removida!" }
```

---

## 8. Relatórios — Ferramentas/Equipamentos

> Antes de adicionar, consulte `GET /catalogos/ferramentas` para obter os IDs disponíveis.

### POST `/relatorios/{id}/equipamentos` — Adicionar

**Body:**
```json
{ "equip_id": 5, "qtd": 2 }
```

| Campo      | Tipo | Descrição                                            |
|------------|------|------------------------------------------------------|
| `equip_id` | int  | ID obtido em `GET /catalogos/ferramentas`            |
| `qtd`      | int  | Quantidade (mínimo 1)                                |

**Response 200:**
```json
{
  "message": "Equipamento adicionado!",
  "data": { "equip_id": 5, "descricao": "Multímetro", "qtd": 2 }
}
```

> `descricao` no response é **[somente leitura]** — apenas confirmação visual.

---

### DELETE `/relatorios/{id}/equipamentos/{equip_id}` — Remover

**Response 200:**
```json
{ "message": "Equipamento removido!" }
```

---

## 9. Relatórios — Atividades

> Atividades são textos livres digitados pelo técnico — não vêm de catálogo.

### POST `/relatorios/{id}/atividades` — Adicionar

**Body:**
```json
{ "descricao": "Instalar quadro de distribuição", "status": 0 }
```

| Campo      | Tipo   | Descrição                        |
|------------|--------|----------------------------------|
| `descricao`| string | Texto livre (máx. 500 caracteres)|
| `status`   | int    | `0` = Pendente / `1` = Concluída |

**Response 200:**
```json
{
  "message": "Atividade adicionada!",
  "data": { "id": 1, "descricao": "Instalar quadro de distribuição", "status": 0 }
}
```

---

### PUT `/relatorios/{id}/atividades/{ativ_id}` — Atualizar

Mesmo body do POST. Útil para marcar como concluída (`status: 1`).

**Response 200:**
```json
{
  "message": "Atividade atualizada!",
  "data": { "id": 1, "descricao": "Instalar quadro de distribuição", "status": 1 }
}
```

---

### DELETE `/relatorios/{id}/atividades/{ativ_id}` — Remover

**Response 200:**
```json
{ "message": "Atividade removida!" }
```

---

## 10. Relatórios — Ocorrências

> Antes de adicionar, consulte `GET /catalogos/ocorrencias` para obter os IDs disponíveis.

### POST `/relatorios/{id}/ocorrencias` — Adicionar

**Body:**
```json
{
  "ocorrencia_id": 3,
  "observacao": "Aguardando entrega do fornecedor"
}
```

| Campo          | Tipo   | Obrigatório | Descrição                                      |
|----------------|--------|-------------|------------------------------------------------|
| `ocorrencia_id`| int    | Sim         | ID obtido em `GET /catalogos/ocorrencias`      |
| `observacao`   | string | Não         | Texto livre de observação sobre a ocorrência   |

**Response 200:**
```json
{
  "message": "Ocorrência adicionada!",
  "data": {
    "ocorrencia_id": 3,
    "descricao":     "Falta de material",
    "observacao":    "Aguardando entrega do fornecedor"
  }
}
```

> `descricao` no response é **[somente leitura]** — apenas confirmação visual.

**Response 422:** Ocorrência já adicionada neste relatório.

---

### DELETE `/relatorios/{id}/ocorrencias/{ocorrencia_id}` — Remover

**Response 200:**
```json
{ "message": "Ocorrência removida!" }
```

---

## 11. Relatórios — Comentários

> Comentários são textos livres digitados pelo técnico.

### POST `/relatorios/{id}/comentarios` — Adicionar

**Body:**
```json
{ "descricao": "Trabalho iniciado conforme cronograma." }
```

**Response 200:**
```json
{
  "message": "Comentário adicionado!",
  "data": { "id": 1, "descricao": "Trabalho iniciado conforme cronograma." }
}
```

---

### DELETE `/relatorios/{id}/comentarios/{com_id}` — Remover

**Response 200:**
```json
{ "message": "Comentário removido!" }
```

---

## 12. Relatórios — Assinaturas

### POST `/relatorios/{id}/assinaturas`

Salva assinaturas em base64 e atualiza o status do relatório.

**Body:**
```json
{
  "status": 2,
  "assinatura_responsavel": "data:image/png;base64,iVBORw0KGgo...",
  "assinatura_cliente":     "data:image/png;base64,iVBORw0KGgo..."
}
```

| Campo                    | Tipo   | Obrigatório | Descrição                              |
|--------------------------|--------|-------------|----------------------------------------|
| `status`                 | int    | Sim         | `0`=Preenchendo `1`=Revisar `2`=Aprovado |
| `assinatura_responsavel` | string | Não         | Imagem PNG em base64 com prefixo data URI |
| `assinatura_cliente`     | string | Não         | Imagem PNG em base64 com prefixo data URI |

**Response 200:**
```json
{
  "message": "Assinaturas salvas com sucesso.",
  "data": {
    "assinaturas": {
      "responsavel": "https://dominio.com/storage/.../responsavel.png",
      "cliente":     "https://dominio.com/storage/.../cliente.png"
    }
  }
}
```

---

## 13. Relatórios — Anexos, Fotos e Vídeos

### POST `/relatorios/{id}/anexos` — Upload

Content-Type: `multipart/form-data`

| Campo       | Limite | Formatos aceitos                         |
|-------------|--------|------------------------------------------|
| `fotos[]`   | 10 MB  | jpg, jpeg, png, webp, gif                |
| `videos[]`  | 100 MB | mp4, mov, avi, mkv, webm                 |
| `arquivos[]`| 20 MB  | pdf, doc, docx, xls, xlsx, txt, csv     |

Pode enviar múltiplos arquivos por campo. Todos os campos são opcionais.

**Response 200:**
```json
{
  "message": "Uploads processados com sucesso.",
  "data": {
    "fotos":    [{ "id": 3, "url": "https://dominio.com/storage/...jpg" }],
    "videos":   [{ "id": 1, "url": "https://dominio.com/storage/...mp4" }],
    "arquivos": [{ "id": 2, "url": "https://dominio.com/storage/...pdf" }]
  }
}
```

---

### DELETE `/relatorios/{id}/anexos/{tipo}/{item_id}` — Remover

`tipo` deve ser: `foto`, `video` ou `arquivo`

**Exemplo:** `DELETE /api/v1/relatorios/8/anexos/foto/3`

**Response 200:**
```json
{ "message": "Anexo removido!" }
```

---

## 14. Erros Comuns

| Código | Significado                                           |
|--------|-------------------------------------------------------|
| 401    | Token inválido ou ausente                             |
| 403    | Técnico tentou acessar dado de outro técnico          |
| 404    | Recurso não encontrado                                |
| 422    | Validação falhou — veja o campo `errors` no body      |
| 500    | Erro interno do servidor                              |

**Estrutura de erro de validação (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "campo": ["Mensagem de erro."]
  }
}
```

---

## 15. Guia Flutter

### Configuração inicial (`pubspec.yaml`)

```yaml
dependencies:
  http: ^1.2.0
  shared_preferences: ^2.2.0
```

---

### Serviço de API (`lib/services/api_service.dart`)

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = 'http://SEU_DOMINIO/api/v1';

  // ── Token ─────────────────────────────────────────────────────────────

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('api_token');
  }

  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('api_token', token);
  }

  static Future<void> removeToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('api_token');
  }

  // ── Headers ───────────────────────────────────────────────────────────

  static Future<Map<String, String>> _headers() async {
    final token = await getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  // ── Auth ──────────────────────────────────────────────────────────────

  static Future<Map<String, dynamic>> login(String email, String senha) async {
    final response = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({'email': email, 'senha': senha, 'device_name': 'flutter_app'}),
    );
    final data = jsonDecode(response.body);
    if (response.statusCode == 200) await saveToken(data['token']);
    return data;
  }

  static Future<void> logout() async {
    await http.post(Uri.parse('$baseUrl/logout'), headers: await _headers());
    await removeToken();
  }

  // ── Catálogos (listas de seleção) ─────────────────────────────────────
  // Use estes métodos para popular dropdowns no app.
  // Os campos de texto retornados são apenas para exibição.
  // Apenas os IDs são enviados de volta ao gravar.

  static Future<List<dynamic>> getMaoObra() async {
    final response = await http.get(
      Uri.parse('$baseUrl/catalogos/mao-obra'),
      headers: await _headers(),
    );
    return jsonDecode(response.body)['data'];
    // Retorna: [{ ocup_id, descricao [exibição], tipo_id, tipo [exibição] }]
  }

  static Future<List<dynamic>> getFerramentas() async {
    final response = await http.get(
      Uri.parse('$baseUrl/catalogos/ferramentas'),
      headers: await _headers(),
    );
    return jsonDecode(response.body)['data'];
    // Retorna: [{ equip_id, descricao [exibição] }]
  }

  static Future<List<dynamic>> getOcorrencias() async {
    final response = await http.get(
      Uri.parse('$baseUrl/catalogos/ocorrencias'),
      headers: await _headers(),
    );
    return jsonDecode(response.body)['data'];
    // Retorna: [{ ocorrencia_id, descricao [exibição] }]
  }

  // ── Atendimentos ──────────────────────────────────────────────────────

  static Future<List<dynamic>> getAtendimentos({int? status, String? search}) async {
    final params = <String, String>{};
    if (status != null) params['status'] = status.toString();
    if (search != null && search.isNotEmpty) params['search'] = search;

    final uri = Uri.parse('$baseUrl/atendimentos').replace(queryParameters: params);
    final response = await http.get(uri, headers: await _headers());
    return jsonDecode(response.body)['data'];
  }

  static Future<Map<String, dynamic>> getAtendimento(int id) async {
    final response = await http.get(
      Uri.parse('$baseUrl/atendimentos/$id'),
      headers: await _headers(),
    );
    return jsonDecode(response.body)['data'];
  }

  // ── Relatórios ────────────────────────────────────────────────────────

  static Future<List<dynamic>> getRelatorios({int? atendimentoId, int? status}) async {
    final params = <String, String>{};
    if (atendimentoId != null) params['atendimento_id'] = atendimentoId.toString();
    if (status != null) params['status'] = status.toString();

    final uri = Uri.parse('$baseUrl/relatorios').replace(queryParameters: params);
    final response = await http.get(uri, headers: await _headers());
    return jsonDecode(response.body)['data'];
  }

  static Future<Map<String, dynamic>> getRelatorio(int id) async {
    final response = await http.get(
      Uri.parse('$baseUrl/relatorios/$id'),
      headers: await _headers(),
    );
    return jsonDecode(response.body)['data'];
  }

  static Future<Map<String, dynamic>> criarRelatorio(int atendimentoId, {String? data}) async {
    final body = <String, dynamic>{'aten_id': atendimentoId};
    if (data != null) body['aten_rel_data'] = data;
    final response = await http.post(
      Uri.parse('$baseUrl/relatorios'),
      headers: await _headers(),
      body: jsonEncode(body),
    );
    return jsonDecode(response.body);
  }

  // ── Seções do relatório ───────────────────────────────────────────────

  static Future<void> updateHorarios(int relId, Map<String, String?> horarios) async {
    await http.post(
      Uri.parse('$baseUrl/relatorios/$relId/horarios'),
      headers: await _headers(),
      body: jsonEncode(horarios),
    );
  }

  static Future<void> updateClima(int relId, Map<String, String?> clima) async {
    await http.post(
      Uri.parse('$baseUrl/relatorios/$relId/clima'),
      headers: await _headers(),
      body: jsonEncode(clima),
    );
  }

  // ── Mão de obra ───────────────────────────────────────────────────────

  static Future<void> addMaoObra(int relId, int ocupId, int qtd) async {
    await http.post(
      Uri.parse('$baseUrl/relatorios/$relId/mao-obra'),
      headers: await _headers(),
      body: jsonEncode({'ocup_id': ocupId, 'qtd': qtd}),
      // ocupId vem de getMaoObra() → campo ocup_id
    );
  }

  static Future<void> removeMaoObra(int relId, int ocupId) async {
    await http.delete(
      Uri.parse('$baseUrl/relatorios/$relId/mao-obra/$ocupId'),
      headers: await _headers(),
    );
  }

  // ── Ferramentas ───────────────────────────────────────────────────────

  static Future<void> addFerramenta(int relId, int equipId, int qtd) async {
    await http.post(
      Uri.parse('$baseUrl/relatorios/$relId/equipamentos'),
      headers: await _headers(),
      body: jsonEncode({'equip_id': equipId, 'qtd': qtd}),
      // equipId vem de getFerramentas() → campo equip_id
    );
  }

  static Future<void> removeFerramenta(int relId, int equipId) async {
    await http.delete(
      Uri.parse('$baseUrl/relatorios/$relId/equipamentos/$equipId'),
      headers: await _headers(),
    );
  }

  // ── Ocorrências ───────────────────────────────────────────────────────

  static Future<void> addOcorrencia(int relId, int ocorrenciaId, {String observacao = ''}) async {
    await http.post(
      Uri.parse('$baseUrl/relatorios/$relId/ocorrencias'),
      headers: await _headers(),
      body: jsonEncode({'ocorrencia_id': ocorrenciaId, 'observacao': observacao}),
      // ocorrenciaId vem de getOcorrencias() → campo ocorrencia_id
    );
  }

  static Future<void> removeOcorrencia(int relId, int ocorrenciaId) async {
    await http.delete(
      Uri.parse('$baseUrl/relatorios/$relId/ocorrencias/$ocorrenciaId'),
      headers: await _headers(),
    );
  }

  // ── Atividades ────────────────────────────────────────────────────────

  static Future<Map<String, dynamic>> addAtividade(int relId, String descricao, int status) async {
    final response = await http.post(
      Uri.parse('$baseUrl/relatorios/$relId/atividades'),
      headers: await _headers(),
      body: jsonEncode({'descricao': descricao, 'status': status}),
    );
    return jsonDecode(response.body);
  }

  static Future<Map<String, dynamic>> updateAtividade(int relId, int ativId, String descricao, int status) async {
    final response = await http.put(
      Uri.parse('$baseUrl/relatorios/$relId/atividades/$ativId'),
      headers: await _headers(),
      body: jsonEncode({'descricao': descricao, 'status': status}),
    );
    return jsonDecode(response.body);
  }

  static Future<void> deleteAtividade(int relId, int ativId) async {
    await http.delete(
      Uri.parse('$baseUrl/relatorios/$relId/atividades/$ativId'),
      headers: await _headers(),
    );
  }

  // ── Comentários ───────────────────────────────────────────────────────

  static Future<void> addComentario(int relId, String descricao) async {
    await http.post(
      Uri.parse('$baseUrl/relatorios/$relId/comentarios'),
      headers: await _headers(),
      body: jsonEncode({'descricao': descricao}),
    );
  }

  static Future<void> deleteComentario(int relId, int comId) async {
    await http.delete(
      Uri.parse('$baseUrl/relatorios/$relId/comentarios/$comId'),
      headers: await _headers(),
    );
  }

  // ── Assinaturas ───────────────────────────────────────────────────────

  static Future<void> updateAssinaturas(int relId, int status, {
    String? assinaturaResponsavel,
    String? assinaturaCliente,
  }) async {
    final body = <String, dynamic>{'status': status};
    if (assinaturaResponsavel != null) body['assinatura_responsavel'] = assinaturaResponsavel;
    if (assinaturaCliente != null) body['assinatura_cliente'] = assinaturaCliente;
    await http.post(
      Uri.parse('$baseUrl/relatorios/$relId/assinaturas'),
      headers: await _headers(),
      body: jsonEncode(body),
    );
  }

  // ── Uploads ───────────────────────────────────────────────────────────

  static Future<Map<String, dynamic>> uploadAnexos(int relId, {
    List<String> fotoPaths = const [],
    List<String> videoPaths = const [],
    List<String> arquivoPaths = const [],
  }) async {
    final token = await getToken();
    final request = http.MultipartRequest('POST', Uri.parse('$baseUrl/relatorios/$relId/anexos'));
    request.headers['Authorization'] = 'Bearer $token';
    request.headers['Accept'] = 'application/json';
    for (final p in fotoPaths)    request.files.add(await http.MultipartFile.fromPath('fotos[]', p));
    for (final p in videoPaths)   request.files.add(await http.MultipartFile.fromPath('videos[]', p));
    for (final p in arquivoPaths) request.files.add(await http.MultipartFile.fromPath('arquivos[]', p));
    final streamed = await request.send();
    final response = await http.Response.fromStream(streamed);
    return jsonDecode(response.body);
  }

  static Future<void> deleteAnexo(int relId, String tipo, int itemId) async {
    await http.delete(
      Uri.parse('$baseUrl/relatorios/$relId/anexos/$tipo/$itemId'),
      headers: await _headers(),
    );
  }
}
```

---

### Fluxo recomendado ao abrir a tela de preenchimento

```dart
// 1. Carregue os catálogos uma vez (pode cachear localmente)
final maoObra    = await ApiService.getMaoObra();
final ferramentas= await ApiService.getFerramentas();
final ocorrencias= await ApiService.getOcorrencias();

// 2. Carregue os dados já salvos no relatório
final relatorio  = await ApiService.getRelatorio(relatorioId);

// 3. Use os catálogos para popular dropdowns
// Exiba: item['descricao']   ← somente leitura
// Envie: item['ocup_id']     ← ao gravar
```

---

### Exemplo — Adicionar mão de obra

```dart
// Usuário selecionou "Eletricista" no dropdown
final selecionado = maoObra.firstWhere((m) => m['descricao'] == 'Eletricista');

await ApiService.addMaoObra(
  relatorioId,
  selecionado['ocup_id'],  // ← envia só o ID
  3,                        // ← quantidade
);
```

---

### Observações importantes

1. **Salve o token** em `SharedPreferences` após o login e leia-o antes de cada requisição.
2. **Intercepte o código 401** globalmente para redirecionar ao login quando o token expirar.
3. **Catálogos**: carregue uma vez ao abrir a tela e reutilize — eles raramente mudam. Pode cachear por sessão.
4. **Campos de texto dos catálogos** são sempre somente leitura. Nunca envie texto de volta — apenas IDs.
5. **Assinaturas**: use o pacote `signature` do pub.dev para capturar e exporte como PNG em base64 com o prefixo `data:image/png;base64,`.
6. **HTTPS em produção**: configure SSL no servidor antes de publicar o app.
