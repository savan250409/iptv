@extends('layouts.app')
@section('title', $mode === 'edit' ? 'Edit Video' : 'Add Video')
@section('subtitle', $mode === 'edit' ? 'Update this video' : 'Upload a new video')

@section('content')
<div class="form-page">
  <a href="{{ route('videos.index') }}" class="back-link">@include('partials.icon', ['name' => 'arrow', 'cls' => 'icon-sm back-ic']) Back to Videos</a>

  <div class="panel">
    <div class="panel-head">
      <h2>@include('partials.icon', ['name' => 'film', 'cls' => 'icon-sm']) {{ $mode === 'edit' ? 'Edit Video' : 'Add New Video' }}</h2>
    </div>

    <div class="panel-body">
      @if ($allCategories->isEmpty())
        <div class="empty">
          <div class="empty-ic">@include('partials.icon', ['name' => 'folder'])</div>
          <b>No categories yet</b>
          You need at least one category first. <a href="{{ route('categories.index') }}">Create a category →</a>
        </div>
      @else
      <form method="post" enctype="multipart/form-data"
            action="{{ $mode === 'edit' ? route('videos.update', $video) : route('videos.store') }}">
        @csrf
        @if ($mode === 'edit') @method('PUT') @endif

        <div class="form-grid">
          <label class="field">
            <span>Category</span>
            <select name="category_id" required>
              <option value="">— Select category —</option>
              @foreach ($allCategories as $c)
                <option value="{{ $c->id }}" {{ (int) old('category_id', $video->category_id) === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
              @endforeach
            </select>
          </label>

          <label class="field">
            <span>Video title</span>
            <input type="text" name="title" required maxlength="190"
                   value="{{ old('title', $video->title) }}" placeholder="e.g. Episode 1">
          </label>
        </div>

        <label class="field">
          <span>Video file
            <small class="muted">{{ $mode === 'edit' ? '(leave empty to keep current)' : '(mp4/webm/mov…, max 500 MB)' }}</small>
          </span>
          <input type="file" name="video" id="videoFile" accept="video/*" {{ $mode === 'create' ? 'required' : '' }}>
          <small class="thumb-status muted" id="thumbStatus"></small>
        </label>
        {{-- Canvas-generated thumbnail travels with the form (no server-side video processing). --}}
        <input type="hidden" name="thumbnail_data" id="thumbnailData">

        @php
          $hasCurrent   = $mode === 'edit' && $video->video_file;
          $hasThumbNail = $mode === 'edit' && $video->thumbnail;
        @endphp
        <div class="preview-row">
          <div class="field" id="videoPreviewWrap" style="{{ $hasCurrent ? '' : 'display:none' }}">
            <span id="videoPreviewLabel">{{ $hasCurrent ? 'Current video' : 'Video preview' }}</span>
            <video id="videoPreview" class="video-preview" src="{{ $hasCurrent ? $video->video_url . '#t=0.1' : '' }}" controls preload="metadata"></video>
          </div>
          <div class="field" id="thumbPreviewWrap" style="{{ $hasThumbNail ? '' : 'display:none' }}">
            <span>Generated thumbnail</span>
            <img id="thumbPreviewImg" class="thumb-preview" src="{{ $hasThumbNail ? $video->thumbnail_url : '' }}" alt="Thumbnail">
          </div>
        </div>

        <div class="form-actions">
          <a href="{{ route('videos.index') }}" class="btn">Cancel</a>
          <button type="submit" class="btn btn-primary">
            @include('partials.icon', ['name' => $mode === 'edit' ? 'check' : 'play', 'cls' => 'icon-sm'])
            {{ $mode === 'edit' ? 'Update Video' : 'Upload Video' }}
          </button>
        </div>
      </form>
      @endif
    </div>
  </div>
</div>

@push('scripts')
<script>
(function () {
  const input   = document.getElementById('videoFile');
  if (!input) return;
  const wrap    = document.getElementById('videoPreviewWrap');
  const preview = document.getElementById('videoPreview');
  const label   = document.getElementById('videoPreviewLabel');
  const status  = document.getElementById('thumbStatus');
  const hidden  = document.getElementById('thumbnailData');
  const form    = input.closest('form');

  // Capture a frame from the chosen video using a <canvas> → JPEG data URL.
  function generateThumbnail(file) {
    return new Promise((resolve) => {
      const url = URL.createObjectURL(file);
      const v = document.createElement('video');
      v.preload = 'metadata'; v.muted = true; v.playsInline = true; v.src = url;

      const fail = () => { URL.revokeObjectURL(url); resolve(''); };
      v.addEventListener('error', fail, { once: true });
      v.addEventListener('loadeddata', () => {
        // seek ~10% in (fallback to 0.1s) so we don't grab a black first frame
        const t = (isFinite(v.duration) && v.duration > 0) ? Math.min(1, v.duration * 0.1) : 0.1;
        try { v.currentTime = t; } catch (e) { fail(); }
      }, { once: true });
      v.addEventListener('seeked', () => {
        try {
          const maxW = 640;
          const vw = v.videoWidth || maxW, vh = v.videoHeight || 360;
          const scale = Math.min(1, maxW / vw);
          const canvas = document.createElement('canvas');
          canvas.width = Math.round(vw * scale);
          canvas.height = Math.round(vh * scale);
          canvas.getContext('2d').drawImage(v, 0, 0, canvas.width, canvas.height);
          const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
          URL.revokeObjectURL(url);
          resolve(dataUrl);
        } catch (e) { fail(); }
      }, { once: true });
    });
  }

  const thumbWrap = document.getElementById('thumbPreviewWrap');
  const thumbImg  = document.getElementById('thumbPreviewImg');

  let generating = false;
  async function buildThumb(file) {
    generating = true;
    status.textContent = 'Generating thumbnail…';
    const dataUrl = await generateThumbnail(file);
    hidden.value = dataUrl || '';
    if (dataUrl) {
      status.textContent = '✓ Thumbnail ready';
      thumbImg.src = dataUrl;
      thumbWrap.style.display = '';
    } else {
      status.textContent = 'Thumbnail could not be generated for this format (video still uploads).';
      thumbWrap.style.display = 'none';
    }
    generating = false;
    return dataUrl;
  }

  input.addEventListener('change', function () {
    const file = this.files && this.files[0];
    hidden.value = '';
    status.textContent = '';
    thumbWrap.style.display = 'none';
    if (!file) return;
    // live preview
    preview.src = URL.createObjectURL(file);
    label.textContent = 'Selected video preview';
    wrap.style.display = '';
    preview.load();
    // auto thumbnail
    buildThumb(file);
  });

  // Make sure the thumbnail is ready before submitting (if a file is chosen).
  form.addEventListener('submit', function (e) {
    const file = input.files && input.files[0];
    if (file && !hidden.value && !generating) {
      e.preventDefault();
      buildThumb(file).then(() => form.submit());
    } else if (generating) {
      e.preventDefault();
      const wait = setInterval(() => { if (!generating) { clearInterval(wait); form.submit(); } }, 120);
    }
  });
})();
</script>
@endpush
@endsection
