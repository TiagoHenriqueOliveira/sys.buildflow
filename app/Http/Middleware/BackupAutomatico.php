<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Dispara o backup do banco (ver App\Console\Commands\BackupDatabase) a
 * partir do login/uso de um usuário administrador — este ambiente de
 * hospedagem não tem como agendar uma tarefa fora da aplicação.
 *
 * O dia é dividido em 8 janelas fixas de 3h (00-03, 03-06, 06-09, 09-12,
 * 12-15, 15-18, 18-21, 21-24). Na primeira requisição de um admin dentro de
 * cada janela, roda um backup — só uma vez por janela, mesmo que o mesmo
 * admin faça login várias vezes dentro do mesmo intervalo. Técnico logado
 * não dispara nada.
 *
 * Registrado no grupo 'web' (não no middleware global): precisa rodar
 * depois de StartSession pra $request->user() resolver corretamente.
 *
 * Toda a checagem/execução fica em terminate() — método "terminable
 * middleware" do Laravel, chamado só DEPOIS que a resposta já foi enviada
 * ao navegador (ver public/index.php: $response->send() antes de
 * $kernel->terminate()), então quem estiver usando o sistema não espera
 * nada a mais. IMPORTANTE: o Laravel resolve uma instância NOVA desta
 * classe pra chamar terminate() (não reaproveita a instância do handle()),
 * então nada pode ser guardado em propriedade de instância entre os dois —
 * por isso toda a lógica mora só em terminate(), que recebe o $request de
 * novo e consegue checar tudo sozinho.
 */
class BackupAutomatico
{
    private const NIVEL_ADMIN = 0;
    private const TAMANHO_JANELA_HORAS = 3;

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        $usuario = $request->user();

        if (! $usuario || (int) $usuario->user_nivel_acesso !== self::NIVEL_ADMIN) {
            return;
        }

        if (! $this->janelaAindaNaoTeveBackup()) {
            return;
        }

        $this->marcarJanelaAtual();

        try {
            Artisan::call('backup:run');
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function janelaAtual(): string
    {
        $agora = now();
        $horaJanela = intdiv($agora->hour, self::TAMANHO_JANELA_HORAS) * self::TAMANHO_JANELA_HORAS;

        return $agora->format('Y-m-d') . '_' . str_pad((string) $horaJanela, 2, '0', STR_PAD_LEFT);
    }

    private function arquivoMarcador(): string
    {
        return storage_path('app/backup/.ultima_janela');
    }

    private function janelaAindaNaoTeveBackup(): bool
    {
        $dir = storage_path('app/backup');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $marcador = $this->arquivoMarcador();
        $ultimaJanela = file_exists($marcador) ? trim(file_get_contents($marcador)) : null;

        return $ultimaJanela !== $this->janelaAtual();
    }

    private function marcarJanelaAtual(): void
    {
        file_put_contents($this->arquivoMarcador(), $this->janelaAtual());
    }
}
