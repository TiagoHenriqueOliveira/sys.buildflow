<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Processa parâmetros do protocolo DataTables server-side e retorna
 * a estrutura de resposta esperada pelo frontend.
 *
 * Uso no controller (branch ajax):
 *
 *   $result = $this->dataTable->process(
 *       $request,
 *       Model::query()->with('relations'),
 *       searchable: ['col_a', 'col_b'],
 *       orderable:  ['acoes' => null, 'col_a' => 'col_a', 'col_b' => 'col_b'],
 *       mapper:     fn($row) => ['acoes' => ..., 'col_a' => $row->col_a],
 *   );
 *   return response()->json($result);
 *
 * @param  string[]    $searchable  Colunas do banco que participam da busca global
 * @param  array<string,string|null> $orderable  Mapa coluna-DT → campo-DB (null = não ordenável)
 * @param  callable    $mapper      Transforma cada row em array para o frontend
 */
class DataTableService
{
    public function process(
        Request  $request,
        Builder  $query,
        array    $searchable,
        array    $orderable,
        callable $mapper
    ): array {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $search = trim((string) $request->input('search.value', ''));

        $total = (clone $query)->count();

        // Busca global
        if ($search !== '') {
            $query->where(function (Builder $q) use ($searchable, $search) {
                foreach ($searchable as $col) {
                    $q->orWhere($col, 'like', '%' . $search . '%');
                }
            });
        }

        $filtered = (clone $query)->count();

        // Ordenação
        $orderCol = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $columnKeys  = array_keys($orderable);
        $orderKey    = $columnKeys[$orderCol] ?? null;
        $dbField     = $orderKey ? ($orderable[$orderKey] ?? null) : null;

        if ($dbField) {
            $query->orderBy($dbField, $orderDir);
        }

        // Paginação
        if ($length > 0) {
            $query->offset($start)->limit($length);
        }

        $data = $query->get()->map($mapper)->filter(fn($row) => $row !== null)->values()->all();

        return [
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ];
    }
}
