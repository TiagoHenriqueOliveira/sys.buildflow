# BuildFlow — Documentação da API REST

> Versão: **v1**  
> Base URL: `http://SEU_DOMINIO/api/v1`  
> Autenticação: **Bearer Token** (Laravel Sanctum)  
> Content-Type: `application/json` (exceto uploads, que usam `multipart/form-data`)

---

## Índice

1. [Autenticação](#1-autenticação)
2. [Atendimentos](#2-atendimentos)
3. [Relatórios — Geral](#3-relatórios--geral)
4. [Relatórios — Horários](#4-relatórios--horários)
5. [Relatórios — Clima](#5-relatórios--clima)
6. [Relatórios — Mão de Obra](#6-relatórios--mão-de-obra)
7. [Relatórios — Equipamentos](#7-relatórios--equipamentos)
8. [Relatórios — Atividades](#8-relatórios--atividades)
9. [Relatórios — Comentários](#9-relatórios--comentários)
10. [Relatórios — Assinaturas](#10-relatórios--assinaturas)
11. [Relatórios — Anexos, Fotos e Vídeos](#11-relatórios--anexos-fotos-e-vídeos)
12. [Erros Comuns](#12-erros-comuns)
13. [Guia Flutter](#13-guia-flutter)

---

## 1. Autenticação

### POST `/login` — Login

Não requer token. Retorna o Bearer token que deve ser incluído em todas as demais requisições.

**Request:**
```json
{
  "email": "tecnico@empresa.com",
  "senha": "minha_senha",
  "device_name": "flutter_app"   // opcional — identifica o dispositivo
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

> `nivel_acesso: 1` = Técnico (vê apenas seus próprios dados)  
> `nivel_acesso: 2+` = Administrador (vê todos os dados)

**Response 422 — Credenciais inválidas:**
```json
{
  "message": "The given data was invalid.",
  "errors": { "email": ["Credenciais inválidas."] }
}
```

---

### POST `/logout` — Logout

Requer token. Revoga o token atual.

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

## 2. Atendimentos

> Técnico vê somente seus atendimentos. Admin vê todos.

### GET `/atendimentos` — Listar

**Headers:** `Authorization: Bearer {token}`

**Query params (opcionais):**
| Param    | Tipo   | Descrição                       |
|----------|--------|---------------------------------|
| `status` | int    | 0=Não iniciada, 1=Paralisada, 2=Em andamento, 3=Concluída |
| `search` | string | Busca em descrição e nome do cliente |

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
      "natureza": { "id": 3, "descricao": "Elétrica" },
      "setor":    { "id": 1, "descricao": "Manutenção" },
      "cliente":  { "id": 7, "nome": "Construtora XYZ" },
      "tecnico":  { "id": 5, "nome": "João Silva" }
    }
  ]
}
```

---

### GET `/atendimentos/{id}` — Detalhe

Inclui lista de equipamentos do atendimento.

**Response 200:**
```json
{
  "data": {
    "id": 12,
    "descricao": "Instalação elétrica Bloco A",
    ...
    "equipamentos": [
      { "id": 1, "descricao": "Furadeira", "observacoes": null }
    ]
  }
}
```

**Response 403:** Técnico tentou acessar atendimento de outro técnico.

---

## 3. Relatórios — Geral

### GET `/relatorios` — Listar

> Técnico vê somente relatórios dos seus atendimentos.

**Query params (opcionais):**
| Param            | Tipo | Descrição                      |
|------------------|------|--------------------------------|
| `atendimento_id` | int  | Filtra por atendimento         |
| `status`         | int  | 0=Preenchendo, 1=Revisar, 2=Aprovado |

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
      "obra": "Instalação elétrica Bloco A",
      "natureza": "Elétrica",
      "setor": "Manutenção",
      "cliente": "Construtora XYZ"
    }
  ]
}
```

---

### POST `/relatorios` — Criar

**Body:**
```json
{
  "aten_id": 12,
  "aten_rel_data": "2024-02-15"   // opcional, padrão = hoje
}
```

**Response 201:**
```json
{
  "message": "Relatório criado com sucesso.",
  "data": { "id": 8 }
}
```

**Response 422:** Atendimento sem modelo de relatório vinculado na natureza.

---

### GET `/relatorios/{id}` — Detalhe completo

Retorna todos os dados do relatório (horários, clima, atividades, etc.).

**Response 200:**
```json
{
  "data": {
    "id": 8,
    "status": 0,
    "dados": {
      "aten_rel_data": "2024-02-15",
      "prazo_total": 59,
      "prazo_decorrido": 36,
      "prazo_vencer": 23
    },
    "atendimento": {
      "id": 12,
      "descricao": "Instalação elétrica Bloco A",
      "responsavel": "João Silva",
      "endereco": "Rua das Flores, 100",
      "dt_inicio": "2024-01-10",
      "dt_fim": "2024-03-10",
      "cliente": "Construtora XYZ",
      "natureza": "Elétrica",
      "setor": "Manutenção"
    },
    "horarios": {
      "entrada": "07:00",
      "inicio_intervalo": "12:00",
      "fim_intervalo": "13:00",
      "saida": "17:00"
    },
    "clima": {
      "manha": "ensolarado",
      "tarde": "nublado",
      "noite": null
    },
    "mao_obra": [
      { "ocup_id": 2, "descricao": "Eletricista", "tipo": "Mão de Obra Direta", "qtd": 3 }
    ],
    "equipamentos": [
      { "equip_id": 5, "descricao": "Multímetro", "qtd": 2 }
    ],
    "atividades": [
      { "id": 1, "descricao": "Instalar quadro de distribuição", "status": 1 }
    ],
    "ocorrencias": [
      { "ocorrencia_id": 3, "descricao": "Falta de material", "observacao": "Aguardando entrega" }
    ],
    "comentarios": [
      { "id": 1, "descricao": "Trabalho iniciado conforme cronograma." }
    ],
    "assinaturas": {
      "responsavel": "https://dominio.com/storage/atendimentos_relatorios/8/assinaturas/responsavel.png",
      "cliente": null
    }
  }
}
```

---

## 4. Relatórios — Horários

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

Todos os campos são opcionais (null limpa o valor). Formato: `"HH:MM"`.

**Response 200:**
```json
{ "message": "Horários atualizados com sucesso." }
```

---

## 5. Relatórios — Clima

### POST `/relatorios/{id}/clima`

**Body:**
```json
{
  "manha": "ensolarado",
  "tarde": "nublado",
  "noite": "chuvoso"
}
```

Valores aceitos: `"ensolarado"`, `"nublado"`, `"chuvoso"`. Campos opcionais.

**Response 200:**
```json
{ "message": "Clima atualizado com sucesso." }
```

---

## 6. Relatórios — Mão de Obra

### POST `/relatorios/{id}/mao-obra`

**Body:**
```json
{ "ocup_id": 2, "qtd": 3 }
```

**Response 200:**
```json
{
  "message": "Mão de obra adicionada!",
  "data": { "ocup_id": 2, "descricao": "Eletricista", "tipo": "Mão de Obra Direta", "qtd": 3 }
}
```

**Response 422:** Mão de obra já adicionada neste relatório.

---

### DELETE `/relatorios/{id}/mao-obra/{ocup_id}`

**Response 200:**
```json
{ "message": "Mão de obra removida!" }
```

---

## 7. Relatórios — Equipamentos

### POST `/relatorios/{id}/equipamentos`

**Body:**
```json
{ "equip_id": 5, "qtd": 2 }
```

**Response 200:**
```json
{
  "message": "Equipamento adicionado!",
  "data": { "equip_id": 5, "descricao": "Multímetro", "qtd": 2 }
}
```

---

### DELETE `/relatorios/{id}/equipamentos/{equip_id}`

**Response 200:**
```json
{ "message": "Equipamento removido!" }
```

---

## 8. Relatórios — Atividades

### POST `/relatorios/{id}/atividades`

**Body:**
```json
{ "descricao": "Instalar quadro de distribuição", "status": 0 }
```
`status`: `0` = Pendente, `1` = Concluída

**Response 200:**
```json
{
  "message": "Atividade adicionada!",
  "data": { "id": 1, "descricao": "Instalar quadro de distribuição", "status": 0 }
}
```

---

### PUT `/relatorios/{id}/atividades/{ativ_id}`

**Body:**
```json
{ "descricao": "Instalar quadro de distribuição", "status": 1 }
```

**Response 200:**
```json
{
  "message": "Atividade atualizada!",
  "data": { "id": 1, "descricao": "Instalar quadro de distribuição", "status": 1 }
}
```

---

### DELETE `/relatorios/{id}/atividades/{ativ_id}`

**Response 200:**
```json
{ "message": "Atividade removida!" }
```

---

## 9. Relatórios — Comentários

### POST `/relatorios/{id}/comentarios`

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

### DELETE `/relatorios/{id}/comentarios/{com_id}`

**Response 200:**
```json
{ "message": "Comentário removido!" }
```

---

## 10. Relatórios — Assinaturas

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

`status`: `0` = Preenchendo, `1` = Revisar, `2` = Aprovado  
As assinaturas são opcionais — envie apenas as que foram coletadas.

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

## 11. Relatórios — Anexos, Fotos e Vídeos

### POST `/relatorios/{id}/anexos` — Upload

Content-Type: `multipart/form-data`

| Campo       | Tipo    | Limite | Formatos aceitos                       |
|-------------|---------|--------|----------------------------------------|
| `fotos[]`   | arquivo | 10 MB  | jpg, jpeg, png, webp, gif              |
| `videos[]`  | arquivo | 100 MB | mp4, mov, avi, mkv, webm               |
| `arquivos[]`| arquivo | 20 MB  | pdf, doc, docx, xls, xlsx, txt, csv   |

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

### DELETE `/relatorios/{id}/anexos/{tipo}/{item_id}`

`tipo` deve ser: `foto`, `video` ou `arquivo`

**Exemplo:** `DELETE /api/v1/relatorios/8/anexos/foto/3`

**Response 200:**
```json
{ "message": "Anexo removido!" }
```

---

## 12. Erros Comuns

| Código | Significado                                      |
|--------|--------------------------------------------------|
| 401    | Token inválido ou ausente                        |
| 403    | Técnico tentou acessar dado de outro técnico     |
| 404    | Recurso não encontrado                           |
| 422    | Validação falhou (ver campo `errors` no body)    |
| 500    | Erro interno do servidor                         |

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

## 13. Guia Flutter

### Configuração inicial

Adicione no `pubspec.yaml`:
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
      body: jsonEncode({
        'email': email,
        'senha': senha,
        'device_name': 'flutter_app',
      }),
    );
    final data = jsonDecode(response.body);
    if (response.statusCode == 200) {
      await saveToken(data['token']);
    }
    return data;
  }

  static Future<void> logout() async {
    final headers = await _headers();
    await http.post(Uri.parse('$baseUrl/logout'), headers: headers);
    await removeToken();
  }

  // ── Atendimentos ──────────────────────────────────────────────────────

  static Future<List<dynamic>> getAtendimentos({int? status, String? search}) async {
    final params = <String, String>{};
    if (status != null) params['status'] = status.toString();
    if (search != null && search.isNotEmpty) params['search'] = search;

    final uri = Uri.parse('$baseUrl/atendimentos').replace(queryParameters: params);
    final response = await http.get(uri, headers: await _headers());
    final data = jsonDecode(response.body);
    return data['data'] as List<dynamic>;
  }

  static Future<Map<String, dynamic>> getAtendimento(int id) async {
    final response = await http.get(
      Uri.parse('$baseUrl/atendimentos/$id'),
      headers: await _headers(),
    );
    final data = jsonDecode(response.body);
    return data['data'] as Map<String, dynamic>;
  }

  // ── Relatórios ────────────────────────────────────────────────────────

  static Future<List<dynamic>> getRelatorios({int? atendimentoId, int? status}) async {
    final params = <String, String>{};
    if (atendimentoId != null) params['atendimento_id'] = atendimentoId.toString();
    if (status != null) params['status'] = status.toString();

    final uri = Uri.parse('$baseUrl/relatorios').replace(queryParameters: params);
    final response = await http.get(uri, headers: await _headers());
    final data = jsonDecode(response.body);
    return data['data'] as List<dynamic>;
  }

  static Future<Map<String, dynamic>> getRelatorio(int id) async {
    final response = await http.get(
      Uri.parse('$baseUrl/relatorios/$id'),
      headers: await _headers(),
    );
    final data = jsonDecode(response.body);
    return data['data'] as Map<String, dynamic>;
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

  // ── Upload de arquivos ────────────────────────────────────────────────

  static Future<Map<String, dynamic>> uploadAnexos(int relId, {
    List<String> fotoPaths = const [],
    List<String> videoPaths = const [],
    List<String> arquivoPaths = const [],
  }) async {
    final token = await getToken();
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('$baseUrl/relatorios/$relId/anexos'),
    );
    request.headers['Authorization'] = 'Bearer $token';
    request.headers['Accept'] = 'application/json';

    for (final path in fotoPaths) {
      request.files.add(await http.MultipartFile.fromPath('fotos[]', path));
    }
    for (final path in videoPaths) {
      request.files.add(await http.MultipartFile.fromPath('videos[]', path));
    }
    for (final path in arquivoPaths) {
      request.files.add(await http.MultipartFile.fromPath('arquivos[]', path));
    }

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);
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

### Exemplo de uso — Login

```dart
final resultado = await ApiService.login('joao@empresa.com', 'senha123');
if (resultado['token'] != null) {
  // Token salvo automaticamente. Navegar para tela principal.
  print('Bem-vindo, ${resultado['usuario']['nome']}');
} else {
  print('Erro: ${resultado['message']}');
}
```

---

### Exemplo de uso — Listar atendimentos

```dart
final atendimentos = await ApiService.getAtendimentos(status: 2); // Em andamento
for (final a in atendimentos) {
  print('${a['id']} — ${a['descricao']} — ${a['cliente']['nome']}');
}
```

---

### Exemplo de uso — Criar relatório

```dart
final resultado = await ApiService.criarRelatorio(
  12,                  // atendimento_id
  data: '2024-02-15', // opcional
);
if (resultado['data'] != null) {
  final relatorioId = resultado['data']['id'];
  // Navegar para tela de preenchimento
}
```

---

### Exemplo de uso — Upload de foto

```dart
// Usando image_picker para capturar foto
final picker = ImagePicker();
final file = await picker.pickImage(source: ImageSource.camera);

if (file != null) {
  final resultado = await ApiService.uploadAnexos(
    8,  // relatorio_id
    fotoPaths: [file.path],
  );
  print(resultado['data']['fotos']); // [{ id: 3, url: "..." }]
}
```

---

### Observações importantes para o app

1. **Salve o token** em `SharedPreferences` logo após o login e leia-o antes de cada requisição.
2. **Intercepte o código 401** globalmente para redirecionar ao login quando o token expirar.
3. **Content-Type correto**: JSON para todos os endpoints exceto upload de arquivos, que usa `multipart/form-data`.
4. **Assinaturas**: use o pacote `signature` do pub.dev para capturar a assinatura como imagem e converta para base64 com `dart:convert`.
5. **Offline**: considere armazenar os dados dos atendimentos localmente (ex: `sqflite`) para funcionar sem internet, sincronizando ao reconectar.
