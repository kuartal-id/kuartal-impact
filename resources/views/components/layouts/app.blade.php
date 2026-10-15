<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Kuartal Impact' }}</title>
    <meta name="description" content="{{ $description ?? 'Kuartal Impact is the Kuartal arm for social, environmental and climate causes: capital, AI, technology and grassroots solutions for humanity\'s biggest threats.' }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=ki2">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=ki2" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('favicon-180.png') }}?v=ki2">
    {{-- Before first paint: light/dark, exactly as on kuartal.id. Same localStorage key ('kuartal-theme')
         as every Kuartal site, so a visitor's choice follows them across properties; first visit follows the OS. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('kuartal-theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var theme = stored === 'dark' || stored === 'light' ? stored : (prefersDark ? 'dark' : 'light');
                document.documentElement.classList.toggle('dark', theme === 'dark');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {}
        })();
    </script>
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">
    <meta property="og:site_name" content="Kuartal Impact">
    <meta property="og:title" content="{{ $title ?? 'Kuartal Impact' }}">
    <meta property="og:description" content="{{ $description ?? 'Capital, AI, technology and grassroots solutions for climate, environment and society.' }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('images/og-kuartal-impact.png') }}">
    @unless (isset($ogImage))<meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">@endunless
    <meta property="og:image:alt" content="Kuartal Impact">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'Kuartal Impact' }}">
    <meta name="twitter:image" content="{{ $ogImage ?? asset('images/og-kuartal-impact.png') }}">
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
