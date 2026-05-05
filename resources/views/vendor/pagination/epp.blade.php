@if ($paginator->hasPages())
    <nav class="custom-pagination-wrapper" role="navigation" aria-label="Paginación">
        <div class="custom-pagination-summary">
            Mostrando {{ $paginator->firstItem() }} a {{ $paginator->lastItem() }} de {{ $paginator->total() }} registros
        </div>

        <div class="custom-pagination">
            @if ($paginator->onFirstPage())
                <span class="custom-page-btn disabled">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="custom-page-btn" rel="prev">Anterior</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="custom-page-btn dots">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="custom-page-btn active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="custom-page-btn">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="custom-page-btn" rel="next">Siguiente</a>
            @else
                <span class="custom-page-btn disabled">Siguiente</span>
            @endif
        </div>
    </nav>
@endif
