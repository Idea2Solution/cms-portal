{{-- $p: LengthAwarePaginator --}}
<div class="pager">
  @if ($p->hasPages())
    @if ($p->onFirstPage())<span class="pg dis">‹ Prev</span>@else<a href="{{ $p->previousPageUrl() }}">‹ Prev</a>@endif
    <span>Page {{ $p->currentPage() }} of {{ $p->lastPage() }}</span>
    @if ($p->hasMorePages())<a href="{{ $p->nextPageUrl() }}">Next ›</a>@else<span class="pg dis">Next ›</span>@endif
  @endif
  <span>{{ $p->total() }} {{ Str::plural('record', $p->total()) }}</span>
</div>
