@props(['post'])
<a href="{{ route('newsroom.show', $post) }}" {{ $attributes->merge(['class' => 'k-card group flex flex-col !p-0 overflow-hidden']) }}>
    <div class="aspect-[16/9] overflow-hidden">
        @if ($post->cover)
            <img src="{{ $post->cover->url() }}" alt="{{ $post->cover->alt_text ?? '' }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
        @else
            <div class="k-placeholder flex h-full w-full items-center justify-center"><span class="k-eyebrow">{{ $post->categoryLabel() }}</span></div>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-6">
        <p class="k-eyebrow mb-3">{{ $post->categoryLabel() }}</p>
        <h3 class="text-lg font-semibold leading-snug group-hover:underline">{{ $post->title }}</h3>
        <p class="mt-2 line-clamp-3 text-sm text-gray-muted dark:text-white/65">{{ $post->summary() }}</p>
        <p class="k-meta mt-auto pt-5"><time datetime="{{ $post->published_at?->toAtomString() }}">{{ $post->published_at?->format('j M Y') }}</time> · {{ $post->readingMinutes() }} min read</p>
    </div>
</a>
