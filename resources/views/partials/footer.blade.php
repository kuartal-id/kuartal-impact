@php
    $kFooter = config('kuartal.footer', []);
    $kSocial = config('kuartal.social', []);
    $kContactEmail = config('kuartal.contact_email');
    $kHref = fn ($u) => str_starts_with($u, 'http') ? $u : url($u);
@endphp
{{-- Social strip, as on kuartal.id: white strip (cream is never a full-width band) above the footer. --}}
<div class="border-t border-gray-border bg-white dark:border-white/10 dark:bg-surface-dark-alt">
    <div class="k-container-wide flex flex-col gap-4 py-6 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-muted dark:text-white/65">Follow us on social media</span>
            <span class="hidden h-4 w-px bg-gray-border sm:block dark:bg-white/15" aria-hidden="true"></span>
            <ul class="flex items-center gap-4" aria-label="Kuartal on social media">
                @foreach ($kSocial as $social)
                    <li><a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Kuartal on {{ $social['label'] }}" class="text-navy hover:text-black transition-colors dark:text-white/75 dark:hover:text-green-400"><x-social-icon :icon="$social['icon']" class="h-[18px] w-[18px]" /></a></li>
                @endforeach
            </ul>
        </div>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <a href="{{ route('newsroom.index') }}" class="k-navlink">Newsroom</a>
            <span class="h-4 w-px bg-gray-border dark:bg-white/15" aria-hidden="true"></span>
            <a href="mailto:{{ $kContactEmail }}" class="k-navlink">{{ $kContactEmail }}</a>
            <span class="h-4 w-px bg-gray-border dark:bg-white/15" aria-hidden="true"></span>
            <a href="{{ route('newsroom.feed') }}" class="k-navlink">RSS feed</a>
        </div>
    </div>
</div>

<footer class="bg-white dark:bg-black">
    <div class="k-container-wide py-14">
        <div class="flex flex-col gap-12 lg:flex-row lg:items-start">
            <div class="lg:w-[320px] lg:shrink-0">
                <x-wordmark size="h-20" />
                <p class="mt-5 max-w-[300px] text-sm text-gray-muted dark:text-white/65">The Kuartal arm for social, environmental and climate causes: capital, AI, technology and grassroots solutions for humanity's biggest threats.</p>
                <a href="mailto:{{ $kContactEmail }}" class="k-navlink mt-6 inline-block">{{ $kContactEmail }}</a>
            </div>
            <div class="grid grid-cols-2 gap-x-10 gap-y-8 sm:grid-cols-4 lg:ml-auto lg:flex lg:justify-end lg:gap-x-14">
                @foreach ($kFooter as $col)
                    <nav aria-label="{{ $col['heading'] }}" class="lg:w-[170px]">
                        <h2 class="k-eyebrow mb-3 !font-bold !text-xs">{{ $col['heading'] }}</h2>
                        <ul class="space-y-2">
                            @foreach ($col['links'] as $l)<li><a href="{{ $kHref($l['url']) }}" class="k-navlink">{{ $l['label'] }}</a></li>@endforeach
                        </ul>
                    </nav>
                @endforeach
            </div>
        </div>
    </div>
    <div class="k-container-wide pb-10">
        <div class="k-rule flex flex-col justify-between gap-4 pt-6 text-xs text-gray-muted md:flex-row md:items-center dark:text-white/55">
            <p>&copy; {{ date('Y') }} Kuartal Group. All rights reserved.</p>
            <div class="flex flex-wrap items-center gap-4">
                <a href="https://kuartal.id/legal" class="hover:text-navy dark:hover:text-green-400">Privacy</a>
                <a href="https://kuartal.id/legal" class="hover:text-navy dark:hover:text-green-400">Terms</a>
                <a href="{{ route('contact') }}" class="hover:text-navy dark:hover:text-green-400">Contact</a>
                @auth
                    @if (auth()->user()->isAdmin())<a href="{{ route('admin.dashboard') }}" class="hover:text-navy dark:hover:text-green-400">Admin</a>@endif
                @else
                    <a href="{{ route('login.kuartal-id') }}" class="hover:text-navy dark:hover:text-green-400" rel="nofollow">Sign in with Kuartal ID</a>
                @endauth
            </div>
        </div>
    </div>
</footer>
