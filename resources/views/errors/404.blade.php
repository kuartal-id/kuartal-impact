<x-layouts.app title="Page not found — Kuartal Impact">
    <x-slot:head><meta name="robots" content="noindex"></x-slot:head>
    <x-page-hero eyebrow="404" title="We couldn't find that page">
        The link may be old or mistyped. Try one of these instead.
    </x-page-hero>
    <section class="k-section">
        <div class="k-container flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('home') }}" class="k-btn-primary">Back to home</a>
            <a href="{{ route('programmes.index') }}" class="k-btn-outline">Programmes</a>
            <a href="{{ route('newsroom.index') }}" class="k-btn-outline">Newsroom</a>
            <a href="{{ route('contact') }}" class="k-btn-outline">Contact</a>
        </div>
    </section>
</x-layouts.app>
