<x-layouts.app title="Kuartal Impact Index — find effective organisations to give to" description="An independent, evidence-based index of NGOs, charities, foundations, cooperatives and social enterprises, graded on impact, finances, transparency, governance and community leadership.">
    <x-page-hero eyebrow="Kuartal Impact Index" title="Give where it does the most good">
        Organisations graded on evidence across five pillars: impact, financial health, transparency, governance and community leadership.
        <a class="underline" href="{{ route('impact-index.methodology') }}">How we score</a>.
    </x-page-hero>
    <x-index.subnav />

    @if ($campaigns->isNotEmpty())
        <div class="k-container pt-6">
            <div class="k-surface flex flex-wrap items-center gap-3 p-4">
                <span class="k-eyebrow">Campaigns</span>
                @foreach ($campaigns as $c)<a href="{{ route('impact-index.campaign', $c) }}" class="k-chip">{{ $c->title }}</a>@endforeach
            </div>
        </div>
    @endif

    <section class="k-section !pt-10">
        <div class="k-container grid gap-10 lg:grid-cols-[300px_1fr]">
            <form method="GET" action="{{ route('impact-index') }}" class="k-surface h-fit space-y-4 p-5" role="search" aria-label="Filter organisations">
                <div><label class="k-label" for="ix-q">Search</label><input id="ix-q" class="k-input" name="q" value="{{ $params['q'] ?? '' }}" placeholder="Organisation name or keyword"></div>
                <div><label class="k-label" for="ix-region">Region</label>
                    <select id="ix-region" name="region" class="k-input"><option value="">All regions</option>
                        @foreach ($regions as $r)<option value="{{ $r->slug }}" @selected(($params['region'] ?? '') === $r->slug)>{{ str_repeat('— ', $r->depth) }}{{ $r->name }}</option>@endforeach
                    </select></div>
                <div><label class="k-label" for="ix-sector">Sector</label>
                    <select id="ix-sector" name="sector" class="k-input"><option value="">All sectors</option>
                        @foreach ($sectors as $s)<option value="{{ $s->slug }}" @selected(($params['sector'] ?? '') === $s->slug)>{{ $s->name }}</option>@endforeach
                    </select></div>
                <div><label class="k-label" for="ix-type">Type</label>
                    <select id="ix-type" name="type" class="k-input"><option value="">All types</option>
                        @foreach (\App\Models\Index\Organisation::TYPES as $k => $v)<option value="{{ $k }}" @selected(($params['type'] ?? '') === $k)>{{ $v }}</option>@endforeach
                    </select></div>
                <div><label class="k-label" for="ix-grade">Grade</label>
                    <select id="ix-grade" name="grade" class="k-input"><option value="">Any grade</option>
                        @foreach (array_keys(\App\Support\ImpactIndex\Scoring::GRADES) as $g)<option value="{{ $g }}" @selected(($params['grade'] ?? '') === $g)>{{ $g }}</option>@endforeach
                        <option value="unrated" @selected(($params['grade'] ?? '') === 'unrated')>Not yet rated</option>
                    </select></div>
                <fieldset><legend class="k-label">Score range</legend>
                    <div class="flex gap-2"><input class="k-input" type="number" min="0" max="100" name="min" value="{{ $params['min'] ?? '' }}" placeholder="0" aria-label="Minimum score"><input class="k-input" type="number" min="0" max="100" name="max" value="{{ $params['max'] ?? '' }}" placeholder="100" aria-label="Maximum score"></div></fieldset>
                <div><label class="k-label" for="ix-sort">Sort by</label>
                    <select id="ix-sort" name="sort" class="k-input">@foreach (\App\Support\ImpactIndex\OrganisationSearch::SORTS as $k => $v)<option value="{{ $k }}" @selected(($params['sort'] ?? 'score') === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="flex gap-2"><button class="k-btn-primary">Apply</button>@if($filtered)<a href="{{ route('impact-index') }}" class="k-btn-ghost">Clear</a>@endif</div>
            </form>

            <div>
                <p class="k-meta mb-4" aria-live="polite">{{ $orgs->total() }} {{ \Illuminate\Support\Str::plural('organisation', $orgs->total()) }}{{ $filtered ? ' match' : ' listed' }}</p>
                @if ($orgs->isEmpty())
                    @if ($total === 0)
                        <x-empty-state title="The Index is being built">We publish an organisation only after reviewing its evidence. The first profiles will appear here soon. Read the <a class="underline" href="{{ route('impact-index.methodology') }}">methodology</a> to see how we grade.</x-empty-state>
                    @else
                        <x-empty-state title="No organisations match">Try removing a filter or searching a different word.</x-empty-state>
                    @endif
                @else
                    <div class="space-y-3">@foreach ($orgs as $org)<x-index.org-row :org="$org" />@endforeach</div>
                    <div class="mt-6">{{ $orgs->links() }}</div>
                @endif
                <x-index.disclaimer class="mt-10" />
            </div>
        </div>
    </section>
</x-layouts.app>
