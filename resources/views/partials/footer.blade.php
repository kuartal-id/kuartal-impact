@php
    $kFooter = config('kuartal.footer', []);
    $kSocial = config('kuartal.social', []);
    $kContactEmail = config('kuartal.contact_email');
@endphp
<footer class="k-grid-bg mt-auto border-t" style="border-color: var(--k-line)">
    <div class="k-container-wide py-14">
        <div class="grid gap-12 lg:grid-cols-[1.2fr_2fr]">
            <div>
                <x-wordmark size="h-9" />
                <p class="mt-5 max-w-sm text-sm k-muted">The Kuartal arm for social, environmental and climate causes: capital, AI, technology and grassroots solutions for humanity's biggest threats.</p>
                <p class="k-mono mt-6"><a class="hover:underline" href="mailto:{{ $kContactEmail }}">{{ $kContactEmail }}</a></p>
                <ul class="mt-6 flex items-center gap-4" aria-label="Kuartal on social media">
                    @foreach ($kSocial as $social)
                        <li><a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Kuartal on {{ $social['label'] }}" class="k-muted hover:text-[color:var(--k-accent)]"><x-social-icon :icon="$social['icon']" class="h-[18px] w-[18px]" /></a></li>
                    @endforeach
                </ul>
            </div>
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                @foreach ($kFooter as $col)
                    <nav aria-label="{{ $col['heading'] }}">
                        <h2 class="k-mono mb-4">{{ $col['heading'] }}</h2>
                        <ul class="space-y-2.5 text-sm">
                            @foreach ($col['links'] as $l)
                                <li><a href="{{ str_starts_with($l['url'], 'http') ? $l['url'] : url($l['url']) }}" class="hover:underline" style="color: var(--k-ink)">{{ $l['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endforeach
            </div>
        </div>
        <div class="k-rule mt-14 flex flex-col gap-4 pt-6 md:flex-row md:items-center md:justify-between">
            <p class="k-mono-muted">&copy; {{ date('Y') }} Kuartal Group. All rights reserved.</p>
            <div class="flex items-center gap-5">
                <a href="{{ route('newsroom.feed') }}" class="k-mono-muted hover:underline">RSS</a>
                @auth
                    @if (auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}" class="k-mono-muted hover:underline">Admin</a>@endif
                @else
                    <a href="{{ route('login.kuartal-id') }}" class="k-mono-muted hover:underline" rel="nofollow">Sign in with Kuartal ID</a>
                @endauth
            </div>
        </div>
    </div>
</footer>
