<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AtendimentosController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\RelatoriosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
| Base URL: /api/v1
| Autenticação: Bearer token (Laravel Sanctum)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Públicas (sem token) ──────────────────────────────────────────────
    Route::post('/login', [AuthController::class, 'login']);

    // ── Protegidas (Bearer token obrigatório) ────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // Catálogos — somente leitura, para popular selects no app
        Route::get('/catalogos/mao-obra',    [CatalogoController::class, 'maoObra']);
        Route::get('/catalogos/ferramentas', [CatalogoController::class, 'ferramentas']);
        Route::get('/catalogos/ocorrencias', [CatalogoController::class, 'ocorrencias']);

        // Atendimentos
        Route::get('/atendimentos',      [AtendimentosController::class, 'index']);
        Route::get('/atendimentos/{id}', [AtendimentosController::class, 'show']);

        // Relatórios — CRUD principal
        Route::get('/relatorios',      [RelatoriosController::class, 'index']);
        Route::post('/relatorios',     [RelatoriosController::class, 'store']);
        Route::get('/relatorios/{id}', [RelatoriosController::class, 'show']);

        // Relatórios — seções
        Route::post('/relatorios/{id}/horarios',    [RelatoriosController::class, 'updateHorarios']);
        Route::post('/relatorios/{id}/clima',       [RelatoriosController::class, 'updateClima']);
        Route::post('/relatorios/{id}/assinaturas', [RelatoriosController::class, 'updateAssinaturas']);

        // Relatórios — mão de obra
        Route::post('/relatorios/{id}/mao-obra',             [RelatoriosController::class, 'storeMaoObra']);
        Route::delete('/relatorios/{id}/mao-obra/{ocup_id}', [RelatoriosController::class, 'destroyMaoObra']);

        // Relatórios — equipamentos
        Route::post('/relatorios/{id}/equipamentos',              [RelatoriosController::class, 'storeEquipamento']);
        Route::delete('/relatorios/{id}/equipamentos/{equip_id}', [RelatoriosController::class, 'destroyEquipamento']);

        // Relatórios — atividades
        Route::post('/relatorios/{id}/atividades',             [RelatoriosController::class, 'storeAtividade']);
        Route::put('/relatorios/{id}/atividades/{ativ_id}',    [RelatoriosController::class, 'updateAtividade']);
        Route::delete('/relatorios/{id}/atividades/{ativ_id}', [RelatoriosController::class, 'destroyAtividade']);

        // Relatórios — ocorrências
        Route::post('/relatorios/{id}/ocorrencias',                   [RelatoriosController::class, 'storeOcorrencia']);
        Route::delete('/relatorios/{id}/ocorrencias/{ocorrencia_id}', [RelatoriosController::class, 'destroyOcorrencia']);

        // Relatórios — comentários
        Route::post('/relatorios/{id}/comentarios',            [RelatoriosController::class, 'storeComentario']);
        Route::delete('/relatorios/{id}/comentarios/{com_id}', [RelatoriosController::class, 'destroyComentario']);

        // Relatórios — uploads
        Route::post('/relatorios/{id}/anexos',                   [RelatoriosController::class, 'uploadAnexos']);
        Route::delete('/relatorios/{id}/anexos/{tipo}/{item_id}', [RelatoriosController::class, 'destroyAnexo']);
    });
});
