<x-layouts.app title="Kuartal Impact — capital and technology for humanity's biggest threats">
    {{-- Hero: static grid always; the drifting point field is progressive and motion-safe. --}}
    <section class="k-grid-bg k-scan relative overflow-hidden border-b" style="border-color: var(--k-line)">
        <canvas class="k-field" data-field aria-hidden="true"></canvas>
        <div class="k-container-wide relative grid min-h-[78vh] items-end gap-12 py-20 md:py-28 lg:grid-cols-[1.6fr_1fr]">
            <div>
                <p class="k-eyebrow mb-6">// Kuartal Impact · social · environment · climate</p>
                <h1 class="text-[2.6rem] leading-[1.02] sm:text-6xl lg:text-7xl xl:text-[5.5rem]">
                    Capital for the<br>problems that<br><span class="k-accent k-cursor">can't wait</span>
                </h1>
                <p class="k-lead mt-8 max-w-xl">Kuartal Impact is the Kuartal arm for social, environmental and climate causes. We invest in AI, technology and grassroots solutions to humanity's biggest existential threats.</p>
                <div class="mt-10 flex flex-wrap gap-3">
                    <a href="{{ route('programmes.index') }}" class="k-btn-primary">Explore the funds</a>
                    <a href="{{ route('about') }}" class="k-btn-outline">Our thesis</a>
                </div>
            </div>
            <aside class="k-panel k-ticks hidden lg:block" aria-label="Kuartal Impact at a glance">
                <p class="k-mono mb-4">sys.status</p>
                <dl class="k-readout">
                    <div><dt>Entity</dt><dd>Kuartal Impact</dd></div>
                    <div><dt>Parent</dt><dd>Kuartal Group</dd></div>
                    <div><dt>Focus areas</dt><dd>{{ count(config('kuartalimpact.focus_areas')) }}</dd></div>
                    <div><dt>Running funds</dt><dd>{{ $funds->count() ?: '—' }}</dd></div>
                    <div><dt>Base</dt><dd>Indonesia</dd></div>
                </dl>
            </aside>
        </div>
    </section>

    {{-- Funds --}}
    <section class="k-section">
        <div class="k-container-wide">
            <x-section-header eyebrow="Running programmes" index="01" title="Funds working on the ground now">
                Each fund backs one problem, on the ground. Open a fund for its brief, figures and projects.
            </x-section-header>
            @if ($funds->isNotEmpty())
                <div class="grid gap-5 md:grid-cols-2">
                    @foreach ($funds as $fund)<x-fund-card :fund="$fund" :index="$loop->iteration" />@endforeach
                </div>
            @else
                <x-empty-state title="Funds coming online">Fund pages will appear here once they are published.</x-empty-state>
            @endif
        </div>
    </section>

    {{-- Thesis / focus areas --}}
    <section class="k-section k-grid-bg border-y" style="border-color: var(--k-line)">
        <div class="k-container-wide">
            <x-section-header eyebrow="Thesis" index="02" title="Where we point capital" />
            <ol class="grid gap-px sm:grid-cols-2 lg:grid-cols-5" style="background: var(--k-line)">
                @foreach (config('kuartalimpact.focus_areas') as $key => $fa)
                    <li class="flex flex-col gap-4 p-6" style="background: var(--k-bg)">
                        <span class="k-index">{{ $fa['code'] }}</span>
                        <h3 class="text-xl">{{ $fa['label'] }}</h3>
                        <p class="text-sm k-muted">{{ $fa['body'] }}</p>
                    </li>
                @endforeach
            </ol>
            <a href="{{ route('focus-areas') }}" class="k-mono mt-8 inline-block hover:underline">All focus areas →</a>
        </div>
    </section>

    @if ($quote)
        <section class="k-section">
            <figure class="k-container max-w-4xl">
                <p class="k-eyebrow mb-6">03 / Why we exist</p>
                <blockquote class="font-display text-2xl leading-snug md:text-4xl">“{{ $quote }}”</blockquote>
                @if ($quoteBy)<figcaption class="k-mono-muted mt-8">— {{ $quoteBy }}</figcaption>@endif
            </figure>
        </section>
    @endif

    @if ($partners->isNotEmpty())
        <section class="k-section !pt-0">
            <div class="k-container-wide">
                <p class="k-eyebrow mb-6">Partners</p>
                <ul class="flex flex-wrap items-center gap-x-10 gap-y-6">
                    @foreach ($partners as $p)
                        <li>@if ($p->media)<img src="{{ $p->media->url() }}" alt="{{ $p->title }}" class="h-10 w-auto opacity-80">@else<span class="font-display text-lg">{{ $p->title }}</span>@endif</li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- Newsroom --}}
    <section class="k-section border-t" style="border-color: var(--k-line)">
        <div class="k-container-wide">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <x-section-header eyebrow="Newsroom" index="04" title="Signals from the field" class="!mb-0" />
                <a href="{{ route('newsroom.index') }}" class="k-btn-outline">All news</a>
            </div>
            <div class="mt-10">
                @if ($posts->isNotEmpty())
                    <div class="grid gap-5 md:grid-cols-3">@foreach ($posts as $post)<x-post-card :post="$post" />@endforeach</div>
                @else
                    <x-empty-state title="No posts yet">News, field notes and announcements will be published here.</x-empty-state>
                @endif
            </div>
        </div>
    </section>

    <section class="k-section k-grid-bg border-t" style="border-color: var(--k-line)">
        <div class="k-container-wide flex flex-col items-start justify-between gap-8 md:flex-row md:items-end">
            <h2 class="max-w-2xl text-3xl md:text-5xl">For founders and investors who want to do more.</h2>
            <a href="{{ route('contact') }}" class="k-btn-primary">Get in touch</a>
        </div>
    </section>
</x-layouts.app>
