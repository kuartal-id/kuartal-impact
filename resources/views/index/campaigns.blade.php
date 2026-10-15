<x-layouts.app title="Campaigns — Kuartal Impact Index" description="Time-limited pages listing rated organisations working on a specific crisis or place.">
    <x-page-hero eyebrow="Kuartal Impact Index" title="Campaigns">Time-limited pages that group organisations working on one crisis or place, with their scores.</x-page-hero>
    <x-index.subnav />
    <section class="k-section !pt-10">
        <div class="k-container">
            @if ($active->isEmpty())
                <x-empty-state title="No active campaigns">There are no campaigns running right now.</x-empty-state>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($active as $c)
                        <a href="{{ route('impact-index.campaign', $c) }}" class="k-card flex flex-col">
                            <p class="k-eyebrow mb-2">Active campaign</p>
                            <h2 class="font-display text-xl font-semibold">{{ $c->title }}</h2>
                            <p class="k-meta mt-2 line-clamp-4">{{ $c->intro }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
            @if ($ended->isNotEmpty())
                <div class="mt-16 k-rule pt-8">
                    <h2 class="font-subhead text-sm font-semibold k-muted mb-3">Past campaigns</h2>
                    <ul class="space-y-1 text-sm k-muted">
                        @foreach ($ended as $c)<li><a class="underline" href="{{ route('impact-index.campaign', $c) }}">{{ $c->title }}</a>@if($c->ends_at) · ended {{ $c->ends_at->format('M Y') }}@endif</li>@endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>
</x-layouts.app>
