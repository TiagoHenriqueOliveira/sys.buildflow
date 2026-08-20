<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AtendimentosController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\RelatoriosController;
use App\Http\Controllers\Api\Mcl\AppController as MclAppController;
use App\Http\Controllers\Api\Mcl\AtendimentosController as MclAtendimentosController;
use App\Http\Controllers\Api\Mcl\RelatoriosController as MclRelatoriosController;
use App\Http\Controllers\Api\Mcl\CatalogoController as MclCatalogoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes â€” v1
| Base URL: /api/v1
| AutenticaÃ§Ã£o: Bearer token (Laravel Sanctum)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // â”€â”€ PÃºblicas (sem token) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::post('/login', [AuthController::class, 'login']);

    // â”€â”€ Protegidas (Bearer token obrigatÃ³rio) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // CatÃ¡logos â€” somente leitura, para popular selects no app
        Route::get('/catalogos/mao-obra',    [CatalogoController::class, 'maoObra']);
        Route::get('/catalogos/ferramentas', [CatalogoController::class, 'ferramentas']);
        Route::get('/catalogos/ocorrencias', [CatalogoController::class, 'ocorrencias']);

        // Atendimentos
        Route::get('/atendimentos',      [AtendimentosController::class, 'index']);
        Route::get('/atendimentos/{id}', [AtendimentosController::class, 'show']);

        // RelatÃ³rios â€” CRUD principal
        Route::get('/relatorios',      [RelatoriosController::class, 'index']);
        Route::post('/relatorios',     [RelatoriosController::class, 'store']);
        Route::get('/relatorios/{id}', [RelatoriosController::class, 'show']);

        // RelatÃ³rios â€” seÃ§Ãµes
        Route::post('/relatorios/{id}/horarios',    [RelatoriosController::class, 'updateHorarios']);
        Route::post('/relatorios/{id}/clima',       [RelatoriosController::class, 'updateClima']);
        Route::post('/relatorios/{id}/assinaturas', [RelatoriosController::class, 'updateAssinaturas']);

        // RelatÃ³rios â€” mÃ£o de obra
        Route::post('/relatorios/{id}/mao-obra',             [RelatoriosController::class, 'storeMaoObra']);
        Route::delete('/relatorios/{id}/mao-obra/{ocup_id}', [RelatoriosController::class, 'destroyMaoObra']);

        // RelatÃ³rios â€” equipamentos
        Route::post('/relatorios/{id}/equipamentos',              [RelatoriosController::class, 'storeEquipamento']);
        Route::delete('/relatorios/{id}/equipamentos/{equip_id}', [RelatoriosController::class, 'destroyEquipamento']);

        // RelatÃ³rios â€” atividades
        Route::post('/relatorios/{id}/atividades',             [RelatoriosController::class, 'storeAtividade']);
        Route::put('/relatorios/{id}/atividades/{ativ_id}',    [RelatoriosController::class, 'updateAtividade']);
        Route::delete('/relatorios/{id}/atividades/{ativ_id}', [RelatoriosController::class, 'destroyAtividade']);

        // RelatÃ³rios â€” ocorrÃªncias
        Route::post('/relatorios/{id}/ocorrencias',                   [RelatoriosController::class, 'storeOcorrencia']);
        Route::delete('/relatorios/{id}/ocorrencias/{ocorrencia_id}', [RelatoriosController::class, 'destroyOcorrencia']);

        // RelatÃ³rios â€” comentÃ¡rios
        Route::post('/relatorios/{id}/comentarios',            [RelatoriosController::class, 'storeComentario']);
        Route::delete('/relatorios/{id}/comentarios/{com_id}', [RelatoriosController::class, 'destroyComentario']);

        // RelatÃ³rios â€” uploads
        Route::get('/relatorios/{id}/anexos',                    [RelatoriosController::class, 'getAnexos']);
        Route::post('/relatorios/{id}/anexos',                   [RelatoriosController::class, 'uploadAnexos']);
        Route::delete('/relatorios/{id}/anexos/{tipo}/{item_id}', [RelatoriosController::class, 'destroyAnexo']);
    });
});

