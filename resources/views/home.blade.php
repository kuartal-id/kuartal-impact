{{--
    Kuartal Impact home, in kuartal.id's layout: full-screen hero over the real
    Rinjani / Segara Anak footage kuartal.id uses (streamed from kuartal.id; poster
    frame stored here), then alternating white / cream sections of rounded cards.
    Lists are admin-managed with plain empty states. Nothing invented.
--}}
<x-layouts.app title="Kuartal Impact — capital and technology for humanity's biggest threats">
    <section class="relative isolate flex h-[88vh] min-h-[560px] items-center justify-center overflow-hidden">
        <div class="k-hero-bg"></div>
        <video data-hero-video autoplay muted loop playsinline preload="metadata"
               poster="{{ asset('images/hero-poster.jpg') }}"
               class="absolute inset-0 -z-10 h-full w-full object-cover" aria-hidden="true">
            <source src="https://kuartal.id/videos/kuartal-hero.mp4" type="video/mp4">
        </video>
        <div class="k-hero-overlay"></div>

        <div class="k-container relative text-center">
            <div class="mb-8 flex justify-center">
                {{-- Always the white logo here: it sits on the dark footage in both modes. --}}
                <x-wordmark tone="white" size="h-20 md:h-24" />
            </div>
            <h1 class="text-4xl md:text-6xl lg:text-7xl leading-[1.08] font-display font-semibold !text-white">
                Capital for the problems<br class="hidden md:block">
                that <span class="text-green-400">can't wait</span>.
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-white/85">Kuartal Impact is the Kuartal arm for social, environmental and climate causes. We invest in AI, technology and grassroots solutions to humanity's biggest existential threats.</p>
            <div class="mt-10 flex flex-wrap justify-center gap-3">
                <a href="{{ route('programmes.index') }}" class="k-btn-primary">Explore the funds</a>
                <a href="{{ route('about') }}" class="k-btn-outline-white">Our thesis</a>
            </div>
        </div>

        <a href="#funds" class="absolute bottom-8 left-1/2 -translate-x-1/2 text-white/75 hover:text-white transition-colors" aria-label="Scroll down">
            <svg class="h-6 w-6 animate-bounce motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
        </a>
    </section>

    <section id="funds" class="k-section scroll-mt-16">
        <div class="k-container">
            <x-section-header eyebrow="Running programmes" title="Funds working on the ground now" align="center">
                Each fund backs one problem, on the ground. Open a fund for its brief, figures and projects.
            </x-section-header>
            @if ($funds->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($funds as $fund)<x-fund-card :fund="$fund" :index="$loop->iteration" />@endforeach
                </div>
            @else
                <x-empty-state title="Funds coming online">Fund pages will appear here once they are published.</x-empty-state>
            @endif
        </div>
    </section>

    <section class="k-section k-band">
        <div class="k-container">
            <x-section-header eyebrow="Thesis" title="Where we point capital" align="center" />
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
                @foreach (config('kuartalimpact.focus_areas') as $key => $fa)
                    <li class="k-card flex flex-col gap-3">
                        <h3 class="text-xl">{{ $fa['label'] }}</h3>
                        <p class="text-sm text-gray-muted dark:text-white/65">{{ $fa['body'] }}</p>
                    </li>
                @endforeach
            </ul>
            <div class="mt-10 text-center"><a href="{{ route('focus-areas') }}" class="k-btn-outline">All focus areas</a></div>
        </div>
    </section>

    @if ($quote)
        <section class="k-section">
            <figure class="k-container max-w-4xl text-center">
                <p class="k-eyebrow mb-6">Why we exist</p>
                <blockquote class="font-display text-2xl not-italic leading-snug md:text-4xl">“{{ $quote }}”</blockquote>
                @if ($quoteBy)<figcaption class="mt-8 font-subhead text-sm text-gray-muted dark:text-white/65">{{ $quoteBy }}</figcaption>@endif
            </figure>
        </section>
    @endif

    @if ($partners->isNotEmpty())
        <section class="k-section {{ $quote ? '!pt-0' : '' }}">
            <div class="k-container text-center">
                <p class="k-eyebrow mb-6">Partners</p>
                <ul class="flex flex-wrap items-center justify-center gap-x-10 gap-y-6">
                    @foreach ($partners as $p)
                        <li>@if ($p->media)<img src="{{ $p->media->url() }}" alt="{{ $p->title }}" class="h-10 w-auto">@else<span class="font-display text-lg">{{ $p->title }}</span>@endif</li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <section class="k-section k-band">
        <div class="k-container">
            <x-section-header eyebrow="Newsroom" title="News from the field" align="center" />
            @if ($posts->isNotEmpty())
                <div class="grid gap-6 md:grid-cols-3">@foreach ($posts as $post)<x-post-card :post="$post" />@endforeach</div>
            @else
                <x-empty-state title="No posts yet">News, field notes and announcements will be published here.</x-empty-state>
            @endif
            <div class="mt-10 text-center"><a href="{{ route('newsroom.index') }}" class="k-btn-outline">All news</a></div>
        </div>
    </section>

    <section class="k-section">
        <div class="k-container text-center">
            <h2 class="mx-auto max-w-2xl text-3xl md:text-4xl">For founders and investors who want to do more.</h2>
            <div class="mt-8"><a href="{{ route('contact') }}" class="k-btn-primary">Get in touch</a></div>
        </div>
    </section>
</x-layouts.app>
