<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Kuartal Impact' }}</title>
    <meta name="description" content="{{ $description ?? 'Kuartal Impact is the Kuartal arm for social, environmental and climate causes: capital, AI, technology and grassroots solutions for humanity\'s biggest threats.' }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=ki1">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=ki1" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('favicon-180.png') }}?v=ki1">
    {{-- Before first paint: same 'kuartal-theme' key as every Kuartal property (sync).
         With no stored choice Kuartal Impact opens dark, its native canvas. --}}
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('kuartal-theme') || 'dark';
                document.documentElement.classList.toggle('dark', theme === 'dark');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {}
        })();
    </script>
    <meta name="theme-color" content="#050605">
    <meta property="og:site_name" content="Kuartal Impact">
    <meta property="og:title" content="{{ $title ?? 'Kuartal Impact' }}">
    <meta property="og:description" content="{{ $description ?? 'Capital, AI, technology and grassroots solutions for climate, environment and society.' }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('images/og-kuartal-impact.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="alternate" type="application/rss+xml" title="Kuartal Impact Newsroom" href="{{ route('newsroom.feed') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body class="min-h-screen flex flex-col">
    <a href="#main" class="k-skip">Skip to content</a>
    @include('partials.nav')
    <main id="main" class="flex-1" tabindex="-1">
        {{ $slot }}
    </main>
    @include('partials.footer')
</body>
</html>
