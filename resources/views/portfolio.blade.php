<x-layouts.app title="Portfolio — Kuartal Impact">
    <x-page-hero eyebrow="Portfolio" title="Projects and companies we back">
        {{ $intro ?? 'Projects backed through our funds. Listed here as each one is confirmed.' }}
    </x-page-hero>
    <section class="k-section">
        <div class="k-container">
            @if ($projects->isNotEmpty())
                <div class="grid gap-6 md:grid-cols-3">
                    @foreach ($projects as $p)<x-project-card :project="$p" :fund="$funds->get($p->group)" />@endforeach
                </div>
            @else
                <x-empty-state title="Portfolio listings to be published">Projects will be listed here as they are confirmed. Meanwhile, see the <a class="underline" href="{{ route('programmes.index') }}">running funds</a>.</x-empty-state>
            @endif
        </div>
    </section>
</x-layouts.app>
