<?php

use App\Http\Controllers\AtendimentosController;
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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

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
Route::get('/atendimento/autocomplete', [AtendimentosController::class, 'autoComplete'])->name('atendimento.autocomplete');
// Clientes
Route::resource('clientes', ClientesController::class)->except(['create', 'edit', 'show', 'destroy']);
// Equipamentos
Route::resource('equipamentos', EquipamentosController::class)->except(['create', 'edit', 'show', 'destroy']);
// Modelos de Relatórios
Route::resource('modelos-de-relatorios', ModelosRelatoriosController::class)->except(['create', 'edit', 'show', 'destroy']);
// Natureza dos Atendimentos
Route::resource('naturezas-dos-atendimentos', NaturezasAtendimentosController::class)->except(['create', 'edit', 'show', 'destroy']);
// Ocorrências
Route::resource('ocorrencias', OcorrenciasController::class)->except(['create', 'edit', 'show', 'destroy']);
// Ocupações
Route::resource('mao-de-obra', OcupacoesController::class)->except(['create', 'edit', 'show', 'destroy']);
// Setores
Route::resource('setores', TiposAtendimentosController::class)->except(['create', 'edit', 'show', 'destroy']);
// Tipos de Ocupações
Route::resource('tipos-de-mao-de-obra', TiposOcupacoesController::class)->except(['create', 'edit', 'show', 'destroy']);
// Usuários
Route::resource('usuarios', UsuariosController::class)->except(['create', 'edit', 'show', 'destroy']);