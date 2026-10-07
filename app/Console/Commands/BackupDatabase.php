<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Gera um dump só de dados (sem estrutura — as migrations já cobrem isso)
 * de todas as tabelas em storage/app/backup/backup_AAAA-MM-DD_HHMMSS.sql.
 * Cada INSERT lista as colunas explicitamente por nome, então continua
 * restaurável mesmo depois que colunas novas forem adicionadas (a lição do
 * incidente de 2026-08-31: um dump posicional quebra assim que o schema
 * muda; um com colunas nomeadas não). Mantém só os últimos 120 dumps,
 * apagando os mais antigos automaticamente (~30 dias a 4 backups/dia).
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Gera um dump dos dados do banco (só INSERTs) em storage/app/backup';

    private const MANTER = 120;

    public function handle(): int
    {
        $tabelas = collect(DB::select('SHOW TABLES'))
            ->map(fn($row) => array_values((array) $row)[0])
            ->reject(fn($t) => $t === 'migrations');

        $sql = "-- Backup de dados gerado em " . now()->toDateTimeString() . "\n";
        $sql .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        $pdo = DB::getPdo();

        foreach ($tabelas as $tabela) {
            $linhas = DB::table($tabela)->get();
            if ($linhas->isEmpty()) {
                continue;
            }

            $colunas = array_keys((array) $linhas->first());
            $listaColunas = '`' . implode('`,`', $colunas) . '`';

            $grupos = $linhas->map(function ($linha) use ($pdo) {
                $valores = array_map(
                    fn($v) => is_null($v) ? 'NULL' : $pdo->quote((string) $v),
                    (array) $linha
                );
                return '(' . implode(',', $valores) . ')';
            });

            foreach ($grupos->chunk(200) as $lote) {
                $sql .= "INSERT INTO `{$tabela}` ({$listaColunas}) VALUES " . $lote->implode(',') . ";\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $dir = storage_path('app/backup');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $arquivo = $dir . '/backup_' . now()->format('Y-m-d_His') . '.sql';
        file_put_contents($arquivo, $sql);

        $this->info('Backup salvo: ' . basename($arquivo) . ' (' . count($tabelas) . ' tabelas, ' . round(filesize($arquivo) / 1024, 1) . ' KB)');

        $this->prune($dir);

        return 0;
    }

    private function prune(string $dir): void
    {
        $arquivos = collect(glob($dir . '/backup_*.sql'))
            ->sortByDesc(fn($f) => filemtime($f))
            ->values();

        $removidos = $arquivos->slice(self::MANTER);
        foreach ($removidos as $antigo) {
            @unlink($antigo);
        }

        if ($removidos->isNotEmpty()) {
            $this->line('Removidos ' . $removidos->count() . ' backup(s) antigo(s), mantendo os ' . self::MANTER . ' mais recentes.');
        }
    }
}
