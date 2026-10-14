<!DOCTYPE html>
<html lang="en" class="dark" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Kuartal Impact' }}</title>
    <meta name="description" content="{{ $description ?? 'Kuartal Impact is the Kuartal arm for social, environmental and climate causes: capital, AI, technology and grassroots solutions for humanity\'s biggest threats.' }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=ki2">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=ki2" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('favicon-180.png') }}?v=ki2">
    <meta name="theme-color" content="#050505">
    {{-- Before first paint: pick neon / dark / neutral. Shares 'kuartal-theme' (light/dark) with every
         Kuartal property; with no stored choice Kuartal Impact opens dark. Logic: resources/js/theme-boot.js --}}
    <script>{!! file_get_contents(resource_path('js/theme-boot.js')) !!}
        (function (T) {
            try {
                T.apply(document, T.resolveValues(localStorage.getItem('kuartal-impact-theme'), localStorage.getItem('kuartal-theme'), localStorage.getItem('kuartal-impact-light')));
            } catch (e) {}
        })(window.kuartalImpactThemes);
    </script>
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
