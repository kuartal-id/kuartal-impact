<x-layouts.app title="Rankings — Kuartal Impact Index" description="Leaderboards of rated organisations: overall, by sector and by region.">
    <x-page-hero eyebrow="Kuartal Impact Index" title="Rankings">Only organisations with a published Impact Score appear in rankings.</x-page-hero>
    <x-index.subnav />
    <section class="k-section !pt-10">
        <div class="k-container grid gap-10 lg:grid-cols-[1fr_340px]">
            <div>
                <h2 class="text-2xl mb-4">Overall leaderboard</h2>
                @if ($leaders->isEmpty())
                    <x-empty-state title="No rated organisations yet">Rankings appear once organisations have been reviewed and scored. <a class="underline" href="{{ route('impact-index.methodology') }}">How we score</a>.</x-empty-state>
                @else
                    <div class="space-y-3">@foreach ($leaders as $org)<x-index.org-row :org="$org" :rank="$loop->iteration" />@endforeach</div>
                @endif
                <x-index.disclaimer class="mt-10" />
            </div>
            <aside class="space-y-6">
                <div class="k-surface p-5"><h2 class="font-display text-lg font-semibold mb-3">By sector</h2>
                    <div class="flex flex-wrap gap-2">@foreach ($sectors as $s)<a class="k-chip" href="{{ route('impact-index.sector', $s) }}">{{ $s->name }}</a>@endforeach</div></div>
                <div class="k-surface p-5"><h2 class="font-display text-lg font-semibold mb-3">By region</h2>
                    @foreach ($regions as $r)
                        <a class="k-navlink block font-semibold" href="{{ route('impact-index.region', $r) }}">{{ $r->name }}</a>
                        @if ($r->children->isNotEmpty())<div class="mt-2 mb-4 flex flex-wrap gap-1">@foreach ($r->children as $c)<a class="k-chip" href="{{ route('impact-index.region', $c) }}">{{ $c->name }}</a>@endforeach</div>@endif
                    @endforeach</div>
            </aside>
        </div>
    </section>
</x-layouts.app>
