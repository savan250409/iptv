@extends('layouts.app')
@section('title', 'Videos')
@section('subtitle', 'Manage all your Drama videos')

@section('content')
<div class="panel">
  <div class="panel-head">
    <h2>@include('partials.icon', ['name' => 'film', 'cls' => 'icon-sm']) All Videos <span class="count-badge">{{ $videos->total() }}</span></h2>
    <div class="head-actions">
      <button class="btn btn-warning" onclick="openVideoIndexModal()">@include('partials.icon', ['name' => 'menu', 'cls' => 'icon-sm']) Indexing</button>
      <a class="btn btn-primary" href="{{ route('videos.create') }}">@include('partials.icon', ['name' => 'plus', 'cls' => 'icon-sm']) Add Video</a>
    </div>
  </div>

  {{-- Toolbar: show entries + category filter + search --}}
  <form class="table-toolbar" method="get" id="videoFilterForm" action="{{ route('videos.index') }}">
    <div class="tt-left">
      <label class="tt-show">Show
        <select name="per_page" onchange="this.form.submit()">
          @foreach ([10, 25, 50, 100] as $n)
            <option value="{{ $n }}" {{ $perPage === $n ? 'selected' : '' }}>{{ $n }}</option>
          @endforeach
        </select> entries
      </label>
      <select name="category_id" class="tt-status" onchange="this.form.submit()">
        <option value="0">All categories</option>
        @foreach ($allCategories as $c)
          <option value="{{ $c->id }}" {{ $filterCat === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="search-box">
      @include('partials.icon', ['name' => 'search', 'cls' => 'search-ic'])
      <input type="text" name="q" value="{{ $search }}" placeholder="Search videos…">
      @if ($search !== '')
        <a class="search-clear" href="{{ route('videos.index', ['per_page' => $perPage, 'category_id' => $filterCat]) }}" title="Clear">&times;</a>
      @endif
    </div>
  </form>

  @if ($videos->isEmpty())
    <div class="empty">
      <div class="empty-ic">@include('partials.icon', ['name' => 'film'])</div>
      <b>{{ $search !== '' ? 'No videos match your search' : 'No videos yet' }}</b>
      @if ($search === '' && $allCategories->isEmpty())
        You need a category first. <a href="{{ route('categories.index') }}">Create a category →</a>
      @else
        {{ $search !== '' ? 'Try a different keyword.' : 'Click “Add Video” to upload your first one.' }}
      @endif
    </div>
  @else
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>Preview</th><th>Title</th><th>Category</th><th>Added</th><th class="ta-right">Actions</th></tr>
      </thead>
      <tbody>
        @foreach ($videos as $v)
        <tr>
          <td>
            @if ($v->thumbnail_url)
              <img class="video-thumb" src="{{ $v->thumbnail_url }}" alt="">
            @else
              <video class="video-thumb" src="{{ $v->video_url }}#t=0.1" muted preload="metadata"></video>
            @endif
          </td>
          <td class="strong">{{ $v->title }}</td>
          <td><span class="pill"><span class="dot"></span>{{ optional($v->category)->name }}</span></td>
          <td class="muted">{{ optional($v->created_at)->format('d M Y') }}</td>
          <td>
            <div class="row-actions">
              <a class="icon-btn" href="{{ $v->video_url }}" target="_blank" title="Open">@include('partials.icon', ['name' => 'open'])</a>
              <a class="icon-btn" href="{{ route('videos.edit', $v) }}" title="Edit">@include('partials.icon', ['name' => 'edit'])</a>
              <form method="post" action="{{ route('videos.destroy', $v) }}" class="inline js-confirm-delete"
                    data-confirm-title="Delete “{{ e($v->title) }}”?"
                    data-confirm-text="This video will be permanently deleted.">
                @csrf @method('DELETE')
                <input type="hidden" name="keep_filter" value="{{ $filterCat ? 1 : '' }}">
                <button class="icon-btn danger" title="Delete">@include('partials.icon', ['name' => 'trash'])</button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $videos])
  @endif
</div>

{{-- Video Indexing popup: choose a category, then drag to reorder its videos --}}
<div class="modal" id="videoIndexModal">
  <div class="modal-card modal-lg">
    <div class="modal-head">
      <h3>Video Indexing</h3>
      <button class="icon-btn" type="button" onclick="closeModal('videoIndexModal')" title="Close">&times;</button>
    </div>
    <div class="index-info">@include('partials.icon', ['name' => 'alert', 'cls' => 'icon-sm']) Choose a category, then drag &amp; drop to reorder its videos. The new order is saved automatically.</div>

    <label class="field">
      <span>Category</span>
      <select id="videoIndexCategory" onchange="loadVideoIndex()">
        <option value="">— Select a category —</option>
        @foreach ($allCategories as $c)
          <option value="{{ $c->id }}">{{ $c->name }}</option>
        @endforeach
      </select>
    </label>

    <div class="index-note" id="videoIndexNote"></div>
    <ul class="index-list" id="videoIndexList"><li class="index-loading">Select a category to load its videos.</li></ul>
    <div class="index-saving" id="videoIndexSaving"></div>
  </div>
