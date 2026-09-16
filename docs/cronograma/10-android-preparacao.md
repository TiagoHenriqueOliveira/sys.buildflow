# Sessão Android — Preparação

Ver [00-indice.md](00-indice.md). Pré-requisito: Web concluído nos 4 blocos de Telas (feito, `BF_v1.8.5`). **Esta sessão trabalha em DOIS repositórios**: `app_buildflow_fae` (Flutter, foco principal) e `sys-buildflow-fae` (Laravel, só pra confirmar os padrões que os endpoints novos das próximas sessões vão seguir — não cria endpoint nenhum ainda, isso começa na sessão 11).

**Projeto principal:** `C:\Users\tiago\StudioProjects\app_buildflow_fae` (branch `feature/fae`, Flutter, v1.7.1+42). Fork do app MCL — ainda 100% o fluxo herdado (login, atendimentos, relatório com seções fixas em `lib/features/relatorios/sections/*`), sem nenhuma tela de Cliente/Configurador/CRM.

## Entregáveis

### 1. Corrigir o modelo de perfil de usuário
`lib/core/models/usuario_model.dart` hoje só tem `nivelAcesso` (int) com `isAdmin => nivelAcesso >= 2` — **esse getter está errado** para o `NivelAcesso` real do backend FAE (`app/Enums/NivelAcesso.php`, em `sys-buildflow-fae`): `Administrador=0, Tecnico=1, Comercial=2, Assistencia=3, Vendedor=4`. Com a regra atual, Comercial/Assistência/Vendedor cairiam como "admin", o que é falso.
- Trocar por getters explícitos por nível (`isAdministrador`, `isTecnico`, `isComercial`, `isAssistencia`, `isVendedor`) ou um enum Dart espelhando o do backend.
- Confirmar que o endpoint `/me` (`AuthController::me`, `sys-buildflow-fae`) já devolve `user_nivel_acesso` — se sim, só ajustar o parse no Flutter; se faltar algum campo, listar aqui antes de seguir pra sessão 11 (não adivinhar).
- Ajustar `AuthProvider` (`lib/core/auth/auth_provider.dart`) pra expor esses getters ao resto do app (gating de menu nas sessões seguintes).

### 2. Dependência de geolocalização
- Adicionar `geolocator` (ou equivalente) ao `pubspec.yaml` — cobre BF01 ("Usar minha localização" no cadastro de Cliente e no Roteiro de Viagem, sessões 11/15). **Não é preciso pacote de mapa embutido pra isso** — o Web decidiu (BF_v1.8.2, pedido explícito do cliente) remover o picker/busca de mapa e usar só GPS + link do Google Maps colado à mão, aberto externamente. Replicar exatamente esse padrão no app: capturar lat/lng com `geolocator`, guardar/mostrar o link do Maps, abrir com `url_launcher` (já é dependência do projeto).
- **Pacote de mapa (`google_maps_flutter`/`flutter_map`) só entra se a sessão 15 (Mapa de Relações, CRM07) decidir por exibição embutida** — no Web esse mapa é Leaflet somente-leitura com vários pontos. Não fixar essa dependência agora; confirmar com o usuário na sessão 15 antes (custo de configurar chave de API do Google Maps para Android, se for esse o pacote escolhido).

### 3. Levantar o padrão de API a seguir (leitura, sem criar nada ainda)
As próximas sessões vão criar endpoints novos em `sys-buildflow-fae`. Antes de codar, ler:
- `routes/api.php`, grupo `fae/v1` (a partir da linha ~154) — convenção de prefixo, middleware `auth:sanctum`, nomes de rota.
- Um controller `Api\Fae\*` existente (ex.: `FaeRelatoriosController`) — para replicar o padrão de namespace/estrutura de resposta JSON.
- `app/Http/Controllers/AtendimentosRelatoriosController::getRespostas()` e `ConfigModelo::perguntasAgrupadasPorSessao()` (ambos em `sys-buildflow-fae`) — é a estrutura de dados que os endpoints novos de formulário dinâmico (sessão 13) vão expor; só entender o formato aqui, implementar lá.

## Não faz parte desta sessão
Nenhuma tela nova, nenhum endpoint novo — só ajuste de dependências/modelo de dados no Flutter e reconhecimento do padrão de API a seguir, para as sessões seguintes começarem sem re-descobrir isso.

## Critérios de aceite a verificar
- `flutter pub get` resolve sem conflito com `geolocator` adicionado.
- Getters de perfil no `UsuarioModel`/`AuthProvider` batem com os 5 valores reais de `NivelAcesso`.

## Próxima sessão
[11-android-nucleo-telas.md](11-android-nucleo-telas.md)