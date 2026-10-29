@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 mt-4" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="btn btn-quiet btn-sm" aria-disabled="true">Sebelumnya</span>
        @else
            <a class="btn btn-quiet btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
        @endif
        <span class="caption">Halaman {{ $paginator->currentPage() }}@if(method_exists($paginator, 'lastPage')) dari {{ $paginator->lastPage() }}@endif</span>
        @if ($paginator->hasMorePages())
            <a class="btn btn-quiet btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
        @else
            <span class="btn btn-quiet btn-sm" aria-disabled="true">Berikutnya</span>
        @endif
    </nav>
@endif
