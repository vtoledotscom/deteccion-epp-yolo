@if($events->hasPages())
    @php
        $currentPage = $events->currentPage();
        $lastPage = $events->lastPage();
        $candidatePages = [1, $lastPage];

        for ($page = $currentPage - 1; $page <= $currentPage + 1; $page++) {
            if ($page >= 1 && $page <= $lastPage) {
                $candidatePages[] = $page;
            }
        }

        if ($currentPage <= 3) {
            $candidatePages = array_merge($candidatePages, range(1, min(4, $lastPage)));
        }

        if ($currentPage >= $lastPage - 2) {
            $candidatePages = array_merge($candidatePages, range(max(1, $lastPage - 3), $lastPage));
        }

        $pages = array_values(array_unique($candidatePages));
        sort($pages);
        $previousPrintedPage = null;
    @endphp

    <nav class="custom-pagination-wrapper" role="navigation" aria-label="Paginación">
        <div class="custom-pagination-summary">
            Mostrando {{ $events->firstItem() }} a {{ $events->lastItem() }} de {{ $events->total() }} eventos
        </div>

        <div class="custom-pagination">
            @if($events->onFirstPage())
                <span class="custom-page-btn disabled">Anterior</span>
            @else
                <a href="{{ $events->previousPageUrl() }}" class="custom-page-btn">Anterior</a>
            @endif

            @foreach($pages as $page)
                @if($previousPrintedPage !== null && $page > $previousPrintedPage + 1)
                    <span class="custom-page-btn dots">...</span>
                @endif

                @if($page === $currentPage)
                    <span class="custom-page-btn active">{{ $page }}</span>
                @else
                    <a href="{{ $events->url($page) }}" class="custom-page-btn">{{ $page }}</a>
                @endif

                @php($previousPrintedPage = $page)
            @endforeach

            @if($events->hasMorePages())
                <a href="{{ $events->nextPageUrl() }}" class="custom-page-btn">Siguiente</a>
            @else
                <span class="custom-page-btn disabled">Siguiente</span>
            @endif
        </div>
    </nav>
@endif
