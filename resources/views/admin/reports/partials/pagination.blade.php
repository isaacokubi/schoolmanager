@if($paginator->hasPages())
<div class="report-pagination" role="navigation" aria-label="{{ ucfirst($label) }} pagination">
    <div class="meta">
        Showing {{ number_format($paginator->firstItem() ?? 0) }}–{{ number_format($paginator->lastItem() ?? 0) }} of {{ number_format($paginator->total()) }} {{ $label }}
    </div>
    <div class="links">
        @if($paginator->onFirstPage())
            <span class="disabled" aria-disabled="true">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous {{ $label }} page">Previous</a>
        @endif

        @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            @if($page === $paginator->currentPage())
                <span class="current" aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $url }}" aria-label="{{ $label }} page {{ $page }}">{{ $page }}</a>
            @endif
        @endforeach

        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next {{ $label }} page">Next</a>
        @else
            <span class="disabled" aria-disabled="true">Next</span>
        @endif
    </div>
</div>
@endif
