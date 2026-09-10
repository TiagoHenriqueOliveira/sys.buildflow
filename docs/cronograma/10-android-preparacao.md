# Sessão Android — Preparação

Ver [00-indice.md](00-indice.md). Pré-requisito: todo o Web concluído ([09-web-atendimento-persistencia.md](09-web-atendimento-persistencia.md)) — o app mobile consome as APIs `fae/v1` que o Web expõe, e CRM09 reaproveita diretamente os conceitos de CRM01/05/06/07.

**Projeto:** `C:\Users\tiago\StudioProjects\app_buildflow_fae` (branch `feature/fae`, Flutter, v1.7.1+42). Fork do app MCL — hoje só 2 commits específicos da FAÉ (branding/config de backend); todo o resto é herdado do MCL.

## Entregáveis

### Dependências novas no `pubspec.yaml`
- Pacote de geolocalização: `geolocator` (ou equivalente) — necessário para BF01 (captura de coordenadas) e para os mapas (BF08/CRM07).
- Pacote de mapa: `google_maps_flutter` (ou equivalente) — nenhum dos dois pacotes existe hoje no projeto.
- Verificar se as chaves de API do Google Maps já existem para o ambiente FAÉ (Android Manifest / config) — se não, sinalizar como pendência de configuração de ambiente, não de código.

### Campo de perfil/role no modelo de usuário
- `lib/core/models/usuario_model.dart` hoje só tem `id`, `nome`, `email`, `nivelAcesso` (int, `isAdmin => nivelAcesso >= 2`) — **sem** campo de perfil/role em string.
- Adicionar campo de perfil (ex.: `perfil: 'tecnico' | 'comercial' | 'administrador'`, ou reaproveitar `nivelAcesso` com um novo valor, espelhando a decisão tomada na Sessão 03-web-nucleo-persistencia para BF02) — pré-requisito direto de BF02 e CRM09 no app.
- Ajustar `AuthProvider` (`lib/core/auth/auth_provider.dart`) e a API de login/me (`lib/core/api/api_service.dart`) para carregar esse campo do backend.

## Não faz parte desta sessão

Nenhuma tela nova ainda — só ajuste de dependências e do modelo de dados de usuário, para as sessões seguintes já terem o terreno pronto.

## Próxima sessão

[11-android-nucleo-telas.md](11-android-nucleo-telas.md)
