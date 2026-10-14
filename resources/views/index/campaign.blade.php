<x-layouts.app :title="$campaign->title.' — Kuartal Impact Index campaign'" :description="\Illuminate\Support\Str::limit((string) $campaign->intro, 160)">
    <x-page-hero :eyebrow="$campaign->isActive() ? 'Kuartal Impact Index · Campaign' : 'Kuartal Impact Index · Past campaign'" :title="$campaign->title" />
    <x-index.subnav />
    <section class="k-section !pt-10">
        <div class="k-container max-w-4xl">
            @unless ($campaign->isActive())
                <div class="k-surface mb-8 p-5" role="status" data-campaign-ended><p class="font-semibold">This campaign has ended.</p><p class="k-meta">The list below is kept for reference and may be out of date. <a class="underline" href="{{ route('impact-index') }}">Search the full Index</a>.</p></div>
            @endunless
            @if ($campaign->intro)<div class="k-prose mb-8">{!! nl2br(e($campaign->intro)) !!}</div>@endif

            <h2 class="text-2xl mb-4">Organisations</h2>
            @if ($orgs->isEmpty())
                <x-empty-state title="No organisations listed yet">We list organisations here only after reviewing them. Check back soon.</x-empty-state>
            @else
                <div class="space-y-3">@foreach ($orgs as $org)<x-index.org-row :org="$org" />@endforeach</div>
            @endif

            @if ($campaign->give_safely)
                <div class="k-surface mt-10 p-6"><h2 class="font-display text-lg font-semibold mb-2">How to give safely</h2><p class="text-sm">{!! nl2br(e($campaign->give_safely)) !!}</p></div>
            @endif
            <x-index.disclaimer class="mt-8" />
        </div>
    </section>
</x-layouts.app>
