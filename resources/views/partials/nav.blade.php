@php
    $kNav = config('kuartal.nav');
    $kActive = fn ($url) => $url === '/' ? request()->is('/') : request()->is(ltrim($url, '/').'*');
    try { $kFunds = \App\Models\SiteEntry::funds(); } catch (\Throwable $e) { $kFunds = collect(); }
    $kIcon = 'flex h-9 w-9 items-center justify-center rounded-full border border-gray-border text-navy hover:border-navy transition-colors dark:border-white/15 dark:text-white dark:hover:border-green-400 dark:hover:text-green-400';
@endphp
{{-- Header as on kuartal.id: sticky, see-through white (black in dark mode), round search + dark-mode buttons. --}}
<header class="sticky top-0 z-50 border-b border-gray-border bg-white/90 backdrop-blur dark:border-white/10 dark:bg-black/85">
    <div class="k-container-wide flex h-16 items-center justify-between gap-6">
        <a href="{{ url('/') }}" class="flex shrink-0 items-center" aria-label="Kuartal Impact home">
            <x-wordmark size="h-9 md:h-10" />
        </a>

        <nav class="hidden lg:flex items-center gap-0.5" aria-label="Main">
            @foreach ($kNav as $item)
                @if (! empty($item['links']))
                    <div class="relative flex items-center whitespace-nowrap rounded-full hover:bg-gray-bg dark:hover:bg-white/5" data-dd-wrap>
                        <a href="{{ url($item['url']) }}" class="k-navlink rounded-full py-2 pl-3 pr-0.5" @if($kActive($item['url'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                        <button type="button" class="k-navlink flex items-center rounded-full py-2 pl-0.5 pr-2" data-dd aria-expanded="false" aria-controls="dd-{{ $loop->index }}" aria-label="Show {{ $item['label'] }} menu">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <ul id="dd-{{ $loop->index }}" data-dd-panel class="absolute left-0 top-full mt-2 hidden min-w-52 rounded-card border border-gray-border bg-white p-2 shadow-card dark:border-white/10 dark:bg-surface-dark-alt">
                            @foreach ($item['links'] as $l)
                                <li><a href="{{ url($l['url']) }}" class="block rounded-lg px-3 py-2 font-subhead text-sm text-black/80 hover:bg-gray-bg hover:text-navy dark:text-white/80 dark:hover:bg-white/5 dark:hover:text-green-400">{{ $l['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <a href="{{ url($item['url']) }}" class="k-navlink whitespace-nowrap rounded-full px-3 py-2 hover:bg-gray-bg dark:hover:bg-white/5 aria-[current=page]:text-navy aria-[current=page]:font-semibold dark:aria-[current=page]:text-green-400" @if($kActive($item['url'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endif
            @endforeach
        </nav>

        <div class="hidden lg:flex items-center gap-2">
            <button type="button" data-search-toggle aria-expanded="false" class="{{ $kIcon }}" aria-label="Search Kuartal Impact">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            </button>
            <button type="button" data-theme-toggle aria-pressed="false" class="{{ $kIcon }}" aria-label="Toggle dark mode">
                <svg class="hidden h-[18px] w-[18px] dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                <svg class="h-[18px] w-[18px] dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </button>
        </div>

        {{-- Mobile --}}
        <div class="lg:hidden flex items-center gap-1">
            <button type="button" data-search-toggle aria-expanded="false" aria-label="Search Kuartal Impact" class="flex h-9 w-9 items-center justify-center text-navy dark:text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            </button>
            <button type="button" data-nav-toggle aria-expanded="false" aria-controls="k-mobile-nav" aria-label="Open menu" class="flex h-9 w-9 items-center justify-center text-navy dark:text-white">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>

    {{-- Mobile panel: scrolls on its own (the header is sticky). See app.css .k-mobile-panel. --}}
    <div id="k-mobile-nav" data-nav-panel class="k-mobile-panel hidden lg:hidden border-t border-gray-border bg-white dark:border-white/10 dark:bg-black">
        <nav class="k-container flex flex-col gap-1 py-4" aria-label="Mobile">
            @foreach ($kNav as $item)
                <a href="{{ url($item['url']) }}" class="k-navlink py-2" @if($kActive($item['url'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @if (! empty($item['links']))
                    <div class="ml-3 mb-2 flex flex-col gap-1 border-l border-gray-border pl-3 dark:border-white/10">
                        @foreach ($item['links'] as $l)
                            <a href="{{ url($l['url']) }}" class="font-subhead text-sm text-gray-muted py-1 dark:text-white/65">{{ $l['label'] }}</a>
                        @endforeach
                    </div>
                @endif
                @if ($item['url'] === '/programmes' && $kFunds->isNotEmpty())
                    <div class="ml-3 mb-2 flex flex-col gap-1 border-l border-gray-border pl-3 dark:border-white/10">
                        @foreach ($kFunds as $f)
                            <a href="{{ route('programmes.show', $f->slug) }}" class="font-subhead text-sm text-gray-muted py-1 dark:text-white/65">{{ $f->title }}</a>
                        @endforeach
                    </div>
                @endif
            @endforeach
            <button type="button" data-theme-toggle aria-pressed="false" class="k-navlink py-2 text-left">Toggle dark mode</button>
        </nav>
    </div>
</header>

{{-- Search panel (kuartal.id style). Behaviour: resources/js/search-panel.js. --}}
<div data-search-backdrop class="hidden fixed inset-0 z-[70] bg-black/40 backdrop-blur-[1px]"></div>
<aside data-search-panel aria-hidden="true" aria-label="Search"
       class="hidden fixed right-3 top-3 bottom-3 z-[80] flex w-[calc(100%-1.5rem)] md:w-1/3 md:min-w-[380px]
              translate-x-[calc(100%+0.75rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl
              transition-transform duration-300 ease-out dark:bg-surface-dark-alt">
    <div class="flex-1 overflow-y-auto p-6 md:p-8">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="font-display text-2xl font-semibold">Search Kuartal Impact</h2>
            <button type="button" data-search-close aria-label="Close search" class="flex h-9 w-9 items-center justify-center rounded-full text-black/60 hover:text-black dark:text-white/60 dark:hover:text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="relative mb-8">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-muted dark:text-white/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <label for="k-search" class="sr-only">Search</label>
            <input id="k-search" type="text" data-search-input placeholder="Search funds, focus areas, newsroom..."
                   class="w-full rounded-pill border border-gray-border bg-gray-bg py-3 pl-11 pr-4 text-sm text-black placeholder:text-gray-muted focus:border-navy focus:outline-none dark:border-white/15 dark:bg-white/5 dark:text-white dark:placeholder:text-white/50">
        </div>
        <div data-search-browse>
            @if ($kFunds->isNotEmpty())
                <div class="mb-8">
                    <p class="k-eyebrow mb-4">Funds</p>
                    <ul class="space-y-2">
                        @foreach ($kFunds as $f)<li><a href="{{ route('programmes.show', $f->slug) }}" data-category="Fund" class="k-navlink">{{ $f->title }}</a></li>@endforeach
                    </ul>
                </div>
            @endif
            @foreach ($kNav as $item)
                <div class="mb-8 last:mb-0">
                    <p class="k-eyebrow mb-4">{{ $item['label'] }}</p>
                    <ul class="space-y-2">
                        @forelse ($item['links'] ?? [] as $l)
                            <li><a href="{{ url($l['url']) }}" data-category="{{ $item['label'] }}" class="k-navlink">{{ $l['label'] }}</a></li>
                        @empty
                            <li><a href="{{ url($item['url']) }}" data-category="Kuartal Impact" class="k-navlink">{{ $item['label'] }}</a></li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
        <div data-search-results class="hidden space-y-1"></div>
        <p data-search-empty class="hidden text-sm text-gray-muted dark:text-white/60">Nothing matched that search. Try a different word.</p>
    </div>
</aside>
