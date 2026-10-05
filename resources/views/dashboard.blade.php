@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
{{-- Stat tiles --}}
<div class="stats">
  <div class="stat">
    <div class="stat-ic i-indigo">@include('partials.icon', ['name' => 'grid'])</div>
    <div class="stat-meta"><b>{{ $categoryCount }}</b><span>Total categories</span></div>
  </div>
  <div class="stat">
    <div class="stat-ic i-green">@include('partials.icon', ['name' => 'film'])</div>
    <div class="stat-meta"><b>{{ $videoCount }}</b><span>Total videos</span></div>
  </div>
  <div class="stat">
    <div class="stat-ic i-amber">@include('partials.icon', ['name' => 'bolt'])</div>
    <div class="stat-meta">
      <b>{{ $categoryCount ? number_format($videoCount / $categoryCount, 1) : '0' }}</b>
      <span>Avg videos / category</span>
    </div>
  </div>
  <div class="stat">
    <div class="stat-ic i-rose">@include('partials.icon', ['name' => 'play'])</div>
    <div class="stat-meta">
      <b>{{ $topCategory && $topCategory->videos_count ? $topCategory->name : '—' }}</b>
      <span>Top category{{ $topCategory && $topCategory->videos_count ? ' ('.$topCategory->videos_count.')' : '' }}</span>
    </div>
  </div>
</div>

{{-- Quick actions --}}
<div class="panel">
  <div class="panel-head"><h2>@include('partials.icon', ['name' => 'bolt', 'cls' => 'icon-sm']) Quick actions</h2></div>
  <div class="panel-body quick-actions">
    <a class="qa" href="{{ route('categories.index') }}">
      <div class="qa-ic i-indigo">@include('partials.icon', ['name' => 'grid'])</div>
      <div><b>Manage Categories</b><span>Add, edit or remove categories</span></div>
      @include('partials.icon', ['name' => 'arrow', 'cls' => 'qa-go'])
    </a>
    <a class="qa" href="{{ route('videos.index') }}">
      <div class="qa-ic i-green">@include('partials.icon', ['name' => 'film'])</div>
      <div><b>Upload Videos</b><span>Add videos to a category</span></div>
      @include('partials.icon', ['name' => 'arrow', 'cls' => 'qa-go'])
    </a>
    <a class="qa" href="{{ route('api-list') }}">
      <div class="qa-ic i-amber">@include('partials.icon', ['name' => 'plug'])</div>
      <div><b>View APIs</b><span>Public endpoints & docs</span></div>
      @include('partials.icon', ['name' => 'arrow', 'cls' => 'qa-go'])
    </a>
  </div>
</div>

{{-- Recent data --}}
<div class="dash-grid">
  <div class="panel">
    <div class="panel-head">
      <h2>@include('partials.icon', ['name' => 'grid', 'cls' => 'icon-sm']) Recent categories</h2>
      <a class="btn btn-sm" href="{{ route('categories.index') }}">View all</a>
    </div>
    @if ($recentCategories->isEmpty())
      <div class="empty"><div class="empty-ic">@include('partials.icon', ['name' => 'folder'])</div><b>No categories yet</b></div>
    @else
      <div class="mini-list">
        @foreach ($recentCategories as $c)
          <div class="mini-item">
            @if ($c->image_url)
              <img class="thumb" src="{{ $c->image_url }}" alt="">
            @else
              <div class="thumb thumb-empty">@include('partials.icon', ['name' => 'image', 'cls' => 'icon-sm'])</div>
            @endif
            <div class="mini-meta"><b>{{ $c->name }}</b><span>{{ optional($c->created_at)->format('d M Y') }}</span></div>
            <span class="count-badge">{{ $c->videos_count }} videos</span>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2>@include('partials.icon', ['name' => 'film', 'cls' => 'icon-sm']) Recent videos</h2>
      <a class="btn btn-sm" href="{{ route('videos.index') }}">View all</a>
    </div>
    @if ($recentVideos->isEmpty())
      <div class="empty"><div class="empty-ic">@include('partials.icon', ['name' => 'film'])</div><b>No videos yet</b></div>
    @else
      <div class="mini-list">
        @foreach ($recentVideos as $v)
          <div class="mini-item">
            <video class="video-thumb" src="{{ $v->video_url }}#t=0.1" muted preload="metadata"></video>
            <div class="mini-meta"><b>{{ $v->title }}</b><span>{{ optional($v->created_at)->format('d M Y') }}</span></div>
            <span class="pill"><span class="dot"></span>{{ optional($v->category)->name }}</span>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>
@endsection
