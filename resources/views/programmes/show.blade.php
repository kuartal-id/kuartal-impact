@php $fa = $fund->focusArea(); @endphp
<x-layouts.app :title="$fund->title.' — Kuartal Impact'" :description="$fund->subtitle" :og-image="$fund->media?->url()">
    <section class="k-page-hero">
        <div class="k-container py-16 text-center md:py-20">
            <nav aria-label="Breadcrumb" class="k-meta mb-5"><a href="{{ route('programmes.index') }}" class="hover:underline">Programmes</a> <span aria-hidden="true">/</span> <span aria-current="page">{{ $fund->title }}</span></nav>
            <p class="k-eyebrow mb-4">{{ $fa['label'] ?? 'Fund' }}</p>
            <h1 class="mx-auto max-w-4xl text-4xl md:text-5xl leading-[1.1]">{{ $fund->title }}</h1>
            @if ($fund->subtitle)<p class="mx-auto mt-5 max-w-2xl text-lg text-gray-muted dark:text-white/65">{{ $fund->subtitle }}</p>@endif
            <ul class="mt-8 flex flex-wrap justify-center gap-2" aria-label="Fund details">
                @if ($fa)<li class="k-chip">{{ $fa['label'] }}</li>@endif
                @if ($fund->location)<li class="k-chip">{{ $fund->location }}</li>@endif
                <li class="k-chip is-active">Running</li>
            </ul>
        </div>
    </section>

    @if ($fund->media)
        <div class="k-container pt-12"><img src="{{ $fund->media->url() }}" alt="{{ $fund->media->alt_text ?? '' }}" class="aspect-[21/9] w-full rounded-card object-cover"></div>
    @endif

    <section class="k-section" aria-labelledby="figures">
        <div class="k-container">
            <div class="mx-auto mb-10 max-w-2xl text-center"><p class="k-eyebrow mb-3">Figures</p><h2 id="figures" class="text-3xl md:text-4xl">By the numbers</h2></div>
            @if ($metrics->isNotEmpty())
                <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($metrics as $m)
                        <div class="k-card flex flex-col-reverse gap-2 text-center">
                            <dt class="font-subhead text-sm text-gray-muted dark:text-white/65">{{ $m->title }}@if ($m->body)<span class="mt-1 block text-xs">{{ $m->body }}</span>@endif</dt>
                            <dd class="font-display text-4xl font-semibold text-navy md:text-5xl dark:text-green-400">{{ $m->subtitle ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <x-empty-state title="Figures pending verification">Impact figures for {{ $fund->title }} will be published here once they are confirmed.</x-empty-state>
            @endif
        </div>
    </section>

    <section class="k-section k-band">
        <div class="k-container grid gap-10 md:[grid-template-columns:5fr_7fr]">
            <div><p class="k-eyebrow mb-4">Brief</p><h2 class="text-3xl md:text-4xl">What the fund does</h2></div>
            <div>
                @if ($fund->body)<div class="k-prose">{!! nl2br(e($fund->body)) !!}</div>
                @else<x-empty-state title="Full brief to be published">{{ $fund->subtitle }}</x-empty-state>@endif
                @if ($fund->url)<a href="{{ $fund->url }}" class="k-btn-outline mt-8" rel="noopener">More about {{ $fund->title }}</a>@endif
            </div>
        </div>
    </section>

    <section class="k-section">
        <div class="k-container">
            <div class="mx-auto mb-10 max-w-2xl text-center"><p class="k-eyebrow mb-3">Projects</p><h2 class="text-3xl md:text-4xl">Backed by this fund</h2></div>
            @if ($projects->isNotEmpty())
                <div class="grid gap-6 md:grid-cols-3">@foreach ($projects as $p)<x-project-card :project="$p" />@endforeach</div>
            @else
                <x-empty-state title="No projects listed yet" />
            @endif
        </div>
    </section>

    @if ($posts->isNotEmpty())
        <section class="k-section k-band"><div class="k-container"><div class="mx-auto mb-10 max-w-2xl text-center"><p class="k-eyebrow mb-3">Updates</p><h2 class="text-3xl md:text-4xl">From the newsroom</h2></div><div class="grid gap-6 md:grid-cols-3">@foreach ($posts as $post)<x-post-card :post="$post" />@endforeach</div></div></section>
    @endif

    <section class="k-section {{ $posts->isNotEmpty() ? '' : 'k-band' }}">
        <div class="k-container text-center">
            <p class="k-eyebrow mb-3">Support {{ $fund->title }}</p>
            <h2 class="mx-auto max-w-2xl text-3xl md:text-4xl">Back this fund or partner with it.</h2>
            <div class="mt-8"><a href="{{ route('contact') }}" class="k-btn-primary">Get in touch</a></div>
            @if ($others->isNotEmpty())
                <div class="mx-auto mt-14 grid max-w-4xl gap-3 text-left sm:grid-cols-3">
                    @foreach ($others as $o)
                        <a href="{{ route('programmes.show', $o->slug) }}" class="k-list-card !mt-0"><span class="font-display font-semibold">{{ $o->title }}</span><span aria-hidden="true">→</span></a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.app>
