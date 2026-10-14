@php $fa = $fund->focusArea(); $code = 'F-'.str_pad($index, 2, '0', STR_PAD_LEFT); @endphp
<x-layouts.app :title="$fund->title.' — Kuartal Impact'" :description="$fund->subtitle" :og-image="$fund->media?->url()">
    <section class="k-page-hero k-grid-bg k-scan">
        <div class="k-container-wide relative grid gap-10 py-16 md:py-24 lg:grid-cols-[1.6fr_1fr] lg:items-end">
            <div>
                <nav aria-label="Breadcrumb" class="k-mono-muted mb-6"><a href="{{ route('programmes.index') }}" class="hover:underline">Programmes</a> / <span aria-current="page">{{ $fund->title }}</span></nav>
                <p class="k-eyebrow mb-4">// {{ $code }}{{ $fa ? ' · '.$fa['label'] : '' }}</p>
                <h1 class="text-5xl leading-[1.02] md:text-7xl">{{ $fund->title }}</h1>
                @if ($fund->subtitle)<p class="k-lead mt-6 max-w-2xl">{{ $fund->subtitle }}</p>@endif
            </div>
            <dl class="k-panel k-ticks k-readout">
                <div><dt>Fund</dt><dd>{{ $code }}</dd></div>
                <div><dt>Focus</dt><dd>{{ $fa['label'] ?? '—' }}</dd></div>
                <div><dt>Location</dt><dd>{{ $fund->location ?: '—' }}</dd></div>
                <div><dt>Status</dt><dd class="k-accent">Running</dd></div>
            </dl>
        </div>
    </section>

    @if ($fund->media)
        <div class="k-container-wide pt-12"><img src="{{ $fund->media->url() }}" alt="{{ $fund->media->alt_text ?? '' }}" class="aspect-[21/9] w-full object-cover"></div>
    @endif

    <section class="k-section" aria-labelledby="figures">
        <div class="k-container-wide">
            <p class="k-eyebrow mb-3">01 / Figures</p>
            <h2 id="figures" class="mb-10 text-3xl md:text-4xl">By the numbers</h2>
            @if ($metrics->isNotEmpty())
                <dl class="grid gap-px sm:grid-cols-2 lg:grid-cols-4" style="background: var(--k-line)">
                    @foreach ($metrics as $m)
                        <div class="flex flex-col-reverse gap-3 p-6" style="background: var(--k-bg)">
                            <dt class="k-mono-muted">{{ $m->title }}@if ($m->body)<span class="mt-2 block normal-case tracking-normal font-sans text-xs">{{ $m->body }}</span>@endif</dt>
                            <dd class="font-display text-4xl md:text-5xl k-accent">{{ $m->subtitle ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <x-empty-state title="Figures pending verification">Impact figures for {{ $fund->title }} will be published here once they are confirmed.</x-empty-state>
            @endif
        </div>
    </section>

    <section class="k-section k-grid-bg border-y" style="border-color: var(--k-line)">
        <div class="k-container-wide grid gap-12 lg:grid-cols-[1fr_2fr]">
            <div><p class="k-eyebrow">02 / Brief</p><h2 class="mt-3 text-3xl md:text-4xl">What the fund does</h2></div>
            <div>
                @if ($fund->body)<div class="k-prose">{!! nl2br(e($fund->body)) !!}</div>
                @else<x-empty-state title="Full brief to be published">{{ $fund->subtitle }}</x-empty-state>@endif
                @if ($fund->url)<a href="{{ $fund->url }}" class="k-btn-outline mt-8" rel="noopener">More about {{ $fund->title }}</a>@endif
            </div>
        </div>
    </section>

    <section class="k-section">
        <div class="k-container-wide">
            <p class="k-eyebrow mb-3">03 / Projects</p>
            <h2 class="mb-10 text-3xl md:text-4xl">Backed by this fund</h2>
            @if ($projects->isNotEmpty())
                <div class="grid gap-5 md:grid-cols-3">
                    @foreach ($projects as $p)<x-project-card :project="$p" />@endforeach
                </div>
            @else
                <x-empty-state title="No projects listed yet" />
            @endif
        </div>
    </section>

    @if ($posts->isNotEmpty())
        <section class="k-section !pt-0"><div class="k-container-wide"><p class="k-eyebrow mb-8">04 / Updates</p><div class="grid gap-5 md:grid-cols-3">@foreach ($posts as $post)<x-post-card :post="$post" />@endforeach</div></div></section>
    @endif

    <section class="k-section border-t" style="border-color: var(--k-line)">
        <div class="k-container-wide">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div><p class="k-eyebrow mb-3">Support {{ $fund->title }}</p><h2 class="text-3xl md:text-4xl">Back this fund or partner with it.</h2></div>
                <a href="{{ route('contact') }}" class="k-btn-primary">Get in touch</a>
            </div>
            @if ($others->isNotEmpty())
                <div class="mt-14 grid gap-px sm:grid-cols-3" style="background: var(--k-line)">
                    @foreach ($others as $o)
                        <a href="{{ route('programmes.show', $o->slug) }}" class="flex items-center justify-between p-5 hover:text-[color:var(--k-accent)]" style="background: var(--k-bg)"><span class="font-display text-lg">{{ $o->title }}</span><span aria-hidden="true">→</span></a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.app>
