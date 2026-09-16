<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AtendimentosController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\RelatoriosController;
use App\Http\Controllers\Api\Mcl\AppController as MclAppController;
use App\Http\Controllers\Api\Mcl\AtendimentosController as MclAtendimentosController;
use App\Http\Controllers\Api\Mcl\RelatoriosController as MclRelatoriosController;
use App\Http\Controllers\Api\Mcl\CatalogoController as MclCatalogoController;
use App\Http\Controllers\Api\Fae\AppController as FaeAppController;
use App\Http\Controllers\Api\Fae\AtendimentosController as FaeAtendimentosController;
use App\Http\Controllers\Api\Fae\RelatoriosController as FaeRelatoriosController;
use App\Http\Controllers\Api\Fae\CatalogoController as FaeCatalogoController;
use App\Http\Controllers\Api\Fae\ClientesController as FaeClientesController;
use App\Http\Controllers\Api\Fae\RelatorioFormularioController as FaeRelatorioFormularioController;
use App\Http\Controllers\Api\Fae\OrcamentosController as FaeOrcamentosController;
use App\Http\Controllers\Api\Fae\RoteirosViagemController as FaeRoteirosViagemController;
use App\Http\Controllers\Api\Fae\CrmController as FaeCrmController;
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
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

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
    Route::post('/login',  [AuthController::class, 'login'])->middleware('throttle:login');
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

/*
|--------------------------------------------------------------------------
| FAE Bioenergia â€” API v1
| Base URL: /api/fae/v1
| Auth: Bearer token (Laravel Sanctum)
|--------------------------------------------------------------------------
*/

