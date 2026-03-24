<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipamentosController;
use App\Http\Controllers\NaturezaAtendimentoController;
use App\Http\Controllers\OcorrenciasController;
use App\Http\Controllers\TipoOcupacaoController;
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
// Equipamentos
Route::resource('equipamentos', EquipamentosController::class)->except(['create', 'edit', 'show', 'destroy']);
// Ocorrências
Route::resource('ocorrencias', OcorrenciasController::class)->except(['create', 'edit', 'show', 'destroy']);
// Tipos de Ocupações
Route::resource('tipos-de-mao-de-obra', TipoOcupacaoController::class)->except(['create', 'edit', 'show', 'destroy']);
// Natureza dos Atendimentos
Route::resource('naturezas-dos-atendimentos', NaturezaAtendimentoController::class)->except(['create', 'edit', 'show', 'destroy']);