@extends('layouts.app')
@section('title', 'API List')

@section('content')
<div class="api-doc">
  <h1 class="api-doc-title">API Documentation</h1>

  <div class="api-auth-note">
    <b>Authorization:</b> every request must send a bearer token header —
    <code class="code-inline">Authorization: Bearer &lt;token&gt;</code>. Without a valid token the API returns <b>401</b>.
  </div>

  <div class="api-module"><span class="api-module-pill pill-green">Drama Module</span></div>

  <div class="api-grid">
    {{-- 1 --}}
    <div class="api-doc-card">
      <h3>1. Get Category</h3>

      <p class="api-row"><span class="lbl">Method:</span> <span class="m-val">GET</span></p>

      <p class="api-row"><span class="lbl">URL:</span></p>
      <div class="api-url">{{ $apiBase }}/get-category</div>

      <p class="api-row api-descr"><b>Description:</b><br>
        Lists active categories that have videos, each with its last 5 videos.</p>
    </div>

    {{-- 2 --}}
    <div class="api-doc-card">
      <h3>2. Get Videos By Category ID</h3>

      <p class="api-row"><span class="lbl">Method:</span> <span class="m-val">POST</span></p>

      <p class="api-row"><span class="lbl">URL:</span></p>
      <div class="api-url">{{ $apiBase }}/getVideoByCategoryID</div>

      <p class="api-row"><span class="lbl">Parameters:</span><br>
        <code class="param-name">category_id</code> <span class="param-req">(required)</span> e.g. <span class="param-ex">1</span></p>

      <p class="api-row api-descr"><b>Description:</b><br>
        Returns all videos for the specified category ID.</p>
    </div>
  </div>
</div>
@endsection
