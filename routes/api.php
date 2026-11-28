<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Fae\AppController as FaeAppController;
use App\Http\Controllers\Api\Fae\AtendimentosController as FaeAtendimentosController;
use App\Http\Controllers\Api\Fae\RelatoriosController as FaeRelatoriosController;
use App\Http\Controllers\Api\Fae\CatalogoController as FaeCatalogoController;
use App\Http\Controllers\Api\Fae\ClientesController as FaeClientesController;
use App\Http\Controllers\Api\Fae\RelatorioFormularioController as FaeRelatorioFormularioController;
use App\Http\Controllers\Api\Fae\OrcamentosController as FaeOrcamentosController;
use App\Http\Controllers\Api\Fae\RoteirosViagemController as FaeRoteirosViagemController;
use App\Http\Controllers\Api\Fae\CrmController as FaeCrmController;
use App\Http\Controllers\Api\Fae\NotificacoesController as FaeNotificacoesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FAE Bioenergia â€” API v1
| Base URL: /api/fae/v1
| Auth: Bearer token (Laravel Sanctum)
|--------------------------------------------------------------------------
*/

// Auditoria S-14 / contrato §3: X-App-Version → 426 "versao_minima"
// (só com MOBILE_APK_OBRIGATORIA=true; /app/versao nunca é barrado).
Route::prefix('fae/v1')->middleware('versao.app')->group(function () {

    // PÃºblica
    Route::post('/login',  [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/login/mfa', [AuthController::class, 'loginMfa'])->middleware('throttle:login');
    Route::get('/app/versao', [FaeAppController::class, 'versao'])->name('fae.app.versao');

    // Protegidas
    // Auditoria 27/11/2026 (S-05): token precisa da habilidade "fae-app"
    // (os tokens antigos, com "*", continuam valendo até vencer).
    // Contrato §4 (S-14): "idempotente" age só nos POST que criam algo
    // (lista em App\Http\Middleware\Idempotencia::ROTAS), antes das
    // permissões e do bloqueio de relatório aprovado.
    Route::middleware(['auth:sanctum', 'abilities:fae-app', 'idempotente'])->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // Pedido do cliente (2026-11-16): mesmas permissões por tela do Web
        // (cadastro "Perfis de Acesso") — "<tela>.ver" na leitura,
        // "<tela>.editar" na gravação. O /me devolve a lista ("permissoes")
        // pro app esconder o que o usuário não acessa.

        // Clientes (NC01). Autocomplete sem permissão de tela: usado em
        // outros fluxos (atendimento/orçamento), como no Web.
        Route::get('/clientes/autocomplete', [FaeClientesController::class, 'autocomplete']);
        Route::get('/clientes',              [FaeClientesController::class, 'index'])->middleware('can:clientes.ver');
        Route::get('/clientes/{cliente}',    [FaeClientesController::class, 'show'])->middleware('can:clientes.ver');
        Route::post('/clientes',             [FaeClientesController::class, 'store'])->middleware('can:clientes.editar');
        Route::put('/clientes/{cliente}',    [FaeClientesController::class, 'update'])->middleware('can:clientes.editar');

        // CRM09 - Orcamento/Roteiro de Viagem/Mapa de Relacoes.
        Route::get('/orcamentos',                          [FaeOrcamentosController::class, 'index'])->middleware('can:orcamentos.ver');
        Route::get('/orcamentos/{orcamento}',               [FaeOrcamentosController::class, 'show'])->middleware('can:orcamentos.ver');
        Route::middleware('can:orcamentos.editar')->group(function () {
            Route::post('/orcamentos',                         [FaeOrcamentosController::class, 'store']);
            Route::put('/orcamentos/{orcamento}',               [FaeOrcamentosController::class, 'update']);
            Route::post('/orcamentos/{id}/comentarios',         [FaeOrcamentosController::class, 'storeComentario']);
            Route::delete('/orcamentos/{id}/comentarios/{comentarioId}', [FaeOrcamentosController::class, 'destroyComentario']);
        });

        Route::get('/roteiros-viagem',              [FaeRoteirosViagemController::class, 'index'])->middleware('can:roteiros.ver');
        Route::get('/roteiros-viagem/{roteiro}',     [FaeRoteirosViagemController::class, 'show'])->middleware('can:roteiros.ver');
        Route::post('/roteiros-viagem',              [FaeRoteirosViagemController::class, 'store'])->middleware('can:roteiros.editar');
        Route::put('/roteiros-viagem/{roteiro}',     [FaeRoteirosViagemController::class, 'update'])->middleware('can:roteiros.editar');

        Route::get('/mapa-relacoes',          [FaeCrmController::class, 'mapaRelacoes'])->middleware('can:mapa_relacoes.ver');

        // Catálogos usados nos formulários do CRM (vendedores = perfis com
        // "Atua como vendedor"; ver Usuario::scopeVendedores).
        Route::get('/catalogos/vendedores-comerciais', [FaeCatalogoController::class, 'vendedoresComerciais'])->middleware('can:orcamentos.ver');
        Route::get('/catalogos/tipos-orcamento',       [FaeCatalogoController::class, 'tiposOrcamento'])->middleware('can:orcamentos.ver');

        Route::get('/catalogos/classificacoes-cliente', [FaeCatalogoController::class, 'classificacoesCliente']);
        Route::get('/catalogos/segmentos', [FaeCatalogoController::class, 'segmentos']);

        // CatÃ¡logos (somente leitura)
        Route::get('/catalogos/ocorrencias', [FaeCatalogoController::class, 'ocorrencias']);

        // Sistema de Notificacoes (sino do Web) - consulta/marcacao;
        // push nativa (mesmo com app fechado) fica pra quando o transporte
        // (FCM) for implementado.
        Route::get('/notificacoes', [FaeNotificacoesController::class, 'index']);
        Route::post('/notificacoes/{id}/marcar-lida', [FaeNotificacoesController::class, 'marcarLida']);
        Route::post('/notificacoes/{id}/marcar-nao-lida', [FaeNotificacoesController::class, 'marcarNaoLida']);

        // Atendimentos
        Route::get('/atendimentos',      [FaeAtendimentosController::class, 'index'])->middleware('can:atendimentos.ver');
        Route::get('/atendimentos/{id}', [FaeAtendimentosController::class, 'show'])->middleware('can:atendimentos.ver');
        Route::put('/atendimentos/{id}/status', [FaeAtendimentosController::class, 'updateStatus'])->middleware('can:atendimentos.editar');
        Route::get('/mapa-demandas', [FaeAtendimentosController::class, 'mapaDemandas'])->middleware('can:mapa_demandas.ver');

        // RelatÃ³rios de um atendimento
        Route::get('/atendimentos/{aten_id}/relatorios',  [FaeRelatoriosController::class, 'index'])->middleware('can:relatorios.ver');
        Route::post('/atendimentos/{aten_id}/relatorios', [FaeRelatoriosController::class, 'store'])->middleware('can:relatorios.editar');

        // RelatÃ³rio â€” leitura
        Route::get('/relatorios/{id}', [FaeRelatoriosController::class, 'show'])->middleware('can:relatorios.ver');

        // Formulario dinamico (BF05/NC03) - motor novo, consome o mesmo
        // modelo/respostas do Configurador; os endpoints fixos abaixo
        // (horarios/clima/servicos/pecas/ocorrencias) ficam so pra
        // relatorios com dado legado (decisao da sessao 13).
        Route::get('/relatorios/{id}/formulario', [FaeRelatorioFormularioController::class, 'formulario'])->middleware('can:relatorios.ver');
        Route::post('/relatorios/{id}/respostas', [FaeRelatorioFormularioController::class, 'storeResposta'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);
        Route::delete('/relatorios/{id}/respostas/{respostaId}', [FaeRelatorioFormularioController::class, 'destroyResposta'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);
        Route::delete('/relatorios/{id}/respostas-fotos/{fotoId}', [FaeRelatorioFormularioController::class, 'destroyRespostaFoto'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);

        // RelatÃ³rio â€” seÃ§Ãµes de escrita
        Route::put('/relatorios/{id}/informacoes-adicionais',  [FaeRelatoriosController::class, 'updateInformacoesAdicionais'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);
        Route::put('/relatorios/{id}/observacao-interna',      [FaeRelatoriosController::class, 'updateObservacaoInterna'])->middleware('can:relatorios.editar');
        Route::put('/relatorios/{id}/horarios',                [FaeRelatoriosController::class, 'updateHorarios'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);
        Route::put('/relatorios/{id}/clima',                   [FaeRelatoriosController::class, 'updateClima'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);
        Route::put('/relatorios/{id}/status',                  [FaeRelatoriosController::class, 'updateStatus'])->middleware('can:relatorios.editar');

        // ServiÃ§os (1:N)
        Route::post('/relatorios/{id}/servicos',             [FaeRelatoriosController::class, 'storeServico'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);
        Route::delete('/relatorios/{id}/servicos/{serv_id}', [FaeRelatoriosController::class, 'destroyServico'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);

        // PeÃ§as (1:N)
        Route::post('/relatorios/{id}/pecas',             [FaeRelatoriosController::class, 'storePeca'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);
        Route::delete('/relatorios/{id}/pecas/{peca_id}', [FaeRelatoriosController::class, 'destroyPeca'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);

        // Itens de descriÃ§Ã£o (texto + foto opcional) â€” RF001, multipart/form-data
        Route::post('/relatorios/{id}/descricao-itens',              [FaeRelatoriosController::class, 'storeDescricaoItem'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);
        Route::delete('/relatorios/{id}/descricao-itens/{item_id}',  [FaeRelatoriosController::class, 'destroyDescricaoItem'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);

        // OcorrÃªncias
        Route::post('/relatorios/{id}/ocorrencias',                    [FaeRelatoriosController::class, 'storeOcorrencia'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);
        Route::delete('/relatorios/{id}/ocorrencias/{ocorrencia_id}',  [FaeRelatoriosController::class, 'destroyOcorrencia'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);

        // Comprovante de compartilhamento (BF07)
        Route::get('/relatorios/{id}/compartilhamentos',  [FaeRelatoriosController::class, 'getCompartilhamentos'])->middleware('can:relatorios.ver');
        Route::post('/relatorios/{id}/compartilhamentos', [FaeRelatoriosController::class, 'storeCompartilhamento'])->middleware('can:relatorios.editar');

        // Assinaturas (base64)
        Route::post('/relatorios/{id}/assinaturas', [FaeRelatoriosController::class, 'storeAssinaturas'])->middleware('can:relatorios.editar');

        // Fotos / vÃ­deos / arquivos (multipart/form-data)
        Route::get('/relatorios/{id}/anexos',                    [FaeRelatoriosController::class, 'getAnexos'])->middleware('can:relatorios.ver');
        Route::post('/relatorios/{id}/anexos',                   [FaeRelatoriosController::class, 'uploadAnexos'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);
        Route::delete('/relatorios/{id}/anexos/{tipo}/{item_id}', [FaeRelatoriosController::class, 'destroyAnexo'])->middleware(['can:relatorios.editar', 'relatorio.editavel']);
    });
});

