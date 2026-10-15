@props(['icon' => 'holding'])
@php
$paths = [
    'capital' => '<path d="M3 21h18M5 21V10m14 11V10M9 21v-6h6v6M3 10l9-6 9 6"/>',
    'ventures' => '<path d="M5 15c-1.5 1.5-2 5-2 5s3.5-.5 5-2m1.5-6.5L15 6a8 8 0 0 1 5-2 8 8 0 0 1-2 5l-5.5 5.5M9 12l3 3m-5-1 3 3m2-9-3 0-2 2 3 1m5 2 0 3-2 2-1-3"/>',
    'holding' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'systems' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M9 6h6M9 10h1m2 0h1m2 0h0M9 14h1m2 0h1m2 0h0M9 18h6"/>',
    'cloud' => '<path d="M17.5 19a4.5 4.5 0 1 0-1.4-8.8A6 6 0 0 0 4.5 12 3.5 3.5 0 0 0 6 19z"/><path d="M12 13v4m-2-2h4"/>',
    'platforms' => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4M7 9l2 2-2 2m4 0h4"/>',
    'media' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="m10 9 5 3-5 3z"/>',
    'payments' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
    'group' => '<circle cx="12" cy="5" r="2.5"/><circle cx="5" cy="18" r="2.5"/><circle cx="19" cy="18" r="2.5"/><path d="M12 7.5v4M12 11.5 6.5 16M12 11.5l5.5 4.5"/>',
    'news' => '<path d="M4 4h13v16H6a2 2 0 0 1-2-2zM17 8h3v10a2 2 0 0 1-2 2"/><path d="M8 8h5M8 12h5M8 16h3"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4m8-4v4"/>',
    'chart' => '<path d="M3 3v18h18"/><path d="M7 15v2m4-6v6m4-9v9m4-12v12"/>',
    'document' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
    'people' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7m2.5 8.5a6.5 6.5 0 0 0-3-5.5"/>',
    'leaf' => '<path d="M11 20A7 7 0 0 1 4 13c0-6 5-9 16-9 0 11-3 16-9 16z"/><path d="M4 21c3-5 6-8 11-11"/>',
    'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/>',
];
@endphp
<svg {{ $attributes->merge(['class' => 'h-6 w-6']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $paths[$icon] ?? $paths['holding'] !!}</svg>
