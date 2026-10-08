@extends('layouts.app')
@section('title', 'Categories')
@section('subtitle', 'Manage all your Drama categories')

@section('content')
<div class="panel">
  <div class="panel-head">
    <h2>@include('partials.icon', ['name' => 'grid', 'cls' => 'icon-sm']) All Categories <span class="count-badge">{{ $categories->total() }}</span></h2>
    <div class="head-actions">
      <button class="btn btn-warning" onclick="openIndexModal()">@include('partials.icon', ['name' => 'menu', 'cls' => 'icon-sm']) Indexing</button>
      <button class="btn btn-primary" onclick="openCategoryModal('create')">@include('partials.icon', ['name' => 'plus', 'cls' => 'icon-sm']) Add Category</button>
    </div>
  </div>

  {{-- Toolbar: show entries + status filter + search --}}
  <form class="table-toolbar" method="get" id="catFilterForm" action="{{ route('categories.index') }}">
    <div class="tt-left">
      <label class="tt-show">Show
        <select name="per_page" onchange="this.form.submit()">
          @foreach ([10, 25, 50, 100] as $n)
            <option value="{{ $n }}" {{ $perPage === $n ? 'selected' : '' }}>{{ $n }}</option>
          @endforeach
        </select> entries
      </label>
      <select name="status" class="tt-status" onchange="this.form.submit()">
        <option value="">All status</option>
        <option value="active"   {{ $status === 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
      </select>
    </div>
    <div class="search-box">
      @include('partials.icon', ['name' => 'search', 'cls' => 'search-ic'])
      <input type="text" name="q" value="{{ $search }}" placeholder="Search categories…">
      @if ($search !== '')
        <a class="search-clear" href="{{ route('categories.index', ['per_page' => $perPage, 'status' => $status]) }}" title="Clear">&times;</a>
      @endif
    </div>
  </form>

  @if ($categories->isEmpty())
    <div class="empty">
      <div class="empty-ic">@include('partials.icon', ['name' => 'folder'])</div>
      <b>{{ $search !== '' ? 'No categories match your search' : 'No categories yet' }}</b>
      {{ $search !== '' ? 'Try a different keyword.' : 'Click “Add Category” to create your first one.' }}
    </div>
  @else
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>Image</th><th>Category Name</th><th>Videos</th><th>Status</th><th class="ta-right">Actions</th></tr>
      </thead>
      <tbody>
        @foreach ($categories as $c)
        <tr>
          <td>
            @if ($c->image_url)
              <img class="thumb" src="{{ $c->image_url }}" alt="">
            @else
              <div class="thumb thumb-empty">@include('partials.icon', ['name' => 'image', 'cls' => 'icon-sm'])</div>
            @endif
          </td>
          <td class="strong">{{ $c->name }}</td>
          <td><span class="count-badge">{{ $c->videos_count }}</span></td>
          <td>
            <div class="status-cell">
              <label class="switch switch-sm">
                <input type="checkbox" {{ $c->is_active ? 'checked' : '' }} onchange="toggleCategory({{ $c->id }}, this)">
                <span class="slider"></span>
              </label>
              <span class="status {{ $c->is_active ? 'status-on' : 'status-off' }}" data-status-badge>
                <span class="status-dot"></span>{{ $c->is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </td>
          <td>
            <div class="row-actions">
              <button class="icon-btn" title="Edit"
                data-id="{{ $c->id }}"
                data-name="{{ $c->name }}"
                data-active="{{ $c->is_active ? 1 : 0 }}"
                data-image="{{ $c->image_url }}"
                onclick="editCategory(this)">
                @include('partials.icon', ['name' => 'edit'])
              </button>
              <form method="post" action="{{ route('categories.destroy', $c) }}" class="inline js-confirm-delete"
                    data-confirm-title="Delete “{{ e($c->name) }}”?"
                    data-confirm-text="This category and all its videos will be permanently deleted.">
                @csrf @method('DELETE')
                <button class="icon-btn danger" title="Delete">@include('partials.icon', ['name' => 'trash'])</button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @include('partials.pagination', ['paginator' => $categories])
  @endif
</div>

{{-- Add/Edit modal --}}
<div class="modal" id="categoryModal">
  <form class="modal-card" method="post" enctype="multipart/form-data" id="categoryForm"
        action="{{ route('categories.store') }}">
    @csrf
    <input type="hidden" name="_method" id="categoryMethod" value="POST">
    <h3 id="categoryModalTitle">Add Category</h3>

    <label class="field">
      <span>Category name</span>
      <input type="text" name="name" id="categoryName" required maxlength="190" placeholder="e.g. News, Sports, Movies">
    </label>
    <label class="field">
      <span>Category image <small class="muted">(WebP only, max 5&nbsp;MB)</small></span>
      <input type="file" name="image" id="categoryImage" accept="image/webp,.webp">
    </label>

    <div class="img-preview" id="categoryPreview" style="display:none">
      <img id="categoryPreviewImg" src="" alt="Preview">
      <span class="muted">Image preview</span>
    </div>

    <div class="switch-field">
      <div class="switch-text">
        <b>Active</b>
        <small class="muted">When on, this category shows in the API response</small>
      </div>
      <label class="switch">
        <input type="checkbox" name="is_active" id="categoryActive" value="1" checked>
        <span class="slider"></span>
      </label>
    </div>

    <div class="modal-actions">
      <button type="button" class="btn" onclick="closeModal('categoryModal')">Cancel</button>
      <button type="submit" class="btn btn-primary">Save Category</button>
    </div>
  </form>
</div>

{{-- Indexing (drag & drop reorder) modal --}}
<div class="modal" id="indexModal">
  <div class="modal-card modal-lg">
    <div class="modal-head">
      <h3>Category Indexing</h3>
      <button class="icon-btn" type="button" onclick="closeModal('indexModal')" title="Close">&times;</button>
    </div>
    <div class="index-info">@include('partials.icon', ['name' => 'alert', 'cls' => 'icon-sm']) Drag &amp; drop categories to reorder them. The new order is saved automatically.</div>
    <div class="index-note" id="indexNote"></div>
    <ul class="index-list" id="indexList"><li class="index-loading">Loading…</li></ul>
    <div class="index-saving" id="indexSaving"></div>
  </div>
</div>

@push('scripts')
<script>
  const CATEGORY_STORE   = "{{ route('categories.store') }}";
  const CATEGORY_UPDATE  = "{{ url('categories') }}";
  const CATEGORY_REORDER = "{{ route('categories.reorder') }}";
  const CATEGORY_TOGGLE  = "{{ url('categories') }}/__ID__/toggle";
  const CATEGORY_INDEX_LIST = "{{ route('categories.index-list') }}";
  const CSRF = document.querySelector('meta[name=csrf-token]').content;

  function openCategoryModal(mode, data) {
    const form = document.getElementById('categoryForm');
    const fileInput = document.getElementById('categoryImage');
    const preview = document.getElementById('categoryPreview');
    const previewImg = document.getElementById('categoryPreviewImg');

    document.getElementById('categoryModalTitle').textContent = mode === 'update' ? 'Edit Category' : 'Add Category';
    document.getElementById('categoryName').value = data?.name ?? '';
    document.getElementById('categoryActive').checked = mode === 'update' ? !!data.active : true;
    fileInput.value = '';

    // Show existing image on edit, otherwise hide the preview.
    if (mode === 'update' && data.image) {
      previewImg.src = data.image;
      preview.style.display = 'flex';
    } else {
      previewImg.src = '';
      preview.style.display = 'none';
    }

    if (mode === 'update') {
      form.action = CATEGORY_UPDATE + '/' + data.id;
      document.getElementById('categoryMethod').value = 'PUT';
    } else {
      form.action = CATEGORY_STORE;
      document.getElementById('categoryMethod').value = 'POST';
    }
    openModal('categoryModal');
  }

  // Live thumbnail preview when a file is picked.
  document.getElementById('categoryImage').addEventListener('change', function () {
    const file = this.files && this.files[0];
    const preview = document.getElementById('categoryPreview');
    const previewImg = document.getElementById('categoryPreviewImg');
    if (file) {
      previewImg.src = URL.createObjectURL(file);
      preview.style.display = 'flex';
    } else {
      previewImg.src = '';
      preview.style.display = 'none';
    }
  });

  function editCategory(btn) {
    openCategoryModal('update', {
      id: btn.dataset.id,
      name: btn.dataset.name,
      active: btn.dataset.active === '1',
      image: btn.dataset.image || ''
    });
  }

  // Load the indexing list lazily (keeps the main page fast even with huge data).
  // Submit add/edit via AJAX → errors show as a popup, the modal & data stay put.
  document.getElementById('categoryForm').addEventListener('submit', function (e) {
    e.preventDefault();
    ajaxSubmit(this).then(handleAjaxResult);
  });

  function openIndexModal() {
    const list = document.getElementById('indexList');
    const note = document.getElementById('indexNote');
    const saving = document.getElementById('indexSaving');
    saving.textContent = '';
    note.textContent = '';
    list.innerHTML = '<li class="index-loading">Loading…</li>';
    openModal('indexModal');

    fetch(CATEGORY_INDEX_LIST, { headers: { 'Accept': 'application/json' } })
      .then(r => r.json())
      .then(d => {
        list.innerHTML = '';
        if (!d.data.length) { list.innerHTML = '<li class="index-loading">No categories to order yet.</li>'; return; }
        d.data.forEach((c, i) => {
          const li = document.createElement('li');
          li.className = 'index-item';
          li.draggable = true;
          li.dataset.id = c.id;
          li.innerHTML =
            '<span class="drag-handle"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg></span>' +
            '<span class="index-num">' + (i + 1) + '</span>' +
            '<span class="index-name"></span>' +
            '<span class="index-id">ID ' + c.id + '</span>';
          li.querySelector('.index-name').textContent = c.name; // safe (no HTML injection)
          list.appendChild(li);
        });
        if (d.total > d.data.length) {
          note.textContent = 'Showing first ' + d.data.length + ' of ' + d.total + ' categories. Reordering applies to these.';
        }
      })
      .catch(() => { list.innerHTML = '<li class="index-loading">Failed to load.</li>'; });
  }

  // Inline status toggle
  function toggleCategory(id, el) {
    fetch(CATEGORY_TOGGLE.replace('__ID__', id), {
      method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => {
      const badge = el.closest('.status-cell').querySelector('[data-status-badge]');
      badge.className = 'status ' + (d.is_active ? 'status-on' : 'status-off');
      badge.innerHTML = '<span class="status-dot"></span>' + (d.is_active ? 'Active' : 'Inactive');
    }).catch(() => { el.checked = !el.checked; });
  }

  // Drag & drop reordering
  (function () {
    const list = document.getElementById('indexList');
    if (!list) return;
    list.addEventListener('dragstart', e => {
      const li = e.target.closest('.index-item');
      if (li) { li.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; }
    });
    list.addEventListener('dragend', () => {
      list.querySelector('.dragging')?.classList.remove('dragging');
      saveOrder();
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

    let saveTimer = null;
    function saveOrder() {
      const items = [...list.querySelectorAll('.index-item')];
      items.forEach((it, i) => it.querySelector('.index-num').textContent = i + 1);
      const ids = items.map(it => it.dataset.id);
      const saving = document.getElementById('indexSaving');
      clearTimeout(saveTimer);
      saveTimer = setTimeout(() => {
        saving.textContent = 'Saving…';
        fetch(CATEGORY_REORDER, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
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
