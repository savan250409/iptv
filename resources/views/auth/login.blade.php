<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>Login · Drama Admin</title>
<link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v={{ filemtime(public_path('images/favicon.png')) }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
</head>
<body class="auth-body">
  <form class="auth-card" method="post" action="{{ route('login') }}" autocomplete="on">
    @csrf
    <img class="auth-logo-img" src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="NGD Technolab">
    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-sub">Sign in to the Drama control panel</p>

    @if ($errors->any())
      <div class="alert alert-error">@include('partials.icon', ['name' => 'alert']) {{ $errors->first() }}</div>
    @endif

    <label class="field">
      <span>Email address</span>
      <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" required autofocus>
    </label>
    <label class="field">
      <span>Password</span>
      <input type="password" name="password" placeholder="••••••••" required>
    </label>

    <button type="submit" class="btn btn-primary btn-block">
      @include('partials.icon', ['name' => 'logout']) Sign in
    </button>
    <p class="muted auth-hint">🔒 Stays signed in on this device (remember me)</p>
  </form>
</body>
</html>
