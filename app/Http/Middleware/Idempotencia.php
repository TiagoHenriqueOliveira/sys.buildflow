<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrato fae/v1 §4 (auditoria S-14): "Idempotency-Key" nos POST que
 * criam algo. Fica no grupo protegido da API (antes das permissões e do
 * bloqueio de relatório aprovado, para a retentativa de uma gravação já
 * feita receber a mesma resposta) e só age nas rotas de ROTAS.
 *
 * - mesma chave + mesmo conteúdo: devolve a resposta guardada com
 *   "Idempotent-Replayed: true";
 * - mesma chave + conteúdo diferente: 409 "idempotencia_conflito";
 * - mesma chave ainda em processamento: 409 "idempotencia_em_andamento";
 * - sem a chave: segue como antes enquanto API_EXIGIR_IDEMPOTENCIA=false
 *   (senão 428 "idempotencia_obrigatoria").
 *
 * Guarda 2xx e as recusas de regra (403/404/409/422/423); 401, 419, 426,
 * 429 e 5xx não ficam guardados — a retentativa roda de novo. Registros
 * com mais de 7 dias são apagados aos poucos.
 */
class Idempotencia
{
    public const ROTAS = [
        'api/fae/v1/atendimentos/{aten_id}/relatorios',
        'api/fae/v1/relatorios/{id}/respostas',
        'api/fae/v1/relatorios/{id}/anexos',
        'api/fae/v1/relatorios/{id}/assinaturas',
        'api/fae/v1/relatorios/{id}/compartilhamentos',
        'api/fae/v1/relatorios/{id}/descricao-itens',
        'api/fae/v1/relatorios/{id}/servicos',
        'api/fae/v1/relatorios/{id}/pecas',
        'api/fae/v1/relatorios/{id}/ocorrencias',
        'api/fae/v1/clientes',
        'api/fae/v1/orcamentos',
        'api/fae/v1/orcamentos/{id}/comentarios',
        'api/fae/v1/roteiros-viagem',
    ];

    private const GUARDAR = [403, 404, 409, 422, 423];

    private const DIAS = 7;

    /** Processamento parado há mais que isso (queda no meio) é tratado como abandonado. */
    private const ABANDONO_MINUTOS = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $request->isMethod('POST') || ! $usuario || ! in_array($request->route()?->uri(), self::ROTAS, true)) {
            return $next($request);
        }

        $chave = $request->header('Idempotency-Key');

        if ($chave === null || $chave === '') {
            if (config('api_fae.exigir_idempotencia')) {
                return response()->json(['message' => 'Envie o cabeçalho Idempotency-Key.', 'codigo' => 'idempotencia_obrigatoria'], 428);
            }

            return $next($request);
        }

        if (! preg_match('/^[A-Za-z0-9-]{16,64}$/', $chave)) {
            return response()->json(['message' => 'Idempotency-Key inválida (use um UUID).', 'codigo' => 'idempotencia_chave_invalida'], 422);
        }

        $hash = $this->hashDoConteudo($request);
        $tabela = DB::table('api_idempotencia');

        try {
            $id = $tabela->insertGetId([
                'idem_usuario_id' => $usuario->user_id,
                'idem_chave' => $chave,
                'idem_rota' => $request->path(),
                'idem_hash' => $hash,
                'idem_criado_em' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            $existente = DB::table('api_idempotencia')
                ->where('idem_usuario_id', $usuario->user_id)->where('idem_chave', $chave)->first();

            if (! $existente) {
                return response()->json(['message' => 'Gravação em andamento. Tente de novo em instantes.', 'codigo' => 'idempotencia_em_andamento'], 409);
            }

            if (! hash_equals($existente->idem_hash, $hash) || $existente->idem_rota !== $request->path()) {
                return response()->json(['message' => 'Esta chave já foi usada com outro conteúdo.', 'codigo' => 'idempotencia_conflito'], 409);
            }

            if ($existente->idem_concluido_em !== null) {
                return response((string) $existente->idem_corpo, (int) $existente->idem_status)
                    ->header('Content-Type', 'application/json')
                    ->header('Idempotent-Replayed', 'true');
            }

            if (now()->diffInMinutes($existente->idem_criado_em, true) < self::ABANDONO_MINUTOS) {
                return response()->json(['message' => 'Gravação em andamento. Tente de novo em instantes.', 'codigo' => 'idempotencia_em_andamento'], 409);
            }

            // Ficou para trás (queda no meio): esta tentativa assume.
            DB::table('api_idempotencia')->where('idem_id', $existente->idem_id)->update(['idem_criado_em' => now()]);
            $id = $existente->idem_id;
        }

        try {
            $resposta = $next($request);
        } catch (\Throwable $e) {
            DB::table('api_idempotencia')->where('idem_id', $id)->delete();
            throw $e;
        }

        $status = $resposta->getStatusCode();
        if (($status >= 200 && $status < 300) || in_array($status, self::GUARDAR, true)) {
            DB::table('api_idempotencia')->where('idem_id', $id)->update([
                'idem_status' => $status,
                'idem_corpo' => (string) $resposta->getContent(),
                'idem_concluido_em' => now(),
            ]);
        } else {
            DB::table('api_idempotencia')->where('idem_id', $id)->delete();
        }

        if (random_int(1, 50) === 1) {
            DB::table('api_idempotencia')->where('idem_criado_em', '<', now()->subDays(self::DIAS))->delete();
        }

        return $resposta;
    }

    /** Método + caminho + campos (ordenados) + SHA-256 de cada arquivo enviado. */
    private function hashDoConteudo(Request $request): string
    {
        $campos = $request->except(array_keys($request->allFiles()));
        $this->ordenar($campos);

        $arquivos = [];
        $this->hashArquivos($request->allFiles(), '', $arquivos);
        ksort($arquivos);

        return hash('sha256', $request->method() . ' ' . $request->path() . "\n"
            . json_encode($campos, JSON_UNESCAPED_UNICODE) . "\n" . json_encode($arquivos));
    }

    private function ordenar(array &$dados): void
    {
        ksort($dados);
        foreach ($dados as &$valor) {
            if (is_array($valor)) {
                $this->ordenar($valor);
            }
        }
    }

    private function hashArquivos(array $arquivos, string $prefixo, array &$saida): void
    {
        foreach ($arquivos as $campo => $arquivo) {
            $nome = $prefixo === '' ? (string) $campo : "{$prefixo}[{$campo}]";
            if (is_array($arquivo)) {
                $this->hashArquivos($arquivo, $nome, $saida);
            } elseif ($arquivo instanceof UploadedFile) {
                $saida[$nome] = hash_file('sha256', $arquivo->getRealPath());
            }
        }
    }
}
