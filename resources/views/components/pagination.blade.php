@if ($paginator->hasPages())
    @once
        <style>
            .pagination {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem 1rem;
            }
            .pagination-summary {
                color: var(--text-muted);
                font-size: 0.8rem;
            }
            .pagination-links {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.35rem;
                list-style: none;
            }
            .pagination-link {
                min-width: 36px;
                min-height: 36px;
                justify-content: center;
                padding: 0.4rem 0.65rem;
            }
            .pagination-link[aria-disabled="true"] {
                color: var(--text-muted);
                cursor: default;
                background: var(--bg);
            }
            .pagination-ellipsis {
                padding: 0 0.25rem;
                color: var(--text-muted);
            }
            @media (max-width: 480px) {
                .pagination-links {
                    width: 100%;
                    justify-content: space-between;
                }
                .pagination-page {
                    display: none;
                }
            }
        </style>
    @endonce

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
