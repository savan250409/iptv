@php
  $current = $paginator->currentPage();
  $last    = $paginator->lastPage();
  $start   = max(1, $current - 2);
  $end     = min($last, $current + 2);
@endphp
<div class="pag-wrap">
  <p class="page-info">
    Showing {{ $paginator->total() ? $paginator->firstItem() : 0 }}
    to {{ $paginator->total() ? $paginator->lastItem() : 0 }}
    of {{ $paginator->total() }} entries
  </p>
  @if ($paginator->hasPages())
  <nav class="pagination">
    <a class="page-link {{ $paginator->onFirstPage() ? 'disabled' : '' }}"
       href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}">‹</a>

    @if ($start > 1)
      <a class="page-link" href="{{ $paginator->url(1) }}">1</a>
      @if ($start > 2) <span class="page-gap">…</span> @endif
    @endif

    @for ($i = $start; $i <= $end; $i++)
      <a class="page-link {{ $i === $current ? 'active' : '' }}" href="{{ $paginator->url($i) }}">{{ $i }}</a>
    @endfor

    @if ($end < $last)
      @if ($end < $last - 1) <span class="page-gap">…</span> @endif
      <a class="page-link" href="{{ $paginator->url($last) }}">{{ $last }}</a>
    @endif

    <a class="page-link {{ $paginator->hasMorePages() ? '' : 'disabled' }}"
       href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}">›</a>
  </nav>
  @endif
</div>
