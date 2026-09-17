<?php

namespace App\Http\Controllers\Concerns;

use App\Models\FiltroUsuario;
use Illuminate\Support\Facades\Auth;

/**
 * Pedido do cliente (2026-09-17): filtros aplicados em qualquer tela de
 * listagem devem ser lembrados por usuario logado - "nao precise ficar
 * preenchendo sempre". Usado por toda controller com filtro de listagem
 * (ver app/Models/FiltroUsuario.php).
 *
 * Uso: no lugar de ler cada campo direto de $request->get('f_x', ''), o
 * controller chama $this->filtrosPersistentes('tela-unica', ['f_x', 'f_y'])
 * uma vez, e le os valores do array retornado. Se a requisicao atual tiver
 * QUALQUER uma das chaves na querystring, esses valores sao salvos (mesmo
 * vazios - "limpar filtro" tambem deve persistir) e devolvidos; caso
 * contrario (navegacao "limpa", sem querystring de filtro), os valores
 * salvos da ultima vez sao devolvidos como default.
 */
trait PersisteFiltros
{
    protected function filtrosPersistentes(string $tela, array $chaves): array
    {
        $usuario = Auth::user();
        if (! $usuario) {
            return array_fill_keys($chaves, '');
        }

        $request = request();
        $enviadoNestaRequisicao = collect($chaves)->contains(fn ($chave) => $request->has($chave));

        if ($enviadoNestaRequisicao) {
            $valores = collect($chaves)
                ->mapWithKeys(fn ($chave) => [$chave => (string) $request->get($chave, '')])
                ->all();

            FiltroUsuario::updateOrCreate(
                ['filt_usuario_id' => $usuario->user_id, 'filt_tela' => $tela],
                ['filt_valores' => $valores]
            );

            return $valores;
        }

        $salvos = FiltroUsuario::where('filt_usuario_id', $usuario->user_id)
            ->where('filt_tela', $tela)
            ->value('filt_valores') ?? [];

        return collect($chaves)
            ->mapWithKeys(fn ($chave) => [$chave => (string) ($salvos[$chave] ?? '')])
            ->all();
    }
}