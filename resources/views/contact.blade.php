<x-layouts.app title="Contact — Kuartal Impact">
    <x-page-hero eyebrow="Contact" title="Get in touch">
        Founders, partners, communities, investors and press: write to the right inbox and we'll route it.
    </x-page-hero>
    <section class="k-section">
        <div class="k-container grid gap-6 md:grid-cols-3">
            @foreach ($contacts as $c)
                <div class="k-card flex flex-col gap-3">
                    <h2 class="text-2xl">{{ $c['label'] }}</h2>
                    <p class="text-sm text-gray-muted dark:text-white/65">{{ $c['body'] }}</p>
                    <a href="mailto:{{ $c['email'] }}" class="mt-auto break-all font-subhead text-sm font-medium text-navy hover:underline dark:text-green-400">{{ $c['email'] }}</a>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.app>
