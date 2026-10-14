<x-layouts.app title="Focus areas — Kuartal Impact">
    <x-page-hero eyebrow="Focus areas" code="FA" title="Five fronts">
        Climate, environment, social, AI &amp; technology, and grassroots solutions. Every fund we run sits on at least one of them.
    </x-page-hero>
    <section class="k-section">
        <div class="k-container-wide space-y-px" style="background: var(--k-line)">
            @foreach (config('kuartalimpact.focus_areas') as $key => $fa)
                <article id="{{ $key }}" class="grid scroll-mt-20 gap-8 p-6 md:grid-cols-[10rem_1fr_1fr] md:p-10" style="background: var(--k-bg)">
                    <span class="k-index text-sm">{{ $fa['code'] }}</span>
                    <div><h2 class="text-3xl">{{ $fa['label'] }}</h2><p class="mt-4 k-muted">{{ $fa['body'] }}</p></div>
                    <div>
                        <p class="k-mono-muted mb-3">Funds on this front</p>
                        @forelse ($fundsByArea->get($key, collect()) as $f)
                            <a href="{{ route('programmes.show', $f->slug) }}" class="flex items-center justify-between border-b py-3 hover:text-[color:var(--k-accent)]" style="border-color: var(--k-line)"><span class="font-display">{{ $f->title }}</span><span aria-hidden="true">→</span></a>
                        @empty
                            <p class="font-mono text-xs k-muted">&gt; none published yet</p>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.app>