/*
|--------------------------------------------------------------------------
| MCL Vale â€” API v1
| Base URL: /api/mcl/v1
| Auth: Bearer token (Laravel Sanctum)
|--------------------------------------------------------------------------
*/

Route::prefix('mcl/v1')->group(function () {

    // PÃºblica
    Route::post('/login',  [AuthController::class, 'login']);
    Route::get('/app/versao', [MclAppController::class, 'versao']);

    // Protegidas
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // CatÃ¡logos (somente leitura)
        Route::get('/catalogos/ocorrencias', [MclCatalogoController::class, 'ocorrencias']);

        // Atendimentos
        Route::get('/atendimentos',      [MclAtendimentosController::class, 'index']);
        Route::get('/atendimentos/{id}', [MclAtendimentosController::class, 'show']);
        Route::put('/atendimentos/{id}/status', [MclAtendimentosController::class, 'updateStatus']);

        // RelatÃ³rios de um atendimento
        Route::get('/atendimentos/{aten_id}/relatorios',  [MclRelatoriosController::class, 'index']);
        Route::post('/atendimentos/{aten_id}/relatorios', [MclRelatoriosController::class, 'store']);

        // RelatÃ³rio â€” leitura
        Route::get('/relatorios/{id}', [MclRelatoriosController::class, 'show']);

        // RelatÃ³rio â€” seÃ§Ãµes de escrita
        Route::put('/relatorios/{id}/descricao',               [MclRelatoriosController::class, 'updateDescricao']);
        Route::put('/relatorios/{id}/informacoes-adicionais',  [MclRelatoriosController::class, 'updateInformacoesAdicionais']);
        Route::put('/relatorios/{id}/horarios',                [MclRelatoriosController::class, 'updateHorarios']);
        Route::put('/relatorios/{id}/clima',                   [MclRelatoriosController::class, 'updateClima']);
        Route::put('/relatorios/{id}/status',                  [MclRelatoriosController::class, 'updateStatus']);

        // ServiÃ§os (1:N)
        Route::post('/relatorios/{id}/servicos',             [MclRelatoriosController::class, 'storeServico']);
        Route::delete('/relatorios/{id}/servicos/{serv_id}', [MclRelatoriosController::class, 'destroyServico']);

        // PeÃ§as (1:N)
        Route::post('/relatorios/{id}/pecas',             [MclRelatoriosController::class, 'storePeca']);
        Route::delete('/relatorios/{id}/pecas/{peca_id}', [MclRelatoriosController::class, 'destroyPeca']);

        // Itens de descriÃ§Ã£o (texto + foto opcional) â€” RF001, multipart/form-data
        Route::post('/relatorios/{id}/descricao-itens',              [MclRelatoriosController::class, 'storeDescricaoItem']);
        Route::delete('/relatorios/{id}/descricao-itens/{item_id}',  [MclRelatoriosController::class, 'destroyDescricaoItem']);

        // OcorrÃªncias
        Route::post('/relatorios/{id}/ocorrencias',                    [MclRelatoriosController::class, 'storeOcorrencia']);
        Route::delete('/relatorios/{id}/ocorrencias/{ocorrencia_id}',  [MclRelatoriosController::class, 'destroyOcorrencia']);

        // Assinaturas (base64)
        Route::post('/relatorios/{id}/assinaturas', [MclRelatoriosController::class, 'storeAssinaturas']);

        // Fotos / vÃ­deos / arquivos (multipart/form-data)
        Route::get('/relatorios/{id}/anexos',                    [MclRelatoriosController::class, 'getAnexos']);
        Route::post('/relatorios/{id}/anexos',                   [MclRelatoriosController::class, 'uploadAnexos']);
        Route::delete('/relatorios/{id}/anexos/{tipo}/{item_id}', [MclRelatoriosController::class, 'destroyAnexo']);
    });
});

