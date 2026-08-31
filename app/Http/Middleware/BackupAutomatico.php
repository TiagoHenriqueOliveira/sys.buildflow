<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Dispara o backup do banco (ver App\Console\Commands\BackupDatabase) a
 * partir do uso normal do sistema — este ambiente de hospedagem não tem
 * como agendar uma tarefa fora da aplicação. A cada requisição, checa
 * (rápido, só timestamp de um arquivo) se faz mais de 3h desde a última
 * tentativa; se sim, roda um backup novo.
 *
 * O backup roda em terminate() — método "terminable middleware" do Laravel,
 * chamado só DEPOIS que a resposta já foi enviada ao navegador (ver
 * public/index.php: $response->send() acontece antes de $kernel->terminate()).
 * Quem estiver usando o sistema não espera nada a mais; o backup roda
 * depois, sem atrasar a página.
 *
 * Não é preciso num horário exato — depende de alguém estar usando o
 * sistema (web ou app mobile) — mas qualquer uso durante o horário
 * comercial mantém backups espalhados ao longo do dia.
 */
class BackupAutomatico
{
    private const INTERVALO_HORAS = 3;

    private bool $deveRodar = false;

    public function handle(Request $request, Closure $next)
    {
        $this->deveRodar = $this->precisaRodar();

        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        if (! $this->deveRodar) {
            return;
        }

        // Marca a tentativa ANTES de rodar, pra uma requisição quase
        // simultânea não disparar um segundo backup ao mesmo tempo.
        touch(storage_path('app/backup') . '/.ultima_tentativa');

        try {
            Artisan::call('backup:run');
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function precisaRodar(): bool
    {
        $dir = storage_path('app/backup');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $marcador = $dir . '/.ultima_tentativa';
        $ultimaTentativa = file_exists($marcador) ? filemtime($marcador) : 0;

        return $ultimaTentativa <= now()->subHours(self::INTERVALO_HORAS)->timestamp;
    }
}
