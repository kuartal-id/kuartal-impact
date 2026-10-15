<x-layouts.app title="Focus areas — Kuartal Impact">
    <x-page-hero eyebrow="Focus areas" title="Five fronts">
        Climate, environment, social, AI &amp; technology, and grassroots solutions. Every fund we run sits on at least one of them.
    </x-page-hero>
    @foreach (config('kuartalimpact.focus_areas') as $key => $fa)
        <section id="{{ $key }}" class="k-section scroll-mt-20 {{ $loop->even ? 'k-band' : '' }}">
            <div class="k-container grid gap-10 md:[grid-template-columns:5fr_7fr] md:items-start">
                <div>
                    <p class="k-eyebrow mb-4">Focus area</p>
                    <h2 class="text-3xl md:text-4xl">{{ $fa['label'] }}</h2>
                    <p class="mt-4 max-w-md text-gray-muted dark:text-white/65">{{ $fa['body'] }}</p>
                </div>
                <div>
                    <p class="mb-3 font-subhead text-sm font-semibold">Funds on this front</p>
                    @forelse ($fundsByArea->get($key, collect()) as $f)
                        <a href="{{ route('programmes.show', $f->slug) }}" class="k-list-card"><span class="font-display font-semibold">{{ $f->title }}</span><span aria-hidden="true">→</span></a>
                    @empty
                        <x-empty-state title="None published yet" />
                    @endforelse
                </div>
            </div>
        </section>
    @endforeach
</x-layouts.app>
