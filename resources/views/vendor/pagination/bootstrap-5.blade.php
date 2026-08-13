@if ($paginator->hasPages())
    <nav aria-label="Page navigation" class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 px-1">

        {{-- Results info --}}
        <p class="small text-muted mb-0">
            Showing
            <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
            to
            <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
            of
            <span class="fw-semibold">{{ $paginator->total() }}</span>
            results
        </p>

        {{-- Page links --}}
        <ul class="pagination pagination-sm mb-0">

            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">‹ Prev</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Prev</a>
                </li>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next ›</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">Next ›</span>
                </li>
            @endif

        </ul>
    </nav>
@endif
