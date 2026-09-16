@if ($paginator->hasPages())
    <nav class="admin-pagination" role="navigation" aria-label="Pagination">
        <div class="admin-pagination__summary">
            Showing <strong>{{ $paginator->firstItem() ?: 0 }}</strong>–<strong>{{ $paginator->lastItem() ?: 0 }}</strong>
            of <strong>{{ $paginator->total() }}</strong> results
        </div>

        <div class="admin-pagination__controls">
            @if ($paginator->onFirstPage())
                <span class="admin-page admin-page--disabled" aria-disabled="true">← Previous</span>
            @else
                <a class="admin-page" href="{{ $paginator->previousPageUrl() }}" rel="prev">← Previous</a>
            @endif

            <div class="admin-pagination__pages" aria-label="Page numbers">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="admin-page admin-page--ellipsis" aria-hidden="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="admin-page admin-page--current" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="admin-page" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a class="admin-page" href="{{ $paginator->nextPageUrl() }}" rel="next">Next →</a>
            @else
                <span class="admin-page admin-page--disabled" aria-disabled="true">Next →</span>
            @endif
        </div>
    </nav>

    <style>
        .admin-pagination{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;width:100%;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .admin-pagination__summary{color:#63748a;font-size:11px;line-height:1.5}
        .admin-pagination__summary strong{color:#173b68;font-weight:800}
        .admin-pagination__controls{display:flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:wrap}
        .admin-pagination__pages{display:flex;align-items:center;gap:4px}
        .admin-page{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 10px;box-sizing:border-box;border:1px solid #dfe7f0;border-radius:9px;background:#fff;color:#35526f;text-decoration:none;font-size:11px;font-weight:750;line-height:1;transition:background .18s ease,border-color .18s ease,color .18s ease,transform .18s ease}
        a.admin-page:hover{background:#eaf4ff;border-color:#a9d0f1;color:#0d5d99;transform:translateY(-1px)}
        .admin-page--current{background:#1769aa;border-color:#1769aa;color:#fff;box-shadow:0 5px 12px rgba(23,105,170,.18)}
        .admin-page--disabled{background:#f4f7fa;border-color:#e7edf3;color:#a0adba;cursor:not-allowed}
        .admin-page--ellipsis{min-width:26px;padding:0;background:transparent;border-color:transparent;color:#8190a1;cursor:default}
        @media(max-width:700px){.admin-pagination{align-items:stretch;flex-direction:column}.admin-pagination__summary{text-align:center}.admin-pagination__controls{justify-content:center}.admin-pagination__pages{max-width:100%;overflow-x:auto;padding-bottom:2px;scrollbar-width:thin}.admin-page{min-width:32px;height:32px;padding:0 8px}.admin-pagination__controls>.admin-page{flex:0 0 auto}}
    </style>
@endif
