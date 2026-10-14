@props(['fund', 'index' => 1])
@php $fa = $fund->focusArea(); $motif = config('kuartalimpact.fund_motifs.'.$fund->slug, 'roots'); @endphp
<a href="{{ route('programmes.show', $fund->slug) }}" {{ $attributes->merge(['class' => 'k-panel k-ticks group flex flex-col gap-6 transition-colors']) }}>
    <div class="flex items-center justify-between">
        <span class="k-index">F-{{ str_pad($index, 2, '0', STR_PAD_LEFT) }}</span>
        @if ($fa)<span class="k-mono-muted">{{ $fa['label'] }}</span>@endif
    </div>
    @if ($fund->media)
        <img src="{{ $fund->media->url() }}" alt="{{ $fund->media->alt_text ?? '' }}" loading="lazy" class="aspect-[16/9] w-full object-cover">
    @else
        <div class="k-grid-bg -mx-6 border-y md:-mx-8" style="border-color: var(--k-line)"><x-motif :name="$motif" class="mx-auto h-40 w-full max-w-md md:h-48" /></div>
    @endif
    <div>
        <h3 class="text-2xl md:text-3xl">{{ $fund->title }}</h3>
        @if ($fund->subtitle)<p class="mt-3 k-muted">{{ $fund->subtitle }}</p>@endif
    </div>
    <span class="k-mono mt-auto inline-flex items-center gap-2">Open fund <span aria-hidden="true" class="transition-transform group-hover:translate-x-1">→</span></span>
</a>
