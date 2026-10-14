<x-layouts.app title="Newsroom — Kuartal Impact" description="News, field notes and announcements from Kuartal Impact.">
    <x-page-hero eyebrow="Newsroom" title="Signals from the field" icon="news">
        Announcements, fund updates and field notes from Kuartal Impact.
        <a href="{{ route('newsroom.feed') }}" class="mt-4 inline-flex items-center gap-2 text-sm k-accent hover:underline">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="6" cy="18" r="2"/><path d="M4 4v3a13 13 0 0 1 13 13h3A16 16 0 0 0 4 4zm0 6v3a7 7 0 0 1 7 7h3A10 10 0 0 0 4 10z"/></svg>
            Subscribe via RSS
        </a>
    </x-page-hero>

    <section class="k-section !pt-10">
        <div class="k-container">
            <form method="GET" action="{{ route('newsroom.index') }}" class="mb-8 flex flex-col gap-4 md:flex-row md:items-end" role="search">
                <div class="flex-1">
                    <label for="nr-q" class="k-label">Search the newsroom</label>
                    <input id="nr-q" name="q" type="search" value="{{ $filters['q'] }}" class="k-input !py-2.5 dark:!bg-white/5 dark:!border-white/15 dark:text-white" placeholder="Search posts">
                </div>
                <div>
                    <label for="nr-year" class="k-label">Year</label>
                    <select id="nr-year" name="year" class="k-input !py-2.5 dark:!bg-white/5 dark:!border-white/15 dark:text-white">
                        <option value="">All years</option>
                        @foreach ($years as $y)<option value="{{ $y }}" @selected((string) $filters['year'] === (string) $y)>{{ $y }}</option>@endforeach
                    </select>
                </div>
                @if ($filters['category'])<input type="hidden" name="category" value="{{ $filters['category'] }}">@endif
                @if ($filters['tag'])<input type="hidden" name="tag" value="{{ $filters['tag'] }}">@endif
                <button class="k-btn-primary">Search</button>
            </form>

            @if ($categories->isNotEmpty())
                <div class="mb-4 flex flex-wrap gap-2" aria-label="Filter by category">
                    <a href="{{ route('newsroom.index', array_filter(['q' => $filters['q'], 'year' => $filters['year'], 'tag' => $filters['tag']])) }}" class="k-chip {{ ! $filters['category'] ? 'is-active' : '' }}" @if(! $filters['category']) aria-current="true" @endif>All</a>
                    @foreach ($categories as $cat)
                        <a href="{{ route('newsroom.index', array_filter(['category' => $cat, 'q' => $filters['q'], 'year' => $filters['year'], 'tag' => $filters['tag']])) }}"
                           class="k-chip {{ $filters['category'] === $cat ? 'is-active' : '' }}" @if($filters['category'] === $cat) aria-current="true" @endif>{{ config('kuartalimpact.post_categories.'.$cat, $cat) }}</a>
                    @endforeach
                </div>
            @endif
            @if ($tags->isNotEmpty())
                <div class="mb-10 flex flex-wrap items-center gap-2 text-xs" aria-label="Filter by tag">
                    <span class="text-gray-muted dark:text-white/50">Tags:</span>
                    @foreach ($tags as $t)
                        <a href="{{ route('newsroom.index', array_filter(['tag' => $filters['tag'] === $t ? null : $t, 'category' => $filters['category'], 'q' => $filters['q'], 'year' => $filters['year']])) }}" class="k-chip !text-[11px] {{ $filters['tag'] === $t ? 'is-active' : '' }}">#{{ $t }}</a>
                    @endforeach
                </div>
            @endif

            @if ($posts->isNotEmpty())
                @php $lead = $posts->onFirstPage() && ! array_filter($filters) ? $posts->first() : null; @endphp
                @if ($lead)
                    <a href="{{ route('newsroom.show', $lead) }}" class="group mb-10 grid overflow-hidden k-panel !p-0 md:grid-cols-2 hover:border-green-400 dark:border-white/10 dark:bg-surface-dark-alt">
                        <div class="aspect-[16/10] overflow-hidden">
                            @if ($lead->cover)<img src="{{ $lead->cover->url() }}" alt="{{ $lead->cover->alt_text ?? '' }}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
                            @else<div class="k-placeholder k-grid-bg flex h-full w-full items-center justify-center"><span class="k-mono">{{ $lead->categoryLabel() }}</span></div>@endif
                        </div>
                        <div class="flex flex-col justify-center p-8">
                            <p class="k-eyebrow mb-3">{{ $lead->categoryLabel() }} · Latest</p>
                            <h2 class="text-2xl md:text-3xl group-hover:text-green-600 dark:group-hover:text-green-300">{{ $lead->title }}</h2>
                            <p class="mt-3 text-gray-muted dark:text-white/60">{{ $lead->summary() }}</p>
                            <p class="mt-5 text-xs text-gray-muted dark:text-white/50">{{ $lead->published_at->format('j F Y') }} · {{ $lead->readingMinutes() }} min read</p>
                        </div>
                    </a>
                @endif
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($posts as $post)
                        @continue($lead && $post->is($lead))
                        <x-post-card :post="$post" />
                    @endforeach
                </div>
                <div class="mt-10">{{ $posts->links() }}</div>
            @elseif (array_filter($filters))
                <x-empty-state icon="news" title="No posts match those filters"><a class="k-navlink" href="{{ route('newsroom.index') }}">Clear filters</a></x-empty-state>
            @else
                <x-empty-state icon="news" title="No news posts yet">Announcements, fund updates and field notes will be published here. Subscribe to the RSS feed to be notified.</x-empty-state>
            @endif
        </div>
    </section>
</x-layouts.app>
