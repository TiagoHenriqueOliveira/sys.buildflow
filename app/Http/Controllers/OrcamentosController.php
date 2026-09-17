<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersisteFiltros;
use App\Enums\NivelAcesso;
use App\Http\Requests\OrcamentoComentarioRequest;
use App\Http\Requests\OrcamentoRequest;
use App\Models\CrmTipoOrcamento;
use App\Models\Orcamento;
use App\Models\Usuario;
use App\Repositories\OrcamentoRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrcamentosController extends Controller
{
    use PersisteFiltros;

    public function __construct(
        private OrcamentoRepository $repository,
    ) {}

    public function index(Request $request): View
    {
        $filtros = $this->filtrosPersistentes('orcamentos', ['f_cliente', 'f_vendedor', 'f_tipo', 'f_nivel']);
        $filtroCliente = $filtros['f_cliente'];
        $filtroVendedor = $filtros['f_vendedor'];
        $filtroTipo = $filtros['f_tipo'];
        $filtroNivel = $filtros['f_nivel'];

        $orcamentos = Orcamento::query()
            ->with(['cliente', 'vendedor', 'tipoOrcamento'])
            ->when($filtroCliente !== '', fn ($q) => $q->whereHas('cliente', fn ($c) => $c->where('cli_nome', 'like', "%{$filtroCliente}%")))
            ->when($filtroVendedor !== '', fn ($q) => $q->where('orc_vendedor_id', (int) $filtroVendedor))
            ->when($filtroTipo !== '', fn ($q) => $q->where('orc_tipo_orcamento_id', (int) $filtroTipo))
            ->when($filtroNivel !== '', fn ($q) => $q->where('orc_nivel', (int) $filtroNivel))
            ->orderByDesc('orc_id')
            ->paginate(15)
            ->withQueryString();

        return view('orcamentos.index', [
            'orcamentos' => $orcamentos,
            'vendedores' => $this->vendedoresComerciais(),
            'tiposOrcamento' => CrmTipoOrcamento::where('crm_tp_orc_ativo', 1)->orderBy('crm_tp_orc_nome')->get(),
            'niveis' => \App\Enums\NivelOrcamento::cases(),
            'filtroCliente' => $filtroCliente,
            'filtroVendedor' => $filtroVendedor,
            'filtroTipo' => $filtroTipo,
            'filtroNivel' => $filtroNivel,
            'temFiltro' => $filtroCliente !== '' || $filtroVendedor !== '' || $filtroTipo !== '' || $filtroNivel !== '',
        ]);
    }

    public function create(): View
    {
        return view('orcamentos.form', [
            'orcamento' => new Orcamento(),
            ...$this->dadosApoioFormulario(),
        ]);
    }

    public function edit(int $id): View
    {
        $orcamento = Orcamento::with(['cliente', 'vendedor', 'respostas', 'comentarios.autor', 'vendedoresAdicionais'])->findOrFail($id);

        return view('orcamentos.form', [
            'orcamento' => $orcamento,
            ...$this->dadosApoioFormulario(),
        ]);
    }

    private function dadosApoioFormulario(): array
    {
        return [
            'vendedores' => $this->vendedoresComerciais(),
            'tiposOrcamento' => CrmTipoOrcamento::where('crm_tp_orc_ativo', 1)
                ->with(['configModelo.perguntas.opcoes'])
                ->orderBy('crm_tp_orc_nome')
                ->get(),
        ];
    }

    private function vendedoresComerciais()
    {
        return Usuario::where('user_nivel_acesso', NivelAcesso::Comercial->value)
            ->where('user_ativo', 1)
            ->orderBy('user_nome')
            ->get();
    }

    public function store(OrcamentoRequest $request): RedirectResponse
    {
        $orcamento = $this->repository->create($request->validated());

        return redirect()
            ->route('orcamentos.edit', $orcamento->orc_id)
            ->with('success', 'Orçamento cadastrado com sucesso.');
    }

    public function update(OrcamentoRequest $request, int $id): RedirectResponse
    {
        $this->repository->update($id, $request->validated());

        return redirect()
            ->route('orcamentos.edit', $id)
            ->with('success', 'Orçamento atualizado com sucesso.');
    }

    /**
     * CRM03 — comentário no orçamento, com autor/data/hora e alerta
     * opcional para outro usuário. Sub-recurso simples: form POST comum
     * (redirect + flash), sem AJAX.
     */
    public function storeComentario(OrcamentoComentarioRequest $request, int $id): RedirectResponse
    {
        $orcamento = Orcamento::findOrFail($id);

        $orcamento->comentarios()->create([
            'orc_com_autor_id' => Auth::id(),
            'orc_com_texto' => $request->input('orc_com_texto'),
            'orc_com_alerta_usuario_id' => $request->input('orc_com_alerta_usuario_id'),
            'orc_com_criado_em' => now(),
        ]);

        // Pedido do cliente (2026-09-16): o alerta de comentario (CRM03) so
        // gravava o destinatario sem nunca notificar de verdade - agora usa
        // o Sistema de Notificacoes generico (sino do topbar). Pedido do
        // cliente (2026-09-17): texto padrao fixo, mostrando o numero do
        // orcamento e quem incluiu o comentario.
        if ($alertaUsuarioId = $request->input('orc_com_alerta_usuario_id')) {
            \App\Models\Notificacao::notificar(
                (int) $alertaUsuarioId,
                'comentario_orcamento',
                'Novo comentário em orçamento',
                'Novo comentário adicionado no orçamento Nº '.$orcamento->orc_id.'. Incluído por '.Auth::user()->user_nome.'.',
                route('orcamentos.edit', $orcamento->orc_id)
            );
        }

        // Pedido do cliente (2026-09-14): apos adicionar um comentario, a
        // pagina recarrega (form comum, sem AJAX) e a aba ativa (estado do
        // Alpine, perdido no reload) voltava sempre pra "Dados" — flash
        // 'tab' lido no x-data da view pra reabrir direto em "Comentários".
        return redirect()
            ->route('orcamentos.edit', $id)
            ->with('success', 'Comentário adicionado com sucesso.')
            ->with('tab', 'comentarios');
    }

    /**
     * Pedido do cliente (2026-09-14): precisa dar pra excluir um comentário
     * (deixou de ser log 100% imutável). Mesmo perfil de acesso da tela
     * (middleware "comercial") — sem checagem de autoria, mesma regra já
     * usada nos outros destroy* deste sistema (ex.: equipamentos, anexos).
     */
    public function destroyComentario(int $id, int $comentarioId): RedirectResponse
    {
        $orcamento = Orcamento::findOrFail($id);
        $orcamento->comentarios()->where('orc_com_id', $comentarioId)->firstOrFail()->delete();

        return redirect()
            ->route('orcamentos.edit', $id)
            ->with('success', 'Comentário removido com sucesso.')
            ->with('tab', 'comentarios');
    }
}