<?php

use App\Http\Controllers\AtendimentosController;
use App\Http\Controllers\AtendimentosRelatoriosController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipamentosController;
use App\Http\Controllers\ModelosRelatoriosController;
use App\Http\Controllers\NaturezasAtendimentosController;
use App\Http\Controllers\OcorrenciasController;
use App\Http\Controllers\OcupacoesController;
use App\Http\Controllers\TiposAtendimentosController;
use App\Http\Controllers\TiposOcupacoesController;
use App\Http\Controllers\UsuariosController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::get('/', 'mostrarLogin')->name('login');
    Route::post('/login', 'login')->name('login.post');
    Route::post('/logout', 'logout')->name('logout');
});

// Dashboard
Route::controller(DashboardController::class)->group(function () {
    Route::get('/dashboard', 'index');
});
// Atendimentos
Route::resource('atendimentos', AtendimentosController::class)->except(['create', 'edit', 'show', 'destroy']);
Route::get('/atendimentos/autocomplete', [AtendimentosController::class, 'autoComplete'])->name('atendimentos.autocomplete');
Route::get('/atendimentos/naturezas-por-tipo', [AtendimentosController::class, 'naturezasPorTipo'])->name('atendimentos.naturezas_por_tipo');
// Atendimentos Relatórios
Route::resource('atendimentos-relatorios', AtendimentosRelatoriosController::class)->except(['create', 'edit', 'destroy']);
Route::get('/atendimentos-relatorios/autocomplete', [AtendimentosRelatoriosController::class, 'autoComplete'])->name('atendimentos_relatorios.autocomplete');
Route::get('/atendimentos-relatorios/{id}/get-data', [AtendimentosRelatoriosController::class, 'getData'])->name('atendimentos-relatorios.get-data');
Route::post('/atendimentos-relatorios/{id}/dados', [AtendimentosRelatoriosController::class, 'updateDados'])->name('atendimentos-relatorios.update-dados');
Route::post('/atendimentos-relatorios/{id}/horarios', [AtendimentosRelatoriosController::class, 'updateHorarios'])->name('atendimentos-relatorios.update-horarios');
Route::post('/atendimentos-relatorios/{id}/clima', [AtendimentosRelatoriosController::class, 'updateClima'])->name('atendimentos-relatorios.update-clima');
Route::post('/atendimentos-relatorios/{id}/mao-de-obra', [AtendimentosRelatoriosController::class, 'storeMaoObra'])->name('atendimentos-relatorios.store-mao-obra');
Route::delete('/atendimentos-relatorios/{id}/mao-de-obra/{maoObraId}', [AtendimentosRelatoriosController::class, 'destroyMaoObra'])->name('atendimentos-relatorios.destroy-mao-obra');
Route::post('/atendimentos-relatorios/{id}/equipamentos', [AtendimentosRelatoriosController::class, 'storeEquipamento'])->name('atendimentos-relatorios.store-equipamentos');
Route::delete('/atendimentos-relatorios/{id}/equipamentos/{equipId}', [AtendimentosRelatoriosController::class, 'destroyEquipamento'])->name('atendimentos-relatorios.destroy-equipamentos');
Route::post('/atendimentos-relatorios/{id}/atividades', [AtendimentosRelatoriosController::class, 'storeAtividade'])->name('atendimentos-relatorios.store-atividade');
Route::post('/atendimentos-relatorios/{id}/atividades/{ativId}', [AtendimentosRelatoriosController::class, 'updateAtividade'])->name('atendimentos-relatorios.update-atividade');
Route::delete('/atendimentos-relatorios/{id}/atividades/{ativId}', [AtendimentosRelatoriosController::class, 'destroyAtividade'])->name('atendimentos-relatorios.destroy-atividade');
Route::post('/atendimentos-relatorios/{id}/ocorrencias', [AtendimentosRelatoriosController::class, 'storeOcorrencia'])->name('atendimentos-relatorios.store-ocorrencia');
Route::delete('/atendimentos-relatorios/{id}/ocorrencias/{ocorrenciaId}', [AtendimentosRelatoriosController::class, 'destroyOcorrencia'])->name('atendimentos-relatorios.destroy-ocorrencia');
Route::post('/atendimentos-relatorios/{id}/comentarios', [AtendimentosRelatoriosController::class, 'storeComentario'])->name('atendimentos-relatorios.store-comentario');
Route::post('/atendimentos-relatorios/{id}/comentarios/{comentarioId}', [AtendimentosRelatoriosController::class, 'updateComentario'])->name('atendimentos-relatorios.update-comentario');
Route::delete('/atendimentos-relatorios/{id}/comentarios/{comentarioId}', [AtendimentosRelatoriosController::class, 'destroyComentario'])->name('atendimentos-relatorios.destroy-comentario');
// Clientes
Route::resource('clientes', ClientesController::class)->except(['create', 'edit', 'show', 'destroy']);
// Equipamentos
Route::resource('equipamentos', EquipamentosController::class)->except(['create', 'edit', 'show', 'destroy']);
Route::get('/equipamentos/autocomplete', [EquipamentosController::class, 'autoComplete'])->name('equipamentos.autocomplete');
// Modelos de Relatórios
Route::resource('modelos-de-relatorios', ModelosRelatoriosController::class)->except(['create', 'edit', 'show', 'destroy']);
// Natureza dos Atendimentos
Route::resource('naturezas-dos-atendimentos', NaturezasAtendimentosController::class)->except(['create', 'edit', 'show', 'destroy']);
// Ocorrências
Route::resource('ocorrencias', OcorrenciasController::class)->except(['create', 'edit', 'show', 'destroy']);
Route::get('/ocorrencias/autocomplete', [OcorrenciasController::class, 'autoComplete'])->name('ocorrencias.autocomplete');
// Ocupações
Route::resource('mao-de-obra', OcupacoesController::class)->except(['create', 'edit', 'show', 'destroy']);
Route::get('/mao-de-obra/autocomplete', [OcupacoesController::class, 'autoComplete'])->name('mao_de_obra.autocomplete');
// Setores
Route::resource('setores', TiposAtendimentosController::class)->except(['create', 'edit', 'show', 'destroy']);
// Tipos de Ocupações
Route::resource('tipos-de-mao-de-obra', TiposOcupacoesController::class)->except(['create', 'edit', 'show', 'destroy']);
// Usuários
Route::resource('usuarios', UsuariosController::class)->except(['create', 'edit', 'show', 'destroy']);