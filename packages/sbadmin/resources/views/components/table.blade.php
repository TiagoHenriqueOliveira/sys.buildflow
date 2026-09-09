@props([
    'headers' => [],
    'paginator' => null,
    'striped' => true,
    'hover' => true,
    'count' => null,
    'emptyMessage' => 'Nenhum registro encontrado.',
])
<div class="sbadmin-table-card">
    <div class="table-responsive">
        <table {{ $attributes->class([
            'table',
            'sbadmin-table',
            'table-striped' => $striped,
            'table-hover' => $hover,
        ]) }}>
            @if(count($headers))
                <thead>
                    <tr>
                        @foreach($headers as $header)
                            <th scope="col">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody>
                @if(! is_null($count) && (int) $count === 0)
                    <tr>
                        <td colspan="{{ max(count($headers), 1) }}" class="sbadmin-table-empty">
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
            @isset($footer)
                <tfoot>
                    {{ $footer }}
                </tfoot>
            @endisset
        </table>
    </div>

    @if($paginator && method_exists($paginator, 'hasPages') && $paginator->hasPages())
        <div class="sbadmin-table-pagination">
            {{ $paginator->onEachSide(1)->links('sbadmin::pagination') }}
        </div>
    @endif
</div>
