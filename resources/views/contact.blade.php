<x-layouts.app title="Contact — Kuartal Impact">
    <x-page-hero eyebrow="Contact" code="C" title="Open a channel">
        Founders, partners, communities, investors and press: write to the right inbox and we'll route it.
    </x-page-hero>
    <section class="k-section">
        <div class="k-container-wide grid gap-5 md:grid-cols-3">
            @foreach ($contacts as $c)
                <div class="k-panel k-ticks flex flex-col gap-4">
                    <span class="k-index">CH-{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h2 class="text-2xl">{{ $c['label'] }}</h2>
                    <p class="text-sm k-muted">{{ $c['body'] }}</p>
                    <a href="mailto:{{ $c['email'] }}" class="k-mono mt-auto break-all text-sm hover:underline">{{ $c['email'] }}</a>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.app>
