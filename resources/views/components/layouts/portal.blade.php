@props(['title' => 'Kuartal Impact', 'area' => 'admin'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=4" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=4">
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="k-admin min-h-screen flex flex-col bg-gray-bg text-navy-900">
    <a href="#main" class="k-skip">Skip to content</a>
    <header class="sticky top-0 z-50 border-b border-gray-border bg-white/90 backdrop-blur">
        <div class="k-container-wide flex flex-wrap items-center gap-x-6 gap-y-2 min-h-16 py-3">
            <a href="{{ $area === 'admin' ? route('admin.dashboard') : url('/') }}" class="flex items-center gap-3">
                <x-wordmark tone="light" size="h-8" />
                <span class="font-display font-semibold text-sm tracking-wide text-green-500">Impact Admin</span>
            </a>
            @if ($area === 'admin')
                <nav class="flex flex-wrap gap-4 text-sm font-subhead">
                    @foreach (['admin.dashboard' => 'Dashboard', 'admin.posts.index' => 'Newsroom', 'admin.media.index' => 'Media', 'admin.entries.index' => 'Funds & site content'] as $r => $label)
                        <a href="{{ route($r) }}" @if(request()->routeIs(str_replace('.index', '.*', $r))) aria-current="page" @endif class="{{ request()->routeIs(str_replace('.index', '.*', $r)) ? 'text-green-500 font-medium' : 'text-navy/80 hover:text-green-400' }}">{{ $label }}</a>
                    @endforeach
                </nav>
            @endif
            <div class="ml-auto flex items-center gap-3 text-sm">
                @auth
                    <span class="text-gray-muted">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="k-btn-outline !px-4 !py-1.5">Sign out</button></form>
                @endauth
            </div>
        </div>
    </header>
    <main id="main" class="flex-1 k-container-wide py-8 w-full">
        @if (session('status'))
            <div class="mb-6 rounded-card border border-green bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-card border border-red-400 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        {{ $slot }}
    </main>
    <footer class="py-6 text-center text-xs text-gray-muted">Kuartal Impact · Admin</footer>
</body>
</html>
