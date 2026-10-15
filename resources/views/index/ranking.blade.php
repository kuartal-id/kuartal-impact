<x-layouts.app :title="$heading.' — Rankings — Kuartal Impact Index'">
    <x-page-hero :eyebrow="'Kuartal Impact Index · '.$kind" :title="$heading" />
    <x-index.subnav />
    <section class="k-section !pt-10">
        <div class="k-container max-w-4xl">
            <a href="{{ route('impact-index.rankings') }}" class="k-meta underline">← All rankings</a> · <a href="{{ $filterUrl }}" class="k-meta underline">Open in search</a>
            @if (! empty($children) && $children->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-1">@foreach ($children as $c)<a class="k-chip" href="{{ route('impact-index.region', $c) }}">{{ $c->name }}</a>@endforeach</div>
            @endif
            <div class="mt-6">
                @if ($orgs->isEmpty())
                    <x-empty-state title="No organisations listed here yet">Organisations appear once they have been reviewed.</x-empty-state>
                @else
                    @php $rank = 0; @endphp
                    <div class="space-y-3">@foreach ($orgs as $org)<x-index.org-row :org="$org" :rank="$org->isRated() ? ++$rank : null" />@endforeach</div>
                @endif
            </div>
            <x-index.disclaimer class="mt-10" />
        </div>
    </section>
</x-layouts.app>
