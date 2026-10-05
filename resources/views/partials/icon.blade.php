@php
    /** Inline SVG icon set (stroke = currentColor). Usage: @include('partials.icon', ['name' => 'grid']) */
    $name = $name ?? 'dot';
    $cls  = $cls ?? 'icon';
    $icons = [
        'grid'    => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'film'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 4v16M17 4v16M3 9h4M17 9h4M3 15h4M17 15h4"/>',
        'plug'    => '<path d="M9 2v6M15 2v6M7 8h10v3a5 5 0 0 1-10 0V8ZM12 16v6"/>',
        'logout'  => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'plus'    => '<path d="M12 5v14M5 12h14"/>',
        'edit'    => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>',
        'trash'   => '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/>',
        'open'    => '<path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'image'   => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
        'play'    => '<circle cx="12" cy="12" r="10"/><path d="m10 8 6 4-6 4V8Z" fill="currentColor" stroke="none"/>',
        'folder'  => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/>',
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'bolt'    => '<path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z"/>',
        'check'   => '<path d="M20 6 9 17l-5-5"/>',
        'alert'   => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
        'menu'    => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'home'    => '<path d="M3 10.5 12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5"/>',
        'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ];
    $path = $icons[$name] ?? '<circle cx="12" cy="12" r="3"/>';
@endphp
<svg class="{{ $cls }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $path !!}</svg>
