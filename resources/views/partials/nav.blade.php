@php
    $kNav = config('kuartal.nav');
    $kActive = fn ($url) => $url === '/' ? request()->is('/') : request()->is(ltrim($url, '/').'*');
@endphp
<header class="k-header sticky top-0 z-50">
    <div class="k-container-wide flex h-16 items-center gap-6">
        <a href="{{ url('/') }}" class="shrink-0" aria-label="Kuartal Impact home">
            <x-wordmark size="h-6 md:h-7" />
        </a>
        <span class="k-mono-muted hidden 2xl:inline">// a kuartal company</span>

        <nav class="ml-auto hidden lg:block" aria-label="Main">
            <ul class="flex items-center gap-7">
                @foreach ($kNav as $item)
                    <li class="relative">
                        @if (! empty($item['links']))
                            <div class="flex items-center gap-1">
                                <a href="{{ url($item['url']) }}" class="k-nav-item" @if($kActive($item['url'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                                <button type="button" class="k-nav-item p-1" data-dd aria-expanded="false" aria-controls="dd-{{ $loop->index }}">
                                    <span class="sr-only">{{ $item['label'] }} menu</span>
                                    <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M2 4l4 4 4-4"/></svg>
                                </button>
                            </div>
                            <ul id="dd-{{ $loop->index }}" data-dd-panel class="k-dropdown absolute left-0 top-full mt-3 hidden min-w-52 p-2">
                                @foreach ($item['links'] as $l)
                                    <li><a href="{{ url($l['url']) }}" class="k-nav-item block px-3 py-2 !normal-case !tracking-normal !text-sm !font-sans">{{ $l['label'] }}</a></li>
                                @endforeach
                            </ul>
                        @else
                            <a href="{{ url($item['url']) }}" class="k-nav-item" @if($kActive($item['url'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="ml-auto flex items-center gap-2 lg:ml-4">
            <button type="button" data-theme-toggle aria-pressed="true" class="k-btn-ghost !px-2 !py-2" title="Toggle light / dark">
                <span class="sr-only">Toggle dark mode</span>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 3v18" /><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/></svg>
            </button>
            <a href="{{ url('/contact') }}" class="k-btn-primary hidden !py-2 sm:inline-flex">Get in touch</a>
            <button type="button" class="k-btn-outline !px-3 !py-2 lg:hidden" data-nav-toggle aria-expanded="false" aria-controls="k-mobile-nav">
                <span class="sr-only">Menu</span>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 7h18M3 12h18M3 17h12"/></svg>
            </button>
        </div>
    </div>

    <nav id="k-mobile-nav" data-nav-panel class="k-mobile-panel hidden lg:hidden" aria-label="Mobile">
        <ul class="k-container-wide py-4">
            @foreach ($kNav as $item)
                <li class="k-rule py-3 first:border-0">
                    <a href="{{ url($item['url']) }}" class="flex items-baseline gap-3 py-1 font-display text-xl" @if($kActive($item['url'])) aria-current="page" @endif>
                        <span class="k-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $item['label'] }}
                    </a>
                    @if (! empty($item['links']))
                        <ul class="mt-1 pl-9">
                            @foreach ($item['links'] as $l)
                                <li><a href="{{ url($l['url']) }}" class="block py-1.5 text-sm k-muted hover:underline">{{ $l['label'] }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
            <li class="pt-4"><a href="{{ url('/contact') }}" class="k-btn-primary w-full">Get in touch</a></li>
        </ul>
    </nav>
</header>
