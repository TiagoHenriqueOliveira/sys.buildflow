<?php

namespace App\Http\Controllers;

use App\Models\Atendimento;
use App\Models\AtendimentoRelatorio;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StorageController extends Controller
{
    /**
     * Serve arquivos do disco 'public' (storage/app/public, fora de public/ —
     * ver config/filesystems.php) via PHP, com autenticação (ver middleware
     * da rota em routes/web.php). Ficando fora de public/, o Apache nunca
     * resolve essas requisições para um arquivo real — esta é a única forma
     * de acessar esses arquivos.
     *
     * Auditoria 27/11/2026 (S-04): checagem de posse por arquivo, com a mesma
     * regra das telas de atendimento/relatório (AtendimentoPolicy::acessar,
     * a mesma do garantirPosse): o caminho aponta para o registro dono —
     * atendimentos/{id}/... e atendimentos_relatorios/{id}/... — e o resto
     * do disco só para quem tem acesso total. Contrato fae/v1 §9: pelo app,
     * 403 com codigo "sem_acesso_arquivo".
     */
    public function show(Request $request, string $path): Response
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        abort_if($path === '' || in_array('..', explode('/', $path), true), 404);

        if (! $this->podeAcessar($request->user(), $path)) {
            if ($request->expectsJson() || $request->bearerToken()) {
                return response()->json([
                    'message' => 'Você não tem acesso a este arquivo.',
                    'codigo' => 'sem_acesso_arquivo',
                ], 403);
            }
            abort(403, 'Você não tem acesso a este arquivo.');
        }

        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    private function podeAcessar(Usuario $usuario, string $path): bool
    {
        if (preg_match('#^atendimentos/(\d+)/#', $path, $m)) {
            return $this->acessaAtendimento($usuario, Atendimento::find((int) $m[1]));
        }

        if (preg_match('#^atendimentos_relatorios/(\d+)/#', $path, $m)) {
            $relatorio = AtendimentoRelatorio::with('atendimento')->find((int) $m[1]);

            return $this->acessaAtendimento($usuario, $relatorio?->atendimento);
        }

        return $usuario->temAcessoTotal();
    }

    /** Mesma regra de GarantePosseDeAtendimento::garantirPosse(). */
    private function acessaAtendimento(Usuario $usuario, ?Atendimento $atendimento): bool
    {
        return $atendimento
            ? $usuario->can('acessar', $atendimento)
            : Atendimento::veTodos($usuario);
    }
}
