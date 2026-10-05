<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Admin') · Drama Admin</title>
<link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v={{ filemtime(public_path('images/favicon.png')) }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
</head>
<body>
<div class="app" id="app">
  <aside class="sidebar">
    <div class="brand">
      <img class="brand-logo-img" src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="NGD Technolab">
    </div>

    <div class="nav-label">Menu</div>
    <nav class="nav">
      <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" aria-label="Dashboard" data-tip="Dashboard">
        @include('partials.icon', ['name' => 'home']) <span class="label">Dashboard</span>
      </a>
      <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}" aria-label="Categories" data-tip="Categories">
        @include('partials.icon', ['name' => 'grid']) <span class="label">Categories</span>
      </a>
      <a class="nav-link {{ request()->routeIs('videos.*') ? 'active' : '' }}" href="{{ route('videos.index') }}" aria-label="Videos" data-tip="Videos">
        @include('partials.icon', ['name' => 'film']) <span class="label">Videos</span>
      </a>
      <a class="nav-link {{ request()->routeIs('api-list') ? 'active' : '' }}" href="{{ route('api-list') }}" aria-label="API List" data-tip="API List">
        @include('partials.icon', ['name' => 'plug']) <span class="label">API List</span>
      </a>
    </nav>

    <div class="sidebar-foot">
      <div class="user-card">
        <div class="avatar">{{ strtoupper(substr(auth()->user()->email ?? 'A', 0, 1)) }}</div>
        <div class="user-meta">
          <b>Administrator</b>
          <span>{{ auth()->user()->email ?? '' }}</span>
        </div>
      </div>
      <form method="post" action="{{ route('logout') }}">
        @csrf
        <button class="logout-btn" type="submit" aria-label="Logout" data-tip="Logout">
          @include('partials.icon', ['name' => 'logout']) <span class="label">Logout</span>
        </button>
      </form>
    </div>
  </aside>

  <div class="backdrop" onclick="closeSidebar()"></div>

  <div class="main">
    <header class="header">
      <button class="hamburger" type="button" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        @include('partials.icon', ['name' => 'menu'])
      </button>
      <div class="header-title">@yield('title', 'Admin')</div>
      <div class="header-spacer"></div>
      <div class="user-chip">
        <div class="avatar">{{ strtoupper(substr(auth()->user()->email ?? 'A', 0, 1)) }}</div>
        <span>Admin</span>
      </div>
    </header>

    <main class="content">
      @if ($errors->any())
        <div class="alert alert-error">@include('partials.icon', ['name' => 'alert']) {{ $errors->first() }}</div>
      @endif

      @yield('content')
    </main>
  </div>
</div>

<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@if (session('status'))
<script>
  document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
      toast: true, position: 'top-end', icon: 'success',
      title: @json(session('status')),
      showConfirmButton: false, timer: 2600, timerProgressBar: true
    });
  });
</script>
@endif
@stack('scripts')
</body>
</html>
