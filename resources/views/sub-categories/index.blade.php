@extends('layouts.app')
@section('title', 'Sub Categories')

@section('content')
<div class="panel">
  <div class="panel-head">
    <h2>@include('partials.icon', ['name' => 'folder', 'cls' => 'icon-sm']) All Sub Categories <span class="count-badge">{{ $subCategories->total() }}</span></h2>
    <div class="head-actions">
      <button class="btn btn-primary" onclick="openSubModal('create')">@include('partials.icon', ['name' => 'plus', 'cls' => 'icon-sm']) Add Sub Category</button>
    </div>
  </div>

  <form class="table-toolbar" method="get" id="subFilterForm" action="{{ route('sub-categories.index') }}">
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
      <input type="text" name="q" value="{{ $search }}" placeholder="Search sub-categories…">
      @if ($search !== '')
        <a class="search-clear" href="{{ route('sub-categories.index', ['per_page' => $perPage, 'category_id' => $filterCat]) }}" title="Clear">&times;</a>
      @endif
    </div>
  </form>

  @if ($subCategories->isEmpty())
    <div class="empty">
      <div class="empty-ic">@include('partials.icon', ['name' => 'folder'])</div>
      <b>{{ $search !== '' ? 'No sub-categories match your search' : 'No sub-categories yet' }}</b>
      @if ($allCategories->isEmpty())
        You need a category first. <a href="{{ route('categories.index') }}">Create a category →</a>
      @else
        Click “Add Sub Category” to create one.
      @endif
    </div>
  @else
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>Image</th><th>Sub Category</th><th>Category</th><th>Videos</th><th>Status</th><th class="ta-right">Actions</th></tr>
      </thead>
      <tbody>
        @foreach ($subCategories as $s)
        <tr>
          <td>
            @if ($s->image_url)
              <img class="thumb" src="{{ $s->image_url }}" alt="">
            @else
              <div class="thumb thumb-empty">@include('partials.icon', ['name' => 'image', 'cls' => 'icon-sm'])</div>
            @endif
          </td>
          <td class="strong">{{ $s->name }}</td>
          <td><span class="pill"><span class="dot"></span>{{ optional($s->category)->name }}</span></td>
          <td><span class="count-badge">{{ $s->videos_count }}</span></td>
          <td>
            @if ($s->is_active)
              <span class="status status-on"><span class="status-dot"></span>Active</span>
            @else
              <span class="status status-off"><span class="status-dot"></span>Inactive</span>
            @endif
          </td>
          <td>
            <div class="row-actions">
              <button class="icon-btn" title="Edit"
                data-id="{{ $s->id }}" data-category="{{ $s->category_id }}" data-name="{{ $s->name }}"
                data-active="{{ $s->is_active ? 1 : 0 }}" data-image="{{ $s->image_url }}"
                onclick="editSub(this)">@include('partials.icon', ['name' => 'edit'])</button>
              <form method="post" action="{{ route('sub-categories.destroy', $s) }}" class="inline js-confirm-delete"
                    data-confirm-title="Delete “{{ e($s->name) }}”?"
                    data-confirm-text="This sub-category will be removed (its videos stay, but lose this sub-category).">
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
  @include('partials.pagination', ['paginator' => $subCategories])
  @endif
</div>

{{-- Add/Edit modal --}}
<div class="modal" id="subModal">
  <form class="modal-card" method="post" enctype="multipart/form-data" id="subForm" action="{{ route('sub-categories.store') }}">
    @csrf
    <input type="hidden" name="_method" id="subMethod" value="POST">
    <h3 id="subModalTitle">Add Sub Category</h3>

    <label class="field">
      <span>Category</span>
      <select name="category_id" id="subCategory" required>
        <option value="">— Select category —</option>
        @foreach ($allCategories as $c)
          <option value="{{ $c->id }}">{{ $c->name }}</option>
        @endforeach
      </select>
    </label>

    <label class="field">
      <span>Sub category name</span>
      <input type="text" name="name" id="subName" required maxlength="190" placeholder="e.g. Season 1">
    </label>

    <label class="field">
      <span>Sub category image <small class="muted">(WebP only, max 5&nbsp;MB)</small></span>
      <input type="file" name="image" id="subImage" accept="image/webp,.webp">
    </label>

    <div class="img-preview" id="subPreview" style="display:none">
      <img id="subPreviewImg" src="" alt="Preview">
      <span class="muted">Image preview</span>
    </div>

    <div class="switch-field">
      <div class="switch-text">
        <b>Active</b>
        <small class="muted">When on, this sub-category is available in the API</small>
      </div>
      <label class="switch">
        <input type="checkbox" name="is_active" id="subActive" value="1" checked>
        <span class="slider"></span>
      </label>
    </div>

    <div class="modal-actions">
      <button type="button" class="btn" onclick="closeModal('subModal')">Cancel</button>
      <button type="submit" class="btn btn-primary">Save Sub Category</button>
    </div>
  </form>
</div>

@push('scripts')
<script>
  const SUB_STORE  = "{{ route('sub-categories.store') }}";
  const SUB_UPDATE = "{{ url('sub-categories') }}";

  document.getElementById('subForm').addEventListener('submit', function (e) {
    e.preventDefault();
    ajaxSubmit(this).then(handleAjaxResult);
  });

  function openSubModal(mode, data) {
    const form = document.getElementById('subForm');
    document.getElementById('subModalTitle').textContent = mode === 'update' ? 'Edit Sub Category' : 'Add Sub Category';
    document.getElementById('subCategory').value = data?.category ?? '';
    document.getElementById('subName').value = data?.name ?? '';
    document.getElementById('subActive').checked = mode === 'update' ? !!data.active : true;
    document.getElementById('subImage').value = '';
    const prev = document.getElementById('subPreview'), prevImg = document.getElementById('subPreviewImg');
    if (mode === 'update' && data.image) { prevImg.src = data.image; prev.style.display = 'flex'; }
    else { prevImg.src = ''; prev.style.display = 'none'; }

    if (mode === 'update') { form.action = SUB_UPDATE + '/' + data.id; document.getElementById('subMethod').value = 'PUT'; }
    else { form.action = SUB_STORE; document.getElementById('subMethod').value = 'POST'; }
    openModal('subModal');
  }

  function editSub(btn) {
    openSubModal('update', {
      id: btn.dataset.id, category: btn.dataset.category, name: btn.dataset.name,
      active: btn.dataset.active === '1', image: btn.dataset.image || ''
    });
  }

  document.getElementById('subImage').addEventListener('change', function () {
    const f = this.files && this.files[0], prev = document.getElementById('subPreview'), img = document.getElementById('subPreviewImg');
    if (f) { img.src = URL.createObjectURL(f); prev.style.display = 'flex'; }
    else { img.src = ''; prev.style.display = 'none'; }
  });
</script>
@endpush
@endsection
