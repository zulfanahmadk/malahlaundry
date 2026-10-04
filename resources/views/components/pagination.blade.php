@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Navigasi halaman">
        <p class="pagination-summary">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
            · Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
        </p>
        <ul class="pagination-links">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="btn btn-secondary pagination-link" aria-disabled="true">Sebelumnya</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-secondary pagination-link" rel="prev">Sebelumnya</a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="pagination-page pagination-ellipsis" aria-hidden="true">{{ $element }}</li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="pagination-page">
                            @if ($page == $paginator->currentPage())
                                <span class="btn btn-primary pagination-link" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-secondary pagination-link" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-secondary pagination-link" rel="next">Berikutnya</a>
                @else
                    <span class="btn btn-secondary pagination-link" aria-disabled="true">Berikutnya</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
