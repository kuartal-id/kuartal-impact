<x-layouts.app title="About — Kuartal Impact" description="Kuartal Impact's thesis: capital, AI, technology and grassroots solutions for climate, environment and society.">
    <x-page-hero eyebrow="About" code="A-00" title="Profit with purpose, pointed at existential risk">
        Kuartal Impact is the Kuartal arm focused on social, environmental and climate causes, investing in AI, technology and grassroots solutions to humanity's biggest existential threats.
    </x-page-hero>

    <section id="thesis" class="k-section scroll-mt-20">
        <div class="k-container-wide grid gap-12 lg:grid-cols-[1fr_2fr]">
            <div><p class="k-eyebrow">01 / Our thesis</p><h2 class="mt-3 text-3xl md:text-4xl">Five fronts, one test: does it work on the ground?</h2></div>
            <ol class="grid gap-px sm:grid-cols-2" style="background: var(--k-line)">
                @foreach (config('kuartalimpact.focus_areas') as $fa)
                    <li class="p-6" style="background: var(--k-bg)"><span class="k-index">{{ $fa['code'] }}</span><h3 class="mt-3 text-xl">{{ $fa['label'] }}</h3><p class="mt-2 text-sm k-muted">{{ $fa['body'] }}</p></li>
                @endforeach
            </ol>
        </div>
    </section>

    <section id="how-we-invest" class="k-section k-band k-topo scroll-mt-20">
        <div class="k-container-wide grid gap-12 lg:grid-cols-[1fr_2fr]">
            <div><p class="k-eyebrow">02 / How we invest</p><h2 class="mt-3 text-3xl md:text-4xl">Partnering with the people doing the work</h2></div>
            <div>
                @if ($howWeInvest)<div class="k-prose">{!! nl2br(e($howWeInvest)) !!}</div>
                @else<x-empty-state title="Investment approach to be published" />@endif
                <a href="{{ route('programmes.index') }}" class="k-btn-outline mt-8">See the funds</a>
            </div>
        </div>
    </section>

    <section id="team" class="k-section scroll-mt-20">
        <div class="k-container-wide">
            <x-section-header eyebrow="Team" index="03" title="The people behind it">{{ $teamIntro }}</x-section-header>
            @forelse ($people as $group => $members)
                <h3 class="k-mono mb-5 mt-10 first:mt-0">{{ config('kuartalimpact.person_groups.'.$group, $group) }}</h3>
                <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($members as $m)
                        <li class="k-panel !p-5">
                            @if ($m->media)<img src="{{ $m->media->url() }}" alt="" class="mb-4 aspect-square w-full object-cover grayscale">@endif
                            <p class="font-display text-lg">@if ($m->url)<a class="hover:underline" href="{{ $m->url }}">{{ $m->title }}</a>@else{{ $m->title }}@endif</p>
                            @if ($m->subtitle)<p class="k-mono-muted mt-1">{{ $m->subtitle }}</p>@endif
                            @if ($m->body)<p class="mt-3 text-sm k-muted">{{ $m->body }}</p>@endif
                        </li>
                    @endforeach
                </ul>
            @empty
                <x-empty-state title="Team profiles to be published">Profiles will be listed here once published.</x-empty-state>
            @endforelse
        </div>
    </section>
</x-layouts.app>
