<x-layouts.app title="About — Kuartal Impact" description="Kuartal Impact's thesis: capital, AI, technology and grassroots solutions for climate, environment and society.">
    <x-page-hero eyebrow="About" title="Profit with purpose, pointed at existential risk">
        Kuartal Impact is the Kuartal arm focused on social, environmental and climate causes, investing in AI, technology and grassroots solutions to humanity's biggest existential threats.
    </x-page-hero>

    <section id="thesis" class="k-section scroll-mt-20">
        <div class="k-container">
            <x-section-header eyebrow="Our thesis" title="Five fronts, one test: does it work on the ground?" align="center" />
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
                @foreach (config('kuartalimpact.focus_areas') as $fa)
                    <li class="k-card"><h3 class="text-xl">{{ $fa['label'] }}</h3><p class="mt-2 text-sm text-gray-muted dark:text-white/65">{{ $fa['body'] }}</p></li>
                @endforeach
            </ul>
        </div>
    </section>

    <section id="how-we-invest" class="k-section k-band scroll-mt-20">
        <div class="k-container grid gap-10 md:[grid-template-columns:5fr_7fr]">
            <div>
                <p class="k-eyebrow mb-4">How we invest</p>
                <h2 class="text-3xl md:text-4xl">Partnering with the people doing the work</h2>
            </div>
            <div>
                @if ($howWeInvest)<div class="k-prose">{!! nl2br(e($howWeInvest)) !!}</div>
                @else<x-empty-state title="Investment approach to be published" />@endif
                <a href="{{ route('programmes.index') }}" class="k-btn-primary mt-8">See the funds</a>
            </div>
        </div>
    </section>

    <section id="team" class="k-section scroll-mt-20">
        <div class="k-container">
            <x-section-header eyebrow="Team" title="The people behind it" align="center">{{ $teamIntro }}</x-section-header>
            @forelse ($people as $group => $members)
                <h3 class="mb-5 mt-10 text-xl first:mt-0">{{ config('kuartalimpact.person_groups.'.$group, $group) }}</h3>
                <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($members as $m)
                        <li class="k-card !p-0 overflow-hidden">
                            @if ($m->media)<img src="{{ $m->media->url() }}" alt="" class="aspect-square w-full object-cover">@endif
                            <div class="p-5">
                                <p class="font-display text-lg font-semibold">@if ($m->url)<a class="hover:underline" href="{{ $m->url }}">{{ $m->title }}</a>@else{{ $m->title }}@endif</p>
                                @if ($m->subtitle)<p class="k-meta mt-1">{{ $m->subtitle }}</p>@endif
                                @if ($m->body)<p class="mt-3 text-sm text-gray-muted dark:text-white/65">{{ $m->body }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @empty
                <x-empty-state title="Team profiles to be published">Profiles will be listed here once published.</x-empty-state>
            @endforelse
        </div>
    </section>
</x-layouts.app>
