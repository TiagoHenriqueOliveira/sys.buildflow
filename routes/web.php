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
use App\Http\Controllers\MinhaContaController;
use App\Http\Controllers\NaturezasAtendimentosController;
use App\Http\Controllers\NotificacoesController;
use App\Http\Controllers\ClassificacoesClienteController;
use App\Http\Controllers\SegmentosController;
use App\Http\Controllers\OcorrenciasController;
use App\Http\Controllers\OrcamentosController;
use App\Http\Controllers\PerfisAcessoController;
use App\Http\Controllers\RoteirosViagemController;
use App\Http\Controllers\MfaController;
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

    // 2026-11-27: segundo passo do login (código do app autenticador) e
    // "Não é você?" (esquece o navegador lembrado). Ainda sem sessão
    // autenticada — o usuário fica pendente na sessão até acertar o código.
    Route::get('/login/codigo', 'mostrarMfa')->name('login.mfa');
    Route::post('/login/codigo', 'confirmarMfa')->name('login.mfa.store')->middleware('throttle:login');
    Route::post('/login/esquecer', 'esquecerDispositivo')->name('login.esquecer');
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
// 2026-11-27 (revisão de segurança do BF_v1.15.0): usuario.ativo + mfa
// também aqui — pela sessão web, quem ainda não cadastrou o autenticador
// não abre arquivos; pelo token do app (Sanctum) o comportamento não muda.
Route::middleware(['auth:web,sanctum', 'usuario.ativo', 'mfa'])->get('/midia/{path}', [StorageController::class, 'show'])
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
Route::middleware(['auth:web,sanctum', 'usuario.ativo', 'mfa'])
    ->get('/atendimentos-relatorios/{id}/pdf', [AtendimentosRelatoriosController::class, 'pdf'])
    ->middleware('can:relatorios.ver')
    ->name('atendimentos-relatorios.pdf');

