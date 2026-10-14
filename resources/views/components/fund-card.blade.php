@props(['fund', 'index' => 1])
@php $fa = $fund->focusArea(); @endphp
{{-- kuartal.id-style rounded card: photo (or a plain forest panel with the fund name) over text. --}}
<a href="{{ route('programmes.show', $fund->slug) }}" {{ $attributes->merge(['class' => 'k-card group flex flex-col !p-0 overflow-hidden hover:-translate-y-0.5 transition-all']) }}>
    <div class="aspect-[16/9] overflow-hidden">
        @if ($fund->media)
            <img src="{{ $fund->media->url() }}" alt="{{ $fund->media->alt_text ?? '' }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
        @else
            <div class="flex h-full w-full items-end bg-forest p-6"><span class="font-display text-2xl font-semibold text-cream md:text-3xl">{{ $fund->title }}</span></div>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-6">
        @if ($fa)<p class="k-eyebrow mb-3">{{ $fa['label'] }}</p>@endif
        <h3 class="text-2xl">{{ $fund->title }}</h3>
        @if ($fund->subtitle)<p class="mt-2 text-gray-muted dark:text-white/65">{{ $fund->subtitle }}</p>@endif
        <span class="mt-6 inline-flex items-center gap-2 font-subhead text-sm font-medium text-navy dark:text-green-400">Open fund <span aria-hidden="true" class="transition-transform group-hover:translate-x-1">→</span></span>
    </div>
</a>
