<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersisteFiltros;
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
        // 2026-11-27: + prazo de envio (período de/até) e resultado ("aberto"
        // = ainda sem resultado).
        $filtros = $this->filtrosPersistentes('orcamentos', ['f_cliente', 'f_vendedor', 'f_tipo', 'f_nivel', 'f_prazo_de', 'f_prazo_ate', 'f_resultado']);
        $filtroCliente = $filtros['f_cliente'];
        $filtroVendedor = $filtros['f_vendedor'];
        $filtroTipo = $filtros['f_tipo'];
        $filtroNivel = $filtros['f_nivel'];
        $filtroPrazoDe = $this->dataValida($filtros['f_prazo_de']);
        $filtroPrazoAte = $this->dataValida($filtros['f_prazo_ate']);
        $filtroResultado = $filtros['f_resultado'];

        $orcamentos = Orcamento::query()
            ->with(['cliente', 'vendedor', 'tipoOrcamento'])
            ->visivelPara($request->user())
            ->when($filtroCliente !== '', fn ($q) => $q->whereHas('cliente', fn ($c) => $c->where('cli_nome', 'like', "%{$filtroCliente}%")))
            ->when($filtroVendedor !== '', fn ($q) => $q->where('orc_vendedor_id', (int) $filtroVendedor))
            ->when($filtroTipo !== '', fn ($q) => $q->where('orc_tipo_orcamento_id', (int) $filtroTipo))
            ->when($filtroNivel !== '', fn ($q) => $q->where('orc_nivel', (int) $filtroNivel))
            ->when($filtroPrazoDe !== '', fn ($q) => $q->whereDate('orc_prazo_envio', '>=', $filtroPrazoDe))
            ->when($filtroPrazoAte !== '', fn ($q) => $q->whereDate('orc_prazo_envio', '<=', $filtroPrazoAte))
            ->when($filtroResultado === 'aberto', fn ($q) => $q->whereNull('orc_resultado'))
            ->when($filtroResultado !== '' && $filtroResultado !== 'aberto', fn ($q) => $q->where('orc_resultado', (int) $filtroResultado))
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
            'filtroPrazoDe' => $filtroPrazoDe,
            'filtroPrazoAte' => $filtroPrazoAte,
            'filtroResultado' => $filtroResultado,
            'resultados' => \App\Enums\ResultadoOrcamento::cases(),
            'temFiltro' => $filtroCliente !== '' || $filtroVendedor !== '' || $filtroTipo !== '' || $filtroNivel !== ''
                || $filtroPrazoDe !== '' || $filtroPrazoAte !== '' || $filtroResultado !== '',
        ]);
    }

    /** Data do filtro no formato do <input type="date">; qualquer outra coisa é ignorada. */
    private function dataValida(string $valor): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1 ? $valor : '';
    }

    public function create(): View
    {
        // 27/11/2026: quem cria e atua como vendedor já vem como vendedor
        // responsável (o perfil Vendedor nem consegue trocar — ver
        // OrcamentoRequest::prepareForValidation).
        $orcamento = new Orcamento();
        $usuario = Auth::user();
        if ($usuario->veSoOsProprios() || Usuario::vendedores()->whereKey($usuario->user_id)->exists()) {
            $orcamento->orc_vendedor_id = $usuario->user_id;
            $orcamento->setRelation('vendedor', $usuario);
        }

        return view('orcamentos.form', [
            'orcamento' => $orcamento,
            ...$this->dadosApoioFormulario(),
        ]);
    }

    public function edit(int $id): View
    {
        $orcamento = Orcamento::with(['cliente', 'vendedor', 'respostas', 'comentarios.autor', 'vendedoresAdicionais'])->findOrFail($id);
        $this->garantirVisivel($orcamento);

        return view('orcamentos.form', [
            'orcamento' => $orcamento,
            ...$this->dadosApoioFormulario(),
        ]);
    }

    private function dadosApoioFormulario(): array
    {
        return [
            'vendedores' => $this->vendedoresComerciais(),
            // Vendedor responsável: o perfil Vendedor só se atribui a si mesmo
            // (2026-11-27); os vendedores adicionais (CRM04) seguem a lista toda.
            'vendedoresResponsavel' => Usuario::vendedoresAtribuiveisPor(Auth::user())->orderBy('user_nome')->get(),
            // Perfil Vendedor não altera o vendedor responsável (27/11/2026).
            'vendedorTravado' => Auth::user()->veSoOsProprios(),
            'usuariosAlerta' => $this->usuariosParaAlerta(),
            'tiposOrcamento' => CrmTipoOrcamento::where('crm_tp_orc_ativo', 1)
                ->with(['configModelo.perguntas.opcoes'])
                ->orderBy('crm_tp_orc_nome')
                ->get(),
        ];
    }

    private function vendedoresComerciais()
    {
        // Perfis marcados "Atua como vendedor" em Perfis de Acesso (2026-11-16).
        return Usuario::vendedores()
            ->orderBy('user_nome')
            ->get();
    }

    /**
     * Pedido do cliente (2026-09-17): "Alertar usuário" (CRM03) deve listar
     * Comercial E Vendedor - diferente de "Vendedor responsável"/"Vendedores
     * Adicionais" (CRM04), que continuam só Comercial (nao foi pedido pra
     * mudar esses dois).
     */
    private function usuariosParaAlerta()
    {
        // 2026-11-16: quem consegue abrir o orçamento do link da
        // notificação (permissão orcamentos.ver) — antes Comercial+Vendedor
        // fixo, e o Vendedor recebia um aviso que dava 403 ao abrir.
        return Usuario::comPermissao('orcamentos.ver')
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

    /** Perfil Vendedor (2026-11-27): só os orçamentos em que ele é vendedor. */
    private function garantirVisivel(Orcamento $orcamento): void
    {
        abort_unless($orcamento->visivelPara(Auth::user()), 403, 'Este orçamento pertence a outro vendedor.');
    }

    public function update(OrcamentoRequest $request, int $id): RedirectResponse
    {
        $this->garantirVisivel(Orcamento::findOrFail($id));
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
        $this->garantirVisivel($orcamento);

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
        $this->garantirVisivel($orcamento);
        $comentario = $orcamento->comentarios()->where('orc_com_id', $comentarioId)->firstOrFail();
        // 2026-11-27: só quem incluiu o comentário pode excluí-lo.
        abort_unless((int) $comentario->orc_com_autor_id === (int) Auth::id(), 403, 'Só quem incluiu o comentário pode excluí-lo.');
        $comentario->delete();

        return redirect()
            ->route('orcamentos.edit', $id)
            ->with('success', 'Comentário removido com sucesso.')
            ->with('tab', 'comentarios');
    }
}