</div>

@push('scripts')
<script>
  const VIDEO_REORDER_LIST = "{{ route('videos.reorder-list') }}";
  const VIDEO_REORDER      = "{{ route('videos.reorder') }}";
  const VCSRF = document.querySelector('meta[name=csrf-token]').content;

  function openVideoIndexModal() {
    document.getElementById('videoIndexCategory').value = '';
    document.getElementById('videoIndexNote').textContent = '';
    document.getElementById('videoIndexSaving').textContent = '';
    document.getElementById('videoIndexList').innerHTML = '<li class="index-loading">Select a category to load its videos.</li>';
    openModal('videoIndexModal');
  }

  function loadVideoIndex() {
    const catId = document.getElementById('videoIndexCategory').value;
    const list = document.getElementById('videoIndexList');
    const note = document.getElementById('videoIndexNote');
    const saving = document.getElementById('videoIndexSaving');
    note.textContent = ''; saving.textContent = '';
    if (!catId) { list.innerHTML = '<li class="index-loading">Select a category to load its videos.</li>'; return; }
    list.innerHTML = '<li class="index-loading">Loading…</li>';

    fetch(VIDEO_REORDER_LIST + '?category_id=' + encodeURIComponent(catId), { headers: { 'Accept': 'application/json' } })
      .then(r => r.json())
      .then(d => {
        list.innerHTML = '';
        if (!d.data.length) { list.innerHTML = '<li class="index-loading">No videos in this category.</li>'; return; }
        d.data.forEach((v, i) => {
          const li = document.createElement('li');
          li.className = 'index-item';
          li.draggable = true;
          li.dataset.id = v.id;
          const thumbTag = v.thumbnail
            ? '<img class="index-thumb">'
            : '<video class="index-thumb" muted preload="metadata"></video>';
          li.innerHTML =
            '<span class="drag-handle"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg></span>' +
            '<span class="index-num">' + (i + 1) + '</span>' +
            thumbTag +
            '<span class="index-name"></span>' +
            '<span class="index-id">ID ' + v.id + '</span>';
          li.querySelector('.index-name').textContent = v.title;
          const thumbEl = li.querySelector('.index-thumb');
          if (v.thumbnail) thumbEl.src = v.thumbnail;
          else if (v.video_url) thumbEl.src = v.video_url + '#t=0.1';
          list.appendChild(li);
        });
        if (d.total > d.data.length) {
          note.textContent = 'Showing first ' + d.data.length + ' of ' + d.total + ' videos. Reordering applies to these.';
        }
      })
      .catch(() => { list.innerHTML = '<li class="index-loading">Failed to load.</li>'; });
  }

  // Drag & drop reordering (listeners on the container → work for dynamic items)
  (function () {
    const list = document.getElementById('videoIndexList');
    if (!list) return;
    list.addEventListener('dragstart', e => {
      const li = e.target.closest('.index-item');
      if (li) { li.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; }
    });
    list.addEventListener('dragend', () => {
      list.querySelector('.dragging')?.classList.remove('dragging');
      saveVideoOrder();
    });
    list.addEventListener('dragover', e => {
      e.preventDefault();
      const dragging = list.querySelector('.dragging');
      if (!dragging) return;
      const after = getAfter(list, e.clientY);
      if (after == null) list.appendChild(dragging);
      else list.insertBefore(dragging, after);
    });

    function getAfter(container, y) {
      const els = [...container.querySelectorAll('.index-item:not(.dragging)')];
      return els.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        return (offset < 0 && offset > closest.offset) ? { offset, element: child } : closest;
      }, { offset: -Infinity }).element;
    }

    let timer = null;
    function saveVideoOrder() {
      const items = [...list.querySelectorAll('.index-item')];
      if (!items.length) return;
      items.forEach((it, i) => it.querySelector('.index-num').textContent = i + 1);
      const ids = items.map(it => it.dataset.id);
      const saving = document.getElementById('videoIndexSaving');
      clearTimeout(timer);
      timer = setTimeout(() => {
        saving.textContent = 'Saving…';
        fetch(VIDEO_REORDER, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': VCSRF, 'Accept': 'application/json' },
          body: JSON.stringify({ ids })
        }).then(r => r.json()).then(() => {
          saving.textContent = '✓ Order saved';
          setTimeout(() => { saving.textContent = ''; }, 1500);
        }).catch(() => { saving.textContent = 'Save failed'; });
      }, 150);
    }
  })();
</script>
@endpush
@endsection
