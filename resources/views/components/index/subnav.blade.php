<nav class="k-container flex flex-wrap gap-2 pt-8" aria-label="Impact Index">
    @foreach (['impact-index' => 'Search', 'impact-index.rankings' => 'Rankings', 'impact-index.campaigns' => 'Campaigns', 'impact-index.methodology' => 'Methodology'] as $r => $l)
        <a href="{{ route($r) }}" class="k-chip {{ request()->routeIs($r) ? 'is-active' : '' }}" @if(request()->routeIs($r)) aria-current="page" @endif>{{ $l }}</a>
    @endforeach
</nav>