// Rotas protegidas
//
// Pedido do cliente (2026-11-16): acesso por tela configurável no cadastro
// "Perfis de Acesso" — cada tela tem "<chave>.ver" (leitura) e
// "<chave>.editar" (gravação), checados pelo middleware nativo `can:`
// (App\Support\Permissoes + Gate::before no AuthServiceProvider).
// Administrador e o usuário protegido passam em tudo automaticamente.
// Sem permissão: página personalizada resources/views/errors/403.blade.php.
// 2026-11-27: usuário desativado perde a sessão na hora (usuario.ativo) e
// MFA obrigatório — sem o app autenticador cadastrado, só o cadastro dele e
// o "Sair" (mfa).
Route::middleware(['auth', 'usuario.ativo', 'mfa'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Cadastro do app autenticador (MFA) pelo próprio usuário.
    Route::get('/minha-conta/autenticador', [MfaController::class, 'show'])->name('mfa.cadastro');
    Route::post('/minha-conta/autenticador', [MfaController::class, 'store'])->name('mfa.cadastro.store')->middleware('throttle:login');

    // Dashboard (placeholder minimo por ora - ver DashboardController).
    // Sempre liberado: é o destino da página de acesso negado.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Minha conta — qualquer usuário edita o próprio nome/e-mail/senha (é
    // também como o usuário protegido se edita, já que não aparece em
    // Usuários).
    Route::get('/minha-conta', [MinhaContaController::class, 'edit'])->name('minha-conta.edit');
    Route::put('/minha-conta', [MinhaContaController::class, 'update'])->name('minha-conta.update');

    // Notificacoes (sino do topbar) - disponivel pra qualquer usuario
    // autenticado, sem restricao de perfil (o proprio conteudo ja e
    // filtrado pelo usuario logado dentro do controller).
    Route::get('/notificacoes', [NotificacoesController::class, 'index'])->name('notificacoes.index');
    Route::post('/notificacoes/{id}/marcar-lida', [NotificacoesController::class, 'marcarLida'])->name('notificacoes.marcar-lida');
    Route::post('/notificacoes/{id}/marcar-nao-lida', [NotificacoesController::class, 'marcarNaoLida'])->name('notificacoes.marcar-nao-lida');

    // Clientes — autocomplete e resumo: sem permissão de tela, porque são
    // usados dentro do formulário de Atendimento (BF03) por quem não
    // necessariamente acessa o cadastro de Clientes.
    Route::get('/clientes/autocomplete', [ClientesController::class, 'autoComplete'])->name('clientes.autocomplete');
    Route::get('/clientes/{cliente}/resumo', [ClientesController::class, 'resumo'])->name('clientes.resumo');

    // ─── Clientes ───────────────────────────────────────────────────────
    Route::middleware('can:clientes.ver')->group(function () {
        Route::get('/clientes', [ClientesController::class, 'index'])->name('clientes.index');
    });
    Route::middleware('can:clientes.editar')->group(function () {
        Route::get('/clientes/create', [ClientesController::class, 'create'])->name('clientes.create');
        Route::post('/clientes', [ClientesController::class, 'store'])->name('clientes.store');
        Route::get('/clientes/{cliente}/edit', [ClientesController::class, 'edit'])->name('clientes.edit');
        Route::match(['put', 'patch'], '/clientes/{cliente}', [ClientesController::class, 'update'])->name('clientes.update');
    });

    // ─── Orçamentos (CRM02/03/04) ───────────────────────────────────────
    Route::middleware('can:orcamentos.ver')->group(function () {
        Route::get('/orcamentos', [OrcamentosController::class, 'index'])->name('orcamentos.index');
    });
    Route::middleware('can:orcamentos.editar')->group(function () {
        Route::get('/orcamentos/create', [OrcamentosController::class, 'create'])->name('orcamentos.create');
        Route::post('/orcamentos', [OrcamentosController::class, 'store'])->name('orcamentos.store');
        Route::get('/orcamentos/{orcamento}/edit', [OrcamentosController::class, 'edit'])->name('orcamentos.edit');
        Route::match(['put', 'patch'], '/orcamentos/{orcamento}', [OrcamentosController::class, 'update'])->name('orcamentos.update');
        Route::post('/orcamentos/{id}/comentarios', [OrcamentosController::class, 'storeComentario'])->name('orcamentos.store-comentario');
        Route::delete('/orcamentos/{id}/comentarios/{comentarioId}', [OrcamentosController::class, 'destroyComentario'])->name('orcamentos.destroy-comentario');
    });

    // ─── Roteiro de viagem (CRM05/06) ───────────────────────────────────
    Route::middleware('can:roteiros.ver')->group(function () {
        Route::get('/roteiros-viagem', [RoteirosViagemController::class, 'index'])->name('roteiros-viagem.index');
    });
    Route::middleware('can:roteiros.editar')->group(function () {
        Route::get('/roteiros-viagem/create', [RoteirosViagemController::class, 'create'])->name('roteiros-viagem.create');
        Route::post('/roteiros-viagem', [RoteirosViagemController::class, 'store'])->name('roteiros-viagem.store');
        Route::get('/roteiros-viagem/{roteiros_viagem}/edit', [RoteirosViagemController::class, 'edit'])->name('roteiros-viagem.edit');
        Route::match(['put', 'patch'], '/roteiros-viagem/{roteiros_viagem}', [RoteirosViagemController::class, 'update'])->name('roteiros-viagem.update');
        Route::patch('/roteiros-viagem/{roteiros_viagem}/reabrir', [RoteirosViagemController::class, 'reabrir'])->name('roteiros-viagem.reabrir');
    });

    // Mapa de relações (CRM07) e Indicadores comerciais (CRM08) — leitura.
    Route::get('/mapa-relacoes', [MapaRelacoesController::class, 'index'])->name('mapa-relacoes.index')->middleware('can:mapa_relacoes.ver');
    Route::get('/indicadores-comerciais', [IndicadoresComerciaisController::class, 'index'])->name('indicadores-comerciais.index')->middleware('can:indicadores.ver');

    // ─── Atendimentos ───────────────────────────────────────────────────
    // A posse por registro continua no controller (técnico só os seus;
    // Assistência/Admin todos — Atendimento::veTodos()).
    Route::middleware('can:atendimentos.ver')->group(function () {
        Route::get('/atendimentos', [AtendimentosController::class, 'index'])->name('atendimentos.index');
        Route::get('/atendimentos/{id}/equipamentos', [AtendimentosController::class, 'getEquipamentos'])->name('atendimentos.get-equipamentos');
        Route::get('/atendimentos/{id}/relatorios', [AtendimentosController::class, 'getRelatorios'])->name('atendimentos.get-relatorios');
        Route::get('/atendimentos/{id}/observacoes', [AtendimentosController::class, 'getObservacoes'])->name('atendimentos.get-observacoes');
        Route::get('/atendimentos/{id}/anexos', [AtendimentosController::class, 'getAnexos'])->name('atendimentos.get-anexos');
    });
    Route::middleware('can:atendimentos.editar')->group(function () {
        Route::get('/atendimentos/create', [AtendimentosController::class, 'create'])->name('atendimentos.create');
        Route::get('/atendimentos/{id}/edit', [AtendimentosController::class, 'edit'])->name('atendimentos.edit');
        Route::post('/atendimentos', [AtendimentosController::class, 'store'])->name('atendimentos.store');
        Route::match(['put', 'patch'], '/atendimentos/{atendimento}', [AtendimentosController::class, 'update'])->name('atendimentos.update');
        Route::post('/atendimentos/{id}/equipamentos', [AtendimentosController::class, 'storeEquipamento'])->name('atendimentos.store-equipamentos');
        Route::delete('/atendimentos/{id}/equipamentos/{equipId}', [AtendimentosController::class, 'destroyEquipamento'])->name('atendimentos.destroy-equipamentos');
        Route::post('/atendimentos/{id}/observacoes', [AtendimentosController::class, 'updateObservacoes'])->name('atendimentos.update-observacoes');
        Route::post('/atendimentos/{id}/upload-anexos', [AtendimentosController::class, 'uploadAnexos'])->name('atendimentos.upload-anexos');
        Route::delete('/atendimentos/{id}/anexos/{itemId}', [AtendimentosController::class, 'destroyAnexo'])->name('atendimentos.destroy-anexo');
    });

    // ─── Relatórios de atendimento ──────────────────────────────────────
    // Rota do PDF fica fora deste grupo — ver auth:web,sanctum acima (RF005).
    Route::middleware('can:relatorios.ver')->group(function () {
        Route::get('/atendimentos-relatorios/autocomplete', [AtendimentosRelatoriosController::class, 'autoComplete'])->name('atendimentos_relatorios.autocomplete');
        Route::get('/atendimentos-relatorios', [AtendimentosRelatoriosController::class, 'index'])->name('atendimentos-relatorios.index');
        Route::get('/atendimentos-relatorios/{atendimentos_relatorio}', [AtendimentosRelatoriosController::class, 'show'])->name('atendimentos-relatorios.show');
        Route::get('/atendimentos-relatorios/{id}/dados', [AtendimentosRelatoriosController::class, 'getDados'])->name('atendimentos-relatorios.get-dados');
        Route::get('/atendimentos-relatorios/{id}/horarios', [AtendimentosRelatoriosController::class, 'getHorarios'])->name('atendimentos-relatorios.get-horarios');
        Route::get('/atendimentos-relatorios/{id}/clima', [AtendimentosRelatoriosController::class, 'getClimaData'])->name('atendimentos-relatorios.get-clima');
        Route::get('/atendimentos-relatorios/{id}/ocorrencias', [AtendimentosRelatoriosController::class, 'getOcorrenciasData'])->name('atendimentos-relatorios.get-ocorrencias');
        Route::get('/atendimentos-relatorios/{id}/assinaturas', [AtendimentosRelatoriosController::class, 'getAssinaturasData'])->name('atendimentos-relatorios.get-assinaturas');
        Route::get('/atendimentos-relatorios/{id}/servicos', [AtendimentosRelatoriosController::class, 'getServicos'])->name('atendimentos-relatorios.get-servicos');
        Route::get('/atendimentos-relatorios/{id}/pecas', [AtendimentosRelatoriosController::class, 'getPecas'])->name('atendimentos-relatorios.get-pecas');
        Route::get('/atendimentos-relatorios/{id}/descricao-itens', [AtendimentosRelatoriosController::class, 'getDescricaoItens'])->name('atendimentos-relatorios.get-descricao-itens');
        Route::get('/atendimentos-relatorios/{id}/anexos', [AtendimentosRelatoriosController::class, 'getAnexos'])->name('atendimentos-relatorios.get-anexos');
        Route::get('/atendimentos-relatorios/{id}/respostas', [AtendimentosRelatoriosController::class, 'getRespostas'])->name('atendimentos-relatorios.get-respostas');
        Route::get('/atendimentos-relatorios/{id}/compartilhamentos', [AtendimentosRelatoriosController::class, 'getCompartilhamentos'])->name('atendimentos-relatorios.get-compartilhamentos');
    });
    Route::middleware('can:relatorios.editar')->group(function () {
        Route::post('/atendimentos-relatorios', [AtendimentosRelatoriosController::class, 'store'])->name('atendimentos-relatorios.store');
        Route::match(['put', 'patch'], '/atendimentos-relatorios/{atendimentos_relatorio}', [AtendimentosRelatoriosController::class, 'update'])->name('atendimentos-relatorios.update');
        Route::post('/atendimentos-relatorios/{id}/dados', [AtendimentosRelatoriosController::class, 'updateDados'])->name('atendimentos-relatorios.update-dados');
        Route::post('/atendimentos-relatorios/{id}/horarios', [AtendimentosRelatoriosController::class, 'updateHorarios'])->name('atendimentos-relatorios.update-horarios');
        Route::post('/atendimentos-relatorios/{id}/clima', [AtendimentosRelatoriosController::class, 'updateClima'])->name('atendimentos-relatorios.update-clima');
        Route::post('/atendimentos-relatorios/{id}/assinaturas', [AtendimentosRelatoriosController::class, 'updateAssinaturas'])->name('atendimentos-relatorios.update-assinaturas');
        Route::post('/atendimentos-relatorios/{id}/texto/{campo}', [AtendimentosRelatoriosController::class, 'updateTexto'])->name('atendimentos-relatorios.update-texto');
        Route::post('/atendimentos-relatorios/{id}/ocorrencias', [AtendimentosRelatoriosController::class, 'storeOcorrencia'])->name('atendimentos-relatorios.store-ocorrencia');
        Route::delete('/atendimentos-relatorios/{id}/ocorrencias/{ocorrenciaId}', [AtendimentosRelatoriosController::class, 'destroyOcorrencia'])->name('atendimentos-relatorios.destroy-ocorrencia');
        Route::post('/atendimentos-relatorios/{id}/servicos', [AtendimentosRelatoriosController::class, 'storeServico'])->name('atendimentos-relatorios.store-servico');
        Route::delete('/atendimentos-relatorios/{id}/servicos/{itemId}', [AtendimentosRelatoriosController::class, 'destroyServico'])->name('atendimentos-relatorios.destroy-servico');
        Route::post('/atendimentos-relatorios/{id}/pecas', [AtendimentosRelatoriosController::class, 'storePeca'])->name('atendimentos-relatorios.store-peca');
        Route::delete('/atendimentos-relatorios/{id}/pecas/{itemId}', [AtendimentosRelatoriosController::class, 'destroyPeca'])->name('atendimentos-relatorios.destroy-peca');
        Route::post('/atendimentos-relatorios/{id}/descricao-itens', [AtendimentosRelatoriosController::class, 'storeDescricaoItem'])->name('atendimentos-relatorios.store-descricao-item');
        Route::delete('/atendimentos-relatorios/{id}/descricao-itens/{itemId}', [AtendimentosRelatoriosController::class, 'destroyDescricaoItem'])->name('atendimentos-relatorios.destroy-descricao-item');
        Route::post('/atendimentos-relatorios/{id}/upload-anexos', [AtendimentosRelatoriosController::class, 'uploadAnexos'])->name('atendimentos-relatorios.upload-anexos');
        Route::delete('/atendimentos-relatorios/{id}/anexos/{type}/{itemId}', [AtendimentosRelatoriosController::class, 'destroyAnexo'])->name('atendimentos-relatorios.destroy-anexo');
        // Sessao 08 - perguntas dinamicas do Configurador (NC02/NC03) e
        // comprovante de compartilhamento (BF07).
        Route::post('/atendimentos-relatorios/{id}/respostas', [AtendimentosRelatoriosController::class, 'storeResposta'])->name('atendimentos-relatorios.store-resposta');
        Route::delete('/atendimentos-relatorios/{id}/respostas/{respostaId}', [AtendimentosRelatoriosController::class, 'destroyResposta'])->name('atendimentos-relatorios.destroy-resposta');
        Route::delete('/atendimentos-relatorios/{id}/respostas-fotos/{fotoId}', [AtendimentosRelatoriosController::class, 'destroyRespostaFoto'])->name('atendimentos-relatorios.destroy-resposta-foto');
        Route::post('/atendimentos-relatorios/{id}/compartilhamentos', [AtendimentosRelatoriosController::class, 'storeCompartilhamento'])->name('atendimentos-relatorios.store-compartilhamento');
    });

    // Mapa de demandas (BF08) - mesma visibilidade de atendimentos-relatorios.index (tecnico ve so o seu, Assistencia/Admin tudo).
    Route::get('/mapa-demandas', [MapaDemandasController::class, 'index'])->name('mapa-demandas.index')->middleware('can:mapa_demandas.ver');

    // ─── Configurações (cadastros auxiliares) ───────────────────────────
    // Padrão das telas de modal: index = ver; store/update = editar.
    $cadastroModal = function (string $permissao, string $uri, string $controller, string $nomes, ?string $parametro = null) {
        Route::get($uri, [$controller, 'index'])->name("{$nomes}.index")->middleware("can:{$permissao}.ver");
        $param = $parametro ?? str_replace('-', '_', basename($uri));
        Route::middleware("can:{$permissao}.editar")->group(function () use ($uri, $controller, $nomes, $param) {
            Route::post($uri, [$controller, 'store'])->name("{$nomes}.store");
            Route::match(['put', 'patch'], "{$uri}/{{$param}}", [$controller, 'update'])->name("{$nomes}.update");
        });
    };

    Route::get('/ocorrencias/autocomplete', [OcorrenciasController::class, 'autoComplete'])->name('ocorrencias.autocomplete')->middleware('can:ocorrencias.ver');
    $cadastroModal('ocorrencias', 'ocorrencias', OcorrenciasController::class, 'ocorrencias', 'ocorrencia');
    $cadastroModal('naturezas', 'naturezas-dos-atendimentos', NaturezasAtendimentosController::class, 'naturezas-dos-atendimentos', 'naturezas_dos_atendimento');
    // NC01 (pendencia #3) — opcoes de classificacao de cliente
    $cadastroModal('classificacoes', 'classificacoes-cliente', ClassificacoesClienteController::class, 'classificacoes-cliente', 'classificacoes_cliente');
    $cadastroModal('segmentos', 'segmentos', SegmentosController::class, 'segmentos', 'segmento');
    // CRM01 — tipos de sistema de orçamento
    $cadastroModal('tipos_orcamento', 'crm/tipos-orcamento', CrmTiposOrcamentoController::class, 'crm.tipos-orcamento', 'id');
    // Configurador (NC02) — perguntas e modelos reutilizáveis
    Route::get('configurador/perguntas/autocomplete', [ConfiguradorPerguntasController::class, 'autoComplete'])->name('configurador.perguntas.autocomplete')->middleware('can:configurador.ver');
    $cadastroModal('configurador', 'configurador/perguntas', ConfiguradorPerguntasController::class, 'configurador.perguntas', 'id');
    $cadastroModal('configurador', 'configurador/modelos', ConfiguradorModelosController::class, 'configurador.modelos', 'id');
    // Usuários (o usuário protegido não aparece nem é editável por outros — ver UsuariosController)
    $cadastroModal('usuarios', 'usuarios', UsuariosController::class, 'usuarios', 'usuario');

    // Somente administradores (fixo, não configurável): a tela de perfis
    // (senão quem a acessasse poderia dar mais acesso a si mesmo), logs e
    // manutenção.
    Route::middleware('admin')->group(function () {
        // Perfis de acesso (2026-11-16)
        // Redefine o MFA de um usuário (celular perdido/trocado) — 2026-11-27.
        Route::delete('/usuarios/{usuario}/autenticador', [MfaController::class, 'redefinir'])->name('usuarios.mfa.redefinir');
        Route::get('/perfis-acesso', [PerfisAcessoController::class, 'index'])->name('perfis-acesso.index');
        Route::put('/perfis-acesso', [PerfisAcessoController::class, 'update'])->name('perfis-acesso.update');

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