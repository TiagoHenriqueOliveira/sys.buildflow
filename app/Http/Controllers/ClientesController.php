<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersisteFiltros;
use App\Http\Requests\ClienteRequest;
use App\Models\ClassificacaoCliente;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Repositories\ClienteRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientesController extends Controller
{
    use PersisteFiltros;

    public function __construct(
        private ClienteRepository $repository,
    ) {}

    /**
     * Tela de referência da migração pro pacote sbadmin/dashboard (ver
     * CLAUDE.md, seção "Template visual"): a listagem deixou de ser um
     * shell Blade + endpoint JSON no protocolo server-side da DataTables
     * (ver git history / feature/mcl) e passou a ser uma renderização Blade
     * normal com paginação nativa do Eloquent, consumida diretamente por
     * <x-sbadmin::table :paginator="$clientes">. Busca client-side e
     * ordenação por coluna que a DataTables oferecia de graça não têm
     * equivalente direto no novo componente — a ordenação foi descartada
     * nesta fase (lista sempre ordenada por nome; não havia botões de
     * exportação Excel/PDF nesta tela para reavaliar). A busca única
     * (?busca=) foi substituída pelos filtros individuais por coluna abaixo.
     */
    public function index(Request $request): View
    {
        // Filtros individuais por coluna (combinaveis entre si, sempre em
        // AND — cada filtro adicional so restringe mais o resultado): um
        // por coluna exibida em <x-sbadmin::table>, exceto "Ações". Prefixo
        // "f_" no nome do campo evita colisão com outros parâmetros da
        // querystring (page).
        $filtros = $this->filtrosPersistentes('clientes', ['f_nome', 'f_cnpj', 'f_cidade', 'f_uf', 'f_segmento', 'f_classificacao', 'f_status']);
        $filtroNome = $filtros['f_nome'];
        $filtroCnpj = $filtros['f_cnpj'];
        $filtroCidade = $filtros['f_cidade'];
        $filtroUf = $filtros['f_uf'];
        $filtroSegmento = $filtros['f_segmento'];
        $filtroClassificacao = $filtros['f_classificacao'];
        $filtroStatus = $filtros['f_status'];

        $clientes = Cliente::query()
            ->with('classificacao')
            ->visivelPara($request->user())
            ->when($filtroNome !== '', fn ($q) => $q->where('cli_nome', 'like', "%{$filtroNome}%"))
            ->when($filtroCnpj !== '', fn ($q) => $q->where('cli_cnpj', 'like', "%{$filtroCnpj}%"))
            ->when($filtroCidade !== '', fn ($q) => $q->where('cli_cidade', 'like', "%{$filtroCidade}%"))
            ->when($filtroUf !== '', fn ($q) => $q->where('cli_uf', 'like', "%{$filtroUf}%"))
            ->when($filtroSegmento !== '', fn ($q) => $q->where('cli_segmento', 'like', "%{$filtroSegmento}%"))
            ->when($filtroClassificacao !== '', fn ($q) => $q->where('cli_classificacao_id', (int) $filtroClassificacao))
            ->when($filtroStatus !== '', fn ($q) => $q->where('cli_ativo', (int) $filtroStatus))
            ->orderBy('cli_nome')
            ->paginate(15)
            ->withQueryString();

        return view('clientes.index', [
            'clientes' => $clientes,
            'classificacoes' => ClassificacaoCliente::where('cla_cli_ativo', 1)->orderBy('cla_cli_nome')->get(),
            'filtroNome' => $filtroNome,
            'filtroCnpj' => $filtroCnpj,
            'filtroCidade' => $filtroCidade,
            'filtroUf' => $filtroUf,
            'filtroSegmento' => $filtroSegmento,
            'filtroClassificacao' => $filtroClassificacao,
            'filtroStatus' => $filtroStatus,
            'temFiltro' => $filtroNome !== '' || $filtroCnpj !== '' || $filtroCidade !== ''
                || $filtroUf !== '' || $filtroSegmento !== '' || $filtroClassificacao !== '' || $filtroStatus !== '',
        ]);
    }

    public function create(): View
    {
        // 27/11/2026: quem cadastra e atua como vendedor já vem como vendedor
        // responsável (o perfil Vendedor nem consegue trocar — ver
        // ClienteRequest::prepareForValidation).
        $cliente = new Cliente();
        $usuario = auth()->user();
        if ($usuario->veSoOsProprios() || Usuario::vendedores()->whereKey($usuario->user_id)->exists()) {
            $cliente->cli_vendedor_id = $usuario->user_id;
            $cliente->setRelation('vendedor', $usuario);
        }

        return view('clientes.form', [
            'cliente' => $cliente,
            ...$this->dadosApoioFormulario(),
        ]);
    }

    public function edit(int $id): View
    {
        $cliente = Cliente::with(['contatos', 'equipamentos', 'localizacoes'])->findOrFail($id);
        abort_unless($cliente->visivelPara(auth()->user()), 403, 'Este cliente pertence a outro vendedor.');
        // S-03 / D-01: o formulário só traz os contatos que o usuário vê.
        $cliente->setRelation('contatos', $cliente->contatosVisiveisPara(auth()->user()));

        return view('clientes.form', [
            'cliente' => $cliente,
            ...$this->dadosApoioFormulario(),
        ]);
    }

    private function dadosApoioFormulario(): array
    {
        return [
            'classificacoes' => ClassificacaoCliente::where('cla_cli_ativo', 1)->orderBy('cla_cli_nome')->get(),
            'segmentos' => \App\Models\Segmento::where('seg_ativo', 1)->orderBy('seg_descricao')->get(),
            // Perfis marcados "Atua como vendedor" em Perfis de Acesso (2026-11-16);
            // o perfil Vendedor só se atribui a si mesmo (2026-11-27).
            'vendedores' => Usuario::vendedoresAtribuiveisPor(auth()->user())
                ->orderBy('user_nome')
                ->get(),
            // Perfil Vendedor não altera o vendedor responsável (27/11/2026).
            'vendedorTravado' => auth()->user()->veSoOsProprios(),
        ];
    }

    public function store(ClienteRequest $request): RedirectResponse
    {
        $cliente = $this->repository->create($request->validated());

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente "'.$cliente->cli_nome.'" cadastrado com sucesso.');
    }

    public function update(ClienteRequest $request, int $id): RedirectResponse
    {
        abort_unless(Cliente::findOrFail($id)->visivelPara($request->user()), 403, 'Este cliente pertence a outro vendedor.');
        $cliente = $this->repository->update($id, $request->validated());

        // Pedido do cliente (2026-09-11): ao editar, permanece na tela de
        // edição (para continuar ajustando outras abas) em vez de voltar
        // pra listagem — diferente do cadastro novo, que ainda redireciona
        // pra listagem logo abaixo em store().
        // Pedido do cliente (2026-09-14): "Geral" - o mesmo problema de perder
        // a aba ativa apos o redirect (corrigido antes em Orcamento >
        // Comentarios) se aplica a qualquer form com abas que faz POST/PUT
        // comum (sem AJAX); aqui a aba ativa vem num input hidden
        // (tab_ativa, setado via x-model) e volta via flash pro x-data ler.
        return redirect()
            ->route('clientes.edit', $cliente->cli_id)
            ->with('success', 'Cliente "'.$cliente->cli_nome.'" atualizado com sucesso.')
            ->with('tab', $request->input('tab_ativa', 'dados'));
    }

    public function autoComplete(Request $request): JsonResponse
    {
        $term = $request->get('term', '');

        $clientes = Cliente::where('cli_ativo', 1)
            ->visivelPara($request->user())
            ->where('cli_nome', 'like', '%' . $term . '%')
            ->orderBy('cli_nome')
            ->limit(20)
            ->get();

        $result = $clientes->map(function ($c) {
            return [
                'id'    => $c->cli_id,
                'label' => $c->cli_nome,
                'value' => $c->cli_nome,
            ];
        })->values()->all();

        return response()->json($result);
    }

    /**
     * BF03 — resumo somente leitura do cliente, consumido pela tela de
     * Atendimento (qualquer usuário autenticado, inclusive técnico sem
     * acesso ao CRUD de Clientes) para exibir os dados sem navegação extra.
     *
     * Auditoria 27/11/2026 (S-07): continua liberado para quem usa a tela de
     * Atendimento (atendimentos.ver/editar) ou o cadastro de Clientes
     * (clientes.ver), mas respeita a visibilidade do cliente — o perfil
     * Vendedor não lê o resumo de cliente de outro vendedor.
     */
    public function resumo(Request $request, Cliente $cliente): JsonResponse
    {
        $usuario = $request->user();
        abort_unless(
            $usuario->can('atendimentos.editar') || $usuario->can('atendimentos.ver') || $usuario->can('clientes.ver'),
            403
        );
        abort_unless($cliente->visivelPara($usuario), 403, 'Este cliente pertence a outro vendedor.');

        $cliente->load('classificacao');

        return response()->json([
            'cli_id' => $cliente->cli_id,
            'cli_nome' => $cliente->cli_nome,
            'cli_contato_principal' => $cliente->cli_contato_principal,
            'cli_segmento' => $cliente->cli_segmento,
            'classificacao' => $cliente->classificacao?->cla_cli_nome,
            'cli_cidade' => $cliente->cli_cidade,
            'cli_uf' => $cliente->cli_uf,
            'cli_telefone' => $cliente->cli_telefone,
            'cli_email' => $cliente->cli_email,
        ]);
    }
}