Route::prefix('fae/v1')->group(function () {

    // PÃºblica
    Route::post('/login',  [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/app/versao', [FaeAppController::class, 'versao']);

    // Protegidas
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // Clientes (NC01) - CRUD consumido pelo app. Pedido do cliente
        // (2026-09-16): create/update aberto a todos os perfis exceto Tecnico
        // (middleware `nao-tecnico`) - era `comercial`/Administrador+Comercial
        // antes dessa mudanca, mesma regra aplicada no Web em routes/web.php.
        Route::get('/clientes',              [FaeClientesController::class, 'index']);
        Route::get('/clientes/autocomplete', [FaeClientesController::class, 'autocomplete']);
        Route::get('/clientes/{cliente}',    [FaeClientesController::class, 'show']);
        Route::post('/clientes',             [FaeClientesController::class, 'store'])->middleware('nao-tecnico');
        Route::put('/clientes/{cliente}',    [FaeClientesController::class, 'update'])->middleware('nao-tecnico');

        // CRM09 - Orcamento/Roteiro de Viagem/Mapa de Relacoes/Indicadores,
        // perfil Comercial (ou Administrador) em todo o bloco - nenhum uso
        // por tecnico, ao contrario de Clientes acima.
        Route::middleware('comercial')->group(function () {
            Route::get('/orcamentos',                          [FaeOrcamentosController::class, 'index']);
            Route::post('/orcamentos',                         [FaeOrcamentosController::class, 'store']);
            Route::get('/orcamentos/{orcamento}',               [FaeOrcamentosController::class, 'show']);
            Route::put('/orcamentos/{orcamento}',               [FaeOrcamentosController::class, 'update']);
            Route::post('/orcamentos/{id}/comentarios',         [FaeOrcamentosController::class, 'storeComentario']);
            Route::delete('/orcamentos/{id}/comentarios/{comentarioId}', [FaeOrcamentosController::class, 'destroyComentario']);

            Route::get('/roteiros-viagem',              [FaeRoteirosViagemController::class, 'index']);
            Route::post('/roteiros-viagem',              [FaeRoteirosViagemController::class, 'store']);
            Route::get('/roteiros-viagem/{roteiro}',     [FaeRoteirosViagemController::class, 'show']);
            Route::put('/roteiros-viagem/{roteiro}',     [FaeRoteirosViagemController::class, 'update']);

            Route::get('/mapa-relacoes',          [FaeCrmController::class, 'mapaRelacoes']);

            Route::get('/catalogos/vendedores-comerciais', [FaeCatalogoController::class, 'vendedoresComerciais']);
            Route::get('/catalogos/tipos-orcamento',       [FaeCatalogoController::class, 'tiposOrcamento']);
        });

        Route::get('/catalogos/classificacoes-cliente', [FaeCatalogoController::class, 'classificacoesCliente']);

        // CatÃ¡logos (somente leitura)
        Route::get('/catalogos/ocorrencias', [FaeCatalogoController::class, 'ocorrencias']);

        // Atendimentos
        Route::get('/atendimentos',      [FaeAtendimentosController::class, 'index']);
        Route::get('/atendimentos/{id}', [FaeAtendimentosController::class, 'show']);
        Route::put('/atendimentos/{id}/status', [FaeAtendimentosController::class, 'updateStatus']);
        Route::get('/mapa-demandas', [FaeAtendimentosController::class, 'mapaDemandas']);

        // RelatÃ³rios de um atendimento
        Route::get('/atendimentos/{aten_id}/relatorios',  [FaeRelatoriosController::class, 'index']);
        Route::post('/atendimentos/{aten_id}/relatorios', [FaeRelatoriosController::class, 'store']);

        // RelatÃ³rio â€” leitura
        Route::get('/relatorios/{id}', [FaeRelatoriosController::class, 'show']);

        // Formulario dinamico (BF05/NC03) - motor novo, consome o mesmo
        // modelo/respostas do Configurador; os endpoints fixos abaixo
        // (horarios/clima/servicos/pecas/ocorrencias) ficam so pra
        // relatorios com dado legado (decisao da sessao 13).
        Route::get('/relatorios/{id}/formulario', [FaeRelatorioFormularioController::class, 'formulario']);
        Route::post('/relatorios/{id}/respostas', [FaeRelatorioFormularioController::class, 'storeResposta']);
        Route::delete('/relatorios/{id}/respostas/{respostaId}', [FaeRelatorioFormularioController::class, 'destroyResposta']);
        Route::delete('/relatorios/{id}/respostas-fotos/{fotoId}', [FaeRelatorioFormularioController::class, 'destroyRespostaFoto']);

        // RelatÃ³rio â€” seÃ§Ãµes de escrita
        Route::put('/relatorios/{id}/informacoes-adicionais',  [FaeRelatoriosController::class, 'updateInformacoesAdicionais']);
        Route::put('/relatorios/{id}/observacao-interna',      [FaeRelatoriosController::class, 'updateObservacaoInterna']);
        Route::put('/relatorios/{id}/horarios',                [FaeRelatoriosController::class, 'updateHorarios']);
        Route::put('/relatorios/{id}/clima',                   [FaeRelatoriosController::class, 'updateClima']);
        Route::put('/relatorios/{id}/status',                  [FaeRelatoriosController::class, 'updateStatus']);

        // ServiÃ§os (1:N)
        Route::post('/relatorios/{id}/servicos',             [FaeRelatoriosController::class, 'storeServico']);
        Route::delete('/relatorios/{id}/servicos/{serv_id}', [FaeRelatoriosController::class, 'destroyServico']);

        // PeÃ§as (1:N)
        Route::post('/relatorios/{id}/pecas',             [FaeRelatoriosController::class, 'storePeca']);
        Route::delete('/relatorios/{id}/pecas/{peca_id}', [FaeRelatoriosController::class, 'destroyPeca']);

        // Itens de descriÃ§Ã£o (texto + foto opcional) â€” RF001, multipart/form-data
        Route::post('/relatorios/{id}/descricao-itens',              [FaeRelatoriosController::class, 'storeDescricaoItem']);
        Route::delete('/relatorios/{id}/descricao-itens/{item_id}',  [FaeRelatoriosController::class, 'destroyDescricaoItem']);

        // OcorrÃªncias
        Route::post('/relatorios/{id}/ocorrencias',                    [FaeRelatoriosController::class, 'storeOcorrencia']);
        Route::delete('/relatorios/{id}/ocorrencias/{ocorrencia_id}',  [FaeRelatoriosController::class, 'destroyOcorrencia']);

        // Comprovante de compartilhamento (BF07)
        Route::get('/relatorios/{id}/compartilhamentos',  [FaeRelatoriosController::class, 'getCompartilhamentos']);
        Route::post('/relatorios/{id}/compartilhamentos', [FaeRelatoriosController::class, 'storeCompartilhamento']);

        // Assinaturas (base64)
        Route::post('/relatorios/{id}/assinaturas', [FaeRelatoriosController::class, 'storeAssinaturas']);

        // Fotos / vÃ­deos / arquivos (multipart/form-data)
        Route::get('/relatorios/{id}/anexos',                    [FaeRelatoriosController::class, 'getAnexos']);
        Route::post('/relatorios/{id}/anexos',                   [FaeRelatoriosController::class, 'uploadAnexos']);
        Route::delete('/relatorios/{id}/anexos/{tipo}/{item_id}', [FaeRelatoriosController::class, 'destroyAnexo']);
    });
});

