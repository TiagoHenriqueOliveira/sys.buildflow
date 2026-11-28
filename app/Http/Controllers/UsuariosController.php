<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersisteFiltros;
use App\Enums\NivelAcesso;
use App\Http\Requests\UsuarioRequest;
use App\Models\Usuario;
use App\Repositories\UsuarioRepository;
use App\Services\CodigoAcessoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UsuariosController extends Controller
{
    use PersisteFiltros;

    public function __construct(
        private UsuarioRepository $repository,
    ) {}

    /**
     * Migrada pro pacote sbadmin/dashboard — mesmo padrão das demais telas
     * de cadastro simples (ver CLAUDE.md, seção "Template visual"): sem
     * branch DataTables-JSON, paginação nativa consumida por
     * <x-sbadmin::table>; ordenação descartada (lista sempre por nome). O
     * filtro de usuário "protegido" (admin master, só visível/editável por
     * si mesmo — ver Usuario::isProtegido()) que antes era feito no mapper
     * de linha da DataTables virou uma cláusula WHERE equivalente na query.
     *
     * A busca única (?busca=, que casava nome OU e-mail) foi substituída
     * pelos filtros individuais por coluna abaixo (Nome e E-mail,
     * combináveis entre si em AND), seguindo o mesmo padrão de
     * clientes/atendimentos.
     */
    public function index(Request $request): View
    {
        $filtros = $this->filtrosPersistentes('usuarios', ['f_nome', 'f_email']);
        $filtroNome = $filtros['f_nome'];
        $filtroEmail = $filtros['f_email'];
        // Auditoria S-12 (decisão D-06, 28/11/2026): a conta do fornecedor
        // (usuário protegido) passa a aparecer para os administradores da
        // FAÉ, só para leitura e com o último acesso — antes (pedido de
        // 2026-11-16) ela ficava oculta para todos. Continua editável só
        // por ela mesma, em "Minha conta".
        $usuarios = Usuario::query()
            ->when(! Auth::user()->temAcessoTotal(), fn ($q) => $q->where('user_protegido', 0))
            ->when($filtroNome !== '', fn ($q) => $q->where('user_nome', 'like', "%{$filtroNome}%"))
            ->when($filtroEmail !== '', fn ($q) => $q->where('user_email', 'like', "%{$filtroEmail}%"))
            ->orderByDesc('user_protegido')
            ->orderBy('user_nome')
            ->paginate(15)
            ->withQueryString();

        $protegidos = collect($usuarios->items())->filter(fn (Usuario $u) => $u->isProtegido())->pluck('user_id');
        $ultimosAcessos = $protegidos->isEmpty() ? collect() : \App\Models\LogAuditoria::query()
            ->whereIn('log_aud_usuario_id', $protegidos)
            ->where('log_aud_modulo', 'autenticacao')
            ->where('log_aud_acao', 'login')
            ->groupBy('log_aud_usuario_id')
            ->selectRaw('log_aud_usuario_id, MAX(log_aud_created_at) AS ultimo')
            ->pluck('ultimo', 'log_aud_usuario_id');

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'ultimosAcessos' => $ultimosAcessos,
            'filtroNome' => $filtroNome,
            'filtroEmail' => $filtroEmail,
            'temFiltro' => $filtroNome !== '' || $filtroEmail !== '',
        ]);
    }

    public function store(UsuarioRequest $request, CodigoAcessoService $codigos): RedirectResponse
    {
        $usuario = $this->repository->create($request->validated());

        // S-08 (28/11/2026): a conta nova só cadastra o autenticador com o
        // código de ativação, mostrado aqui uma única vez.
        $gerado = $codigos->gerar($usuario, CodigoAcessoService::ATIVACAO_MFA, $request->user());

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuário "'.$usuario->user_nome.'" cadastrado com sucesso.')
            ->with('codigo_gerado', CodigosAcessoController::paraExibir($usuario, CodigoAcessoService::ATIVACAO_MFA, $gerado));
    }

    public function update(UsuarioRequest $request, int $id): RedirectResponse
    {
        $target = $this->repository->findOrFail($id);

        // Somente o próprio usuário protegido pode editar a si mesmo
        if ($target->isProtegido() && Auth::user()->user_id !== $target->user_id) {
            abort(403, 'Não é permitido alterar o usuário administrador master.');
        }

        // Quem tem "usuarios.editar" sem ser Administrador não altera um
        // Administrador (senão bastaria trocar a senha dele pra ganhar
        // acesso total).
        if ((int) $target->user_nivel_acesso === NivelAcesso::Administrador->value && ! Auth::user()->temAcessoTotal()) {
            abort(403, 'Somente administradores podem alterar um usuário Administrador.');
        }

        $usuario = $this->repository->update($id, $request->validated());

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuário "'.$usuario->user_nome.'" atualizado com sucesso.');
    }
}
