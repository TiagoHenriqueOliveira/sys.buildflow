<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Notificacao;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Pedido do cliente (2026-09-16): alerta de recontato via Sistema de
 * Notificacoes (nao mais um badge calculado on-the-fly). Roda 1x/dia (ver
 * app/Console/Kernel.php) - so cria notificacao nova se nao existir uma nao
 * lida do mesmo tipo/cliente ainda, pra nao empilhar notificacao repetida
 * todo dia enquanto o vendedor nao resolve o recontato.
 */
class VerificarRecontatoClientes extends Command
{
    protected $signature = 'clientes:verificar-recontato';

    protected $description = 'Cria notificacao de recontato para clientes atrasados (cli_dias_alerta_recontato).';

    public function handle(): int
    {
        $clientes = Cliente::where('cli_ativo', true)
            ->whereNotNull('cli_dias_alerta_recontato')
            ->whereNotNull('cli_vendedor_id')
            ->get();

        $criadas = 0;

        foreach ($clientes as $cliente) {
            $ultimoContato = $cliente->dataUltimoContato();
            $diasSemContato = $ultimoContato ? $ultimoContato->diffInDays(Carbon::now()) : null;

            $atrasado = $ultimoContato === null || $diasSemContato >= $cliente->cli_dias_alerta_recontato;
            if (! $atrasado) {
                continue;
            }

            $link = route('clientes.edit', $cliente->cli_id);
            $jaNotificado = Notificacao::where('notif_usuario_id', $cliente->cli_vendedor_id)
                ->where('notif_tipo', 'recontato_cliente')
                ->where('notif_link', $link)
                ->where('notif_lida', false)
                ->exists();

            if ($jaNotificado) {
                continue;
            }

            Notificacao::notificar(
                $cliente->cli_vendedor_id,
                'recontato_cliente',
                'Recontato pendente',
                'O cliente "'.$cliente->cli_nome.'" está sem contato há '.($diasSemContato ?? 'muitos').' dias.',
                $link
            );

            $criadas++;
        }

        $this->info("Notificações de recontato criadas: {$criadas}.");

        return self::SUCCESS;
    }
}