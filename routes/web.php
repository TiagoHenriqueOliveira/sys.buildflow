<?php

use App\Http\Controllers\AtendimentosController;
use App\Http\Controllers\AtendimentosRelatoriosController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogsAuditoriaController;
use App\Http\Controllers\ModelosRelatoriosController;
use App\Http\Controllers\NaturezasAtendimentosController;
use App\Http\Controllers\OcorrenciasController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\UsuariosController;
use Illuminate\Support\Facades\Route;

// Rotas públicas (sem autenticação)
Route::controller(AuthController::class)->middleware('guest')->group(function () {
    Route::get('/', 'mostrarLogin')->name('login');
    Route::post('/login', 'login')->name('login.post');
});

// Serve os arquivos gravados em public/storage (disco 'public', ver
// config/filesystems.php). O acesso estático direto pelo Apache a essa pasta
// é bloqueado por public/storage/.htaccess (Require all denied), então toda
// requisição passa por aqui e exige autenticação: sessão web (painel, via
// cookie automático no <img>) OU token Sanctum (app mobile, via header
// Authorization: Bearer) — sem isso, assinatura e foto de qualquer cliente
// eram enumeráveis por ID sequencial sem login.
Route::middleware('auth:web,sanctum')->get('/storage/{path}', [StorageController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.show');

// Rotas protegidas
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::controller(DashboardController::class)->prefix('dashboard')->group(function () {
        Route::get('/',                    'index');
        Route::get('/data/kpis',           'kpis');
        Route::get('/data/por-status',     'porStatus');
        Route::get('/data/evolucao',       'evolucao');
        Route::get('/data/mais-relatorios','maisRelatorios');
        Route::get('/data/por-estado',     'porEstado');
        Route::get('/data/por-tecnico',    'porTecnico');
        Route::get('/data/por-setor',      'porSetor');
        Route::get('/data/tempo-medio',    'tempoMedio');
    });

    // Clientes — autocomplete
    Route::get('/clientes/autocomplete', [ClientesController::class, 'autoComplete'])->name('clientes.autocomplete');

    // Atendimentos — leitura disponível para todos os usuários autenticados
    Route::get('/atendimentos', [AtendimentosController::class, 'index'])->name('atendimentos.index');
    Route::get('/atendimentos/{id}/equipamentos', [AtendimentosController::class, 'getEquipamentos'])->name('atendimentos.get-equipamentos');

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
    Route::get('/atendimentos-relatorios/{id}/pdf', [AtendimentosRelatoriosController::class, 'pdf'])->name('atendimentos-relatorios.pdf');
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
    Route::post('/atendimentos-relatorios/{id}/upload-anexos', [AtendimentosRelatoriosController::class, 'uploadAnexos'])->name('atendimentos-relatorios.upload-anexos');
    Route::get('/atendimentos-relatorios/{id}/anexos', [AtendimentosRelatoriosController::class, 'getAnexos'])->name('atendimentos-relatorios.get-anexos');
    Route::delete('/atendimentos-relatorios/{id}/anexos/{type}/{itemId}', [AtendimentosRelatoriosController::class, 'destroyAnexo'])->name('atendimentos-relatorios.destroy-anexo');

    // Somente administradores
    Route::middleware('admin')->group(function () {
        // Atendimentos — mutações restritas a administradores
        Route::post('/atendimentos', [AtendimentosController::class, 'store'])->name('atendimentos.store');
        Route::put('/atendimentos/{atendimento}', [AtendimentosController::class, 'update'])->name('atendimentos.update');
        Route::patch('/atendimentos/{atendimento}', [AtendimentosController::class, 'update']);
        Route::post('/atendimentos/{id}/equipamentos', [AtendimentosController::class, 'storeEquipamento'])->name('atendimentos.store-equipamentos');
        Route::delete('/atendimentos/{id}/equipamentos/{equipId}', [AtendimentosController::class, 'destroyEquipamento'])->name('atendimentos.destroy-equipamentos');

        // Clientes
        Route::resource('clientes', ClientesController::class)->except(['create', 'edit', 'show', 'destroy']);

        // Ocorrências
        Route::get('/ocorrencias/autocomplete', [OcorrenciasController::class, 'autoComplete'])->name('ocorrencias.autocomplete');
        Route::resource('ocorrencias', OcorrenciasController::class)->except(['create', 'edit', 'show', 'destroy']);

        // Configurações
        Route::resource('modelos-de-relatorios', ModelosRelatoriosController::class)->except(['create', 'edit', 'show', 'destroy']);
        Route::resource('naturezas-dos-atendimentos', NaturezasAtendimentosController::class)->except(['create', 'edit', 'show', 'destroy']);

        // Usuários
        Route::resource('usuarios', UsuariosController::class)->except(['create', 'edit', 'show', 'destroy']);

        // Logs de Auditoria
        Route::get('/logs-auditoria', [LogsAuditoriaController::class, 'index'])->name('logs-auditoria.index');
    });

});
