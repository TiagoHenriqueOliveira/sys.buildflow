<?php

namespace App\Http\Controllers;

use App\Enums\NivelAcesso;
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
        $filtroNome = trim((string) $request->get('f_nome', ''));
        $filtroCnpj = trim((string) $request->get('f_cnpj', ''));
        $filtroCidade = trim((string) $request->get('f_cidade', ''));
        $filtroUf = trim((string) $request->get('f_uf', ''));
        $filtroSegmento = trim((string) $request->get('f_segmento', ''));
        $filtroClassificacao = $request->get('f_classificacao', '');
        $filtroStatus = $request->get('f_status', '');

        $clientes = Cliente::query()
            ->with('classificacao')
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
        return view('clientes.form', [
            'cliente' => new Cliente(),
            ...$this->dadosApoioFormulario(),
        ]);
    }

    public function edit(int $id): View
    {
        $cliente = Cliente::with(['contatos', 'equipamentos', 'localizacoes'])->findOrFail($id);

        return view('clientes.form', [
            'cliente' => $cliente,
            ...$this->dadosApoioFormulario(),
        ]);
    }

    private function dadosApoioFormulario(): array
    {
        return [
            'classificacoes' => ClassificacaoCliente::where('cla_cli_ativo', 1)->orderBy('cla_cli_nome')->get(),
            'vendedores' => Usuario::where('user_nivel_acesso', NivelAcesso::Comercial->value)
                ->where('user_ativo', 1)
                ->orderBy('user_nome')
                ->get(),
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
     */
    public function resumo(Cliente $cliente): JsonResponse
    {
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
