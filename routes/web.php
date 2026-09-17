<?php

use App\Http\Controllers\AppUpdateController;
use App\Http\Controllers\AtendimentosController;
use App\Http\Controllers\AtendimentosRelatoriosController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ConfiguradorModelosController;
use App\Http\Controllers\ConfiguradorPerguntasController;
use App\Http\Controllers\CrmTiposOrcamentoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IndicadoresComerciaisController;
use App\Http\Controllers\LogsAuditoriaController;
use App\Http\Controllers\MapaDemandasController;
use App\Http\Controllers\MapaRelacoesController;
use App\Http\Controllers\NaturezasAtendimentosController;
use App\Http\Controllers\NotificacoesController;
use App\Http\Controllers\ClassificacoesClienteController;
use App\Http\Controllers\SegmentosController;
use App\Http\Controllers\OcorrenciasController;
use App\Http\Controllers\OrcamentosController;
use App\Http\Controllers\RoteirosViagemController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\UsuariosController;
use Illuminate\Support\Facades\Route;

// Rotas públicas (sem autenticação)
Route::controller(AuthController::class)->middleware('guest')->group(function () {
    Route::get('/', 'mostrarLogin')->name('login');
    // Mesmo limiter 'login' já usado pela API (5 tentativas/min por
    // IP+email, ver RouteServiceProvider) — antes só a API tinha proteção
    // contra força bruta de senha, o painel web ficava sem nenhuma.
    Route::post('/login', 'login')->name('login.post')->middleware('throttle:login');
});

// Serve os arquivos gravados em public/midia (disco 'public', ver
// config/filesystems.php). Chamamos a pasta de "midia" (não "storage")
// porque este Plesk bloqueia no servidor qualquer pasta literalmente
// chamada "storage", fora do alcance do .htaccess. O acesso estático
// direto pelo Apache a essa pasta é bloqueado por public/midia/.htaccess
// (Require all denied), então toda requisição passa por aqui e exige
// autenticação: sessão web (painel, via cookie automático no <img>) OU
// token Sanctum (app mobile, via header Authorization: Bearer) — sem isso,
// assinatura e foto de qualquer cliente eram enumeráveis por ID sem login.
Route::middleware('auth:web,sanctum')->get('/midia/{path}', [StorageController::class, 'show'])
    ->where('path', '.*')
    ->name('midia.show');

// Instalador do app móvel (.apk) — pública de propósito, ver AppUpdateController.
// Fica fora de public/ (storage/app/apks/), mesmo motivo do disco 'public':
// este Plesk bloqueia no servidor qualquer pasta com arquivo real dentro de
// public/, então o download precisa passar pelo Laravel.
Route::get('/apk/{arquivo}', [AppUpdateController::class, 'baixar'])
    ->where('arquivo', '.*\.apk$')
    ->name('apk.baixar');

// PDF do relatório (RF003/RF005) — mesmo motivo do /midia acima: precisa
// aceitar tanto sessão web (painel) quanto token Sanctum (app), para o
// técnico poder baixar/abrir o PDF autenticado só com o app, sem sessão no
// painel. Fica fora do grupo `auth` geral (só sessão web) por isso. A
// checagem de que o relatório pertence ao usuário fica dentro do próprio
// AtendimentosRelatoriosController::pdf() (RNF004).
Route::middleware('auth:web,sanctum')
    ->get('/atendimentos-relatorios/{id}/pdf', [AtendimentosRelatoriosController::class, 'pdf'])
    ->name('atendimentos-relatorios.pdf');

