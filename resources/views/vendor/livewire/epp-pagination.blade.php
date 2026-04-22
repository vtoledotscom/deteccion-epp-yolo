@if ($paginator->hasPages())
    <nav class="custom-pagination-wrapper" role="navigation" aria-label="Paginación">
        <div class="custom-pagination-summary">
            Mostrando {{ $paginator->firstItem() }} a {{ $paginator->lastItem() }} de {{ $paginator->total() }} eventos
        </div>

        <div class="custom-pagination">
            {{-- Botón anterior --}}
            @if ($paginator->onFirstPage())
                <span class="custom-page-btn disabled">Anterior</span>
            @else
                <button type="button"
                        wire:click="previousPage"
                        wire:loading.attr="disabled"
                        class="custom-page-btn">
                    Anterior
                </button>
            @endif

            {{-- Números --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="custom-page-btn dots">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="custom-page-btn active">{{ $page }}</span>
                        @else
                            <button type="button"
                                    wire:click="gotoPage({{ $page }})"
                                    class="custom-page-btn">
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Botón siguiente --}}
            @if ($paginator->hasMorePages())
                <button type="button"
                        wire:click="nextPage"
                        wire:loading.attr="disabled"
                        class="custom-page-btn">
                    Siguiente
                </button>
            @else
                <span class="custom-page-btn disabled">Siguiente</span>
            @endif
        </div>
    </nav>
@endif