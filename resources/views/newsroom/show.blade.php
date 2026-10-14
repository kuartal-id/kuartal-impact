<x-layouts.app :title="($post->meta_title ?: $post->title).' — Kuartal Impact Newsroom'"
               :description="$post->meta_description ?: $post->summary()"
               ogType="article" :ogImage="$post->cover?->absoluteUrl()">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org', '@type' => 'NewsArticle',
            'headline' => $post->title, 'datePublished' => $post->published_at->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(), 'description' => $post->meta_description ?: $post->summary(),
            'image' => $post->cover ? [$post->cover->absoluteUrl()] : [],
            'publisher' => ['@type' => 'Organization', 'name' => 'Kuartal Impact'],
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    </x-slot:head>

    <article>
        <header class="k-page-hero">
            <div class="k-container relative max-w-4xl py-14 md:py-20">
                <nav aria-label="Breadcrumb" class="mb-6 text-sm text-gray-muted dark:text-white/60">
                    <a href="{{ route('newsroom.index') }}" class="hover:text-green-600">Newsroom</a>
                    <span aria-hidden="true"> / </span>
                    <a href="{{ route('newsroom.index', ['category' => $post->category]) }}" class="hover:text-green-600">{{ $post->categoryLabel() }}</a>
                </nav>
                <h1 class="text-3xl md:text-5xl leading-tight">{{ $post->title }}</h1>
                @if ($post->excerpt)<p class="mt-5 text-lg text-gray-muted dark:text-white/70">{{ $post->excerpt }}</p>@endif
                <p class="mt-6 text-sm text-gray-muted dark:text-white/60"><time datetime="{{ $post->published_at->toAtomString() }}">{{ $post->published_at->format('j F Y') }}</time> · {{ $post->readingMinutes() }} min read</p>
            </div>
        </header>

        @if ($post->cover)
            <div class="k-container max-w-5xl -mt-2 md:mt-10">
                <img src="{{ $post->cover->url() }}" alt="{{ $post->cover->alt_text ?? '' }}" class="w-full rounded-card object-cover max-h-[560px]" @if($post->cover->width) width="{{ $post->cover->width }}" height="{{ $post->cover->height }}" @endif>
            </div>
        @endif

        <div class="k-container max-w-3xl py-12">
            <div class="k-prose">{!! $post->body !!}</div>

            @if (! empty($post->tags))
                <ul class="mt-10 flex flex-wrap gap-2" aria-label="Tags">
                    @foreach ($post->tags as $t)<li><a class="k-chip" href="{{ route('newsroom.index', ['tag' => $t]) }}">#{{ $t }}</a></li>@endforeach
                </ul>
            @endif

            <div class="mt-10 flex flex-wrap items-center gap-3 border-t border-gray-border pt-6 text-sm dark:border-white/10">
                <span class="text-gray-muted dark:text-white/50">Share:</span>
                <a class="k-chip" target="_blank" rel="noopener noreferrer" href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('newsroom.show', $post)) }}">LinkedIn</a>
                <a class="k-chip" target="_blank" rel="noopener noreferrer" href="https://x.com/intent/post?url={{ urlencode(route('newsroom.show', $post)) }}&text={{ urlencode($post->title) }}">X</a>
                <a class="k-chip" href="mailto:?subject={{ rawurlencode($post->title) }}&body={{ rawurlencode(route('newsroom.show', $post)) }}">Email</a>
                <a class="k-chip ml-auto" href="{{ route('newsroom.feed') }}">RSS</a>
            </div>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="k-section k-band">
            <div class="k-container">
                <h2 class="text-2xl mb-6">More in {{ $post->categoryLabel() }}</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">@foreach ($related as $r)<x-post-card :post="$r" />@endforeach</div>
            </div>
        </section>
    @endif
</x-layouts.app>