// Rotas protegidas
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard (placeholder minimo por ora - ver DashboardController)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Notificacoes (sino do topbar) - disponivel pra qualquer usuario
    // autenticado, sem restricao de perfil (o proprio conteudo ja e
    // filtrado pelo usuario logado dentro do controller).
    Route::get('/notificacoes', [NotificacoesController::class, 'index'])->name('notificacoes.index');
    Route::post('/notificacoes/{id}/marcar-lida', [NotificacoesController::class, 'marcarLida'])->name('notificacoes.marcar-lida');
    Route::post('/notificacoes/{id}/marcar-nao-lida', [NotificacoesController::class, 'marcarNaoLida'])->name('notificacoes.marcar-nao-lida');

    // Clientes — autocomplete e resumo (leitura disponível para todos os
    // usuários autenticados: usados no formulário de Atendimento por
    // técnicos, que não têm acesso ao CRUD completo de Clientes — BF03).
    Route::get('/clientes/autocomplete', [ClientesController::class, 'autoComplete'])->name('clientes.autocomplete');
    Route::get('/clientes/{cliente}/resumo', [ClientesController::class, 'resumo'])->name('clientes.resumo');

    // Clientes — CRUD completo. Pedido do cliente (2026-09-16): aberto a
    // todos os perfis exceto Tecnico (Administrador/Comercial/Assistencia/
    // Vendedor) — por isso fica FORA do grupo "comercial" abaixo, que
    // continua restrito a Administrador/Comercial pro resto do CRM.
    Route::middleware('nao-tecnico')->group(function () {
        Route::resource('clientes', ClientesController::class)->except(['show', 'destroy']);
    });

    // Orçamentos, Roteiro de Viagem, Mapa de Relações, Indicadores —
    // restritos a Administrador/Comercial (BF02).
    Route::middleware('comercial')->group(function () {
        // Orçamentos (CRM02/03/04)
        Route::resource('orcamentos', OrcamentosController::class)->except(['show', 'destroy']);
        Route::post('/orcamentos/{id}/comentarios', [OrcamentosController::class, 'storeComentario'])->name('orcamentos.store-comentario');
        Route::delete('/orcamentos/{id}/comentarios/{comentarioId}', [OrcamentosController::class, 'destroyComentario'])->name('orcamentos.destroy-comentario');

        // Roteiro de viagem (CRM05/06) — saída (clientes a visitar) e retorno.
        Route::resource('roteiros-viagem', RoteirosViagemController::class)->except(['show', 'destroy']);

        // Mapa de relações de clientes (CRM07) — somente leitura.
        Route::get('/mapa-relacoes', [MapaRelacoesController::class, 'index'])->name('mapa-relacoes.index');

        // Indicadores comerciais (CRM08) — somente leitura.
        Route::get('/indicadores-comerciais', [IndicadoresComerciaisController::class, 'index'])->name('indicadores-comerciais.index');
    });

    // Atendimentos — leitura disponível para todos os usuários autenticados
    Route::get('/atendimentos', [AtendimentosController::class, 'index'])->name('atendimentos.index');
    Route::get('/atendimentos/{id}/equipamentos', [AtendimentosController::class, 'getEquipamentos'])->name('atendimentos.get-equipamentos');
    Route::get('/atendimentos/{id}/relatorios', [AtendimentosController::class, 'getRelatorios'])->name('atendimentos.get-relatorios');

    // Atendimentos — Observações e Anexos (disponíveis para todos autenticados)
    Route::get('/atendimentos/{id}/observacoes', [AtendimentosController::class, 'getObservacoes'])->name('atendimentos.get-observacoes');
    Route::post('/atendimentos/{id}/observacoes', [AtendimentosController::class, 'updateObservacoes'])->name('atendimentos.update-observacoes');
    Route::get('/atendimentos/{id}/anexos', [AtendimentosController::class, 'getAnexos'])->name('atendimentos.get-anexos');
    Route::post('/atendimentos/{id}/upload-anexos', [AtendimentosController::class, 'uploadAnexos'])->name('atendimentos.upload-anexos');
    Route::delete('/atendimentos/{id}/anexos/{itemId}', [AtendimentosController::class, 'destroyAnexo'])->name('atendimentos.destroy-anexo');

    // Atendimentos Relatórios
    Route::get('/atendimentos-relatorios/autocomplete', [AtendimentosRelatoriosController::class, 'autoComplete'])->name('atendimentos_relatorios.autocomplete');
    Route::resource('atendimentos-relatorios', AtendimentosRelatoriosController::class)->except(['create', 'edit', 'destroy']);
    Route::get('/atendimentos-relatorios/{id}/dados', [AtendimentosRelatoriosController::class, 'getDados'])->name('atendimentos-relatorios.get-dados');
    Route::get('/atendimentos-relatorios/{id}/horarios', [AtendimentosRelatoriosController::class, 'getHorarios'])->name('atendimentos-relatorios.get-horarios');
    Route::get('/atendimentos-relatorios/{id}/clima', [AtendimentosRelatoriosController::class, 'getClimaData'])->name('atendimentos-relatorios.get-clima');
    Route::get('/atendimentos-relatorios/{id}/ocorrencias', [AtendimentosRelatoriosController::class, 'getOcorrenciasData'])->name('atendimentos-relatorios.get-ocorrencias');
    Route::get('/atendimentos-relatorios/{id}/assinaturas', [AtendimentosRelatoriosController::class, 'getAssinaturasData'])->name('atendimentos-relatorios.get-assinaturas');
    // Rota do PDF movida para fora deste grupo — ver auth:web,sanctum acima (RF005).
    Route::post('/atendimentos-relatorios/{id}/dados', [AtendimentosRelatoriosController::class, 'updateDados'])->name('atendimentos-relatorios.update-dados');
    Route::post('/atendimentos-relatorios/{id}/horarios', [AtendimentosRelatoriosController::class, 'updateHorarios'])->name('atendimentos-relatorios.update-horarios');
    Route::post('/atendimentos-relatorios/{id}/clima', [AtendimentosRelatoriosController::class, 'updateClima'])->name('atendimentos-relatorios.update-clima');
    Route::post('/atendimentos-relatorios/{id}/assinaturas', [AtendimentosRelatoriosController::class, 'updateAssinaturas'])->name('atendimentos-relatorios.update-assinaturas');
    Route::post('/atendimentos-relatorios/{id}/texto/{campo}', [AtendimentosRelatoriosController::class, 'updateTexto'])->name('atendimentos-relatorios.update-texto');
    Route::post('/atendimentos-relatorios/{id}/ocorrencias', [AtendimentosRelatoriosController::class, 'storeOcorrencia'])->name('atendimentos-relatorios.store-ocorrencia');
    Route::delete('/atendimentos-relatorios/{id}/ocorrencias/{ocorrenciaId}', [AtendimentosRelatoriosController::class, 'destroyOcorrencia'])->name('atendimentos-relatorios.destroy-ocorrencia');
    Route::get('/atendimentos-relatorios/{id}/servicos', [AtendimentosRelatoriosController::class, 'getServicos'])->name('atendimentos-relatorios.get-servicos');
    Route::post('/atendimentos-relatorios/{id}/servicos', [AtendimentosRelatoriosController::class, 'storeServico'])->name('atendimentos-relatorios.store-servico');
    Route::delete('/atendimentos-relatorios/{id}/servicos/{itemId}', [AtendimentosRelatoriosController::class, 'destroyServico'])->name('atendimentos-relatorios.destroy-servico');
    Route::get('/atendimentos-relatorios/{id}/pecas', [AtendimentosRelatoriosController::class, 'getPecas'])->name('atendimentos-relatorios.get-pecas');
    Route::post('/atendimentos-relatorios/{id}/pecas', [AtendimentosRelatoriosController::class, 'storePeca'])->name('atendimentos-relatorios.store-peca');
    Route::delete('/atendimentos-relatorios/{id}/pecas/{itemId}', [AtendimentosRelatoriosController::class, 'destroyPeca'])->name('atendimentos-relatorios.destroy-peca');
    Route::get('/atendimentos-relatorios/{id}/descricao-itens', [AtendimentosRelatoriosController::class, 'getDescricaoItens'])->name('atendimentos-relatorios.get-descricao-itens');
    Route::post('/atendimentos-relatorios/{id}/descricao-itens', [AtendimentosRelatoriosController::class, 'storeDescricaoItem'])->name('atendimentos-relatorios.store-descricao-item');
    Route::delete('/atendimentos-relatorios/{id}/descricao-itens/{itemId}', [AtendimentosRelatoriosController::class, 'destroyDescricaoItem'])->name('atendimentos-relatorios.destroy-descricao-item');
    Route::post('/atendimentos-relatorios/{id}/upload-anexos', [AtendimentosRelatoriosController::class, 'uploadAnexos'])->name('atendimentos-relatorios.upload-anexos');
    Route::get('/atendimentos-relatorios/{id}/anexos', [AtendimentosRelatoriosController::class, 'getAnexos'])->name('atendimentos-relatorios.get-anexos');
    Route::delete('/atendimentos-relatorios/{id}/anexos/{type}/{itemId}', [AtendimentosRelatoriosController::class, 'destroyAnexo'])->name('atendimentos-relatorios.destroy-anexo');

    // Sessao 08 - perguntas dinamicas do Configurador (NC02/NC03) e
    // comprovante de compartilhamento (BF07).
    Route::get('/atendimentos-relatorios/{id}/respostas', [AtendimentosRelatoriosController::class, 'getRespostas'])->name('atendimentos-relatorios.get-respostas');
    Route::post('/atendimentos-relatorios/{id}/respostas', [AtendimentosRelatoriosController::class, 'storeResposta'])->name('atendimentos-relatorios.store-resposta');
    Route::delete('/atendimentos-relatorios/{id}/respostas/{respostaId}', [AtendimentosRelatoriosController::class, 'destroyResposta'])->name('atendimentos-relatorios.destroy-resposta');
    Route::delete('/atendimentos-relatorios/{id}/respostas-fotos/{fotoId}', [AtendimentosRelatoriosController::class, 'destroyRespostaFoto'])->name('atendimentos-relatorios.destroy-resposta-foto');
    Route::get('/atendimentos-relatorios/{id}/compartilhamentos', [AtendimentosRelatoriosController::class, 'getCompartilhamentos'])->name('atendimentos-relatorios.get-compartilhamentos');
    Route::post('/atendimentos-relatorios/{id}/compartilhamentos', [AtendimentosRelatoriosController::class, 'storeCompartilhamento'])->name('atendimentos-relatorios.store-compartilhamento');

    // Mapa de demandas (BF08) - mesma visibilidade de atendimentos-relatorios.index (tecnico ve so o seu, admin ve tudo).
    Route::get('/mapa-demandas', [MapaDemandasController::class, 'index'])->name('mapa-demandas.index');

    // NC02/NC03 — rotas de resposta às perguntas do Configurador dentro do
    // relatório ficam para a sessão 08 (Atendimento/Assistência), junto da
    // reformulação completa da tela (Configurador substitui modelos_relatorios
    // — ver memória do projeto). As tabelas já existem
    // (atendimentos_relatorios_respostas[_fotos]), só a tela/endpoints não.

    // Somente administradores
    Route::middleware('admin')->group(function () {
        // Atendimentos — mutações restritas a administradores
        Route::get('/atendimentos/create', [AtendimentosController::class, 'create'])->name('atendimentos.create');
        Route::get('/atendimentos/{id}/edit', [AtendimentosController::class, 'edit'])->name('atendimentos.edit');
        Route::post('/atendimentos', [AtendimentosController::class, 'store'])->name('atendimentos.store');
        Route::put('/atendimentos/{atendimento}', [AtendimentosController::class, 'update'])->name('atendimentos.update');
        Route::patch('/atendimentos/{atendimento}', [AtendimentosController::class, 'update']);
        Route::post('/atendimentos/{id}/equipamentos', [AtendimentosController::class, 'storeEquipamento'])->name('atendimentos.store-equipamentos');
        Route::delete('/atendimentos/{id}/equipamentos/{equipId}', [AtendimentosController::class, 'destroyEquipamento'])->name('atendimentos.destroy-equipamentos');

        // Ocorrências
        Route::get('/ocorrencias/autocomplete', [OcorrenciasController::class, 'autoComplete'])->name('ocorrencias.autocomplete');
        Route::resource('ocorrencias', OcorrenciasController::class)->except(['create', 'edit', 'show', 'destroy']);

        // Configurações
        Route::resource('naturezas-dos-atendimentos', NaturezasAtendimentosController::class)->except(['create', 'edit', 'show', 'destroy']);

        // NC01 (pendencia #3) — opcoes de classificacao de cliente
        Route::resource('classificacoes-cliente', ClassificacoesClienteController::class)->except(['create', 'edit', 'show', 'destroy']);
        Route::resource('segmentos', SegmentosController::class)->except(['create', 'edit', 'show', 'destroy']);

        // CRM01 — tipos de sistema de orçamento
        Route::resource('crm/tipos-orcamento', CrmTiposOrcamentoController::class)
            ->except(['create', 'edit', 'show', 'destroy'])
            ->parameter('tipos-orcamento', 'id')
            ->names('crm.tipos-orcamento');

        // Configurador (NC02) — perguntas e modelos reutilizáveis
        Route::get('configurador/perguntas/autocomplete', [ConfiguradorPerguntasController::class, 'autoComplete'])->name('configurador.perguntas.autocomplete');
        Route::resource('configurador/perguntas', ConfiguradorPerguntasController::class)
            ->except(['create', 'edit', 'show', 'destroy'])
            ->parameter('perguntas', 'id')
            ->names('configurador.perguntas');
        Route::resource('configurador/modelos', ConfiguradorModelosController::class)
            ->except(['create', 'edit', 'show', 'destroy'])
            ->parameter('modelos', 'id')
            ->names('configurador.modelos');

        // Usuários
        Route::resource('usuarios', UsuariosController::class)->except(['create', 'edit', 'show', 'destroy']);

        // Logs de Auditoria
        Route::get('/logs-auditoria', [LogsAuditoriaController::class, 'index'])->name('logs-auditoria.index');

        // Manutenção — roda o comando midia:diagnosticar sem precisar de SSH/console
        // (?fix=1 aplica a correção; sem o parâmetro, só relata)
        Route::get('/admin/midia/diagnosticar', function (\Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Artisan::call('midia:diagnosticar', $request->boolean('fix') ? ['--fix' => true] : []);
            return response(\Illuminate\Support\Facades\Artisan::output())
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        })->name('admin.midia.diagnosticar');

        // Manutenção — roda o comando midia:remover-bom sem precisar de SSH/console
        // (?fix=1 aplica a correção; sem o parâmetro, só relata)
        Route::get('/admin/midia/remover-bom', function (\Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Artisan::call('midia:remover-bom', $request->boolean('fix') ? ['--fix' => true] : []);
            return response(\Illuminate\Support\Facades\Artisan::output())
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        })->name('admin.midia.remover-bom');

        // Manutenção — roda o comando codigo:remover-bom sem precisar de SSH/console
        // (?fix=1 aplica a correção; sem o parâmetro, só relata)
        Route::get('/admin/codigo/remover-bom', function (\Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Artisan::call('codigo:remover-bom', $request->boolean('fix') ? ['--fix' => true] : []);
            return response(\Illuminate\Support\Facades\Artisan::output())
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        })->name('admin.codigo.remover-bom');
    });

});