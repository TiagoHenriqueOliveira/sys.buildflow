@if ($paginator->hasPages())
    <nav aria-label="Navegacao de paginas">
        <ul class="pagination sbadmin-pagination">
            {{-- Anterior --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        <span class="visually-hidden">Anterior</span>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Pagina anterior">
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        <span class="visually-hidden">Anterior</span>
                    </a>
                </li>
            @endif

            {{-- Elementos --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Proxima --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Proxima pagina">
                        <span class="visually-hidden">Proxima</span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">
                        <span class="visually-hidden">Proxima</span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </span>
                </li>
            @endif
        </ul>
        <p class="sbadmin-pagination-summary">
            Mostrando <strong>{{ $paginator->firstItem() ?? 0 }}</strong>
            a <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
            de <strong>{{ $paginator->total() }}</strong> resultados
        </p>
    </nav>
@endif
