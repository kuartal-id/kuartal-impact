@props(['org', 'rank' => null])
<a href="{{ route('impact-index.show', $org) }}" class="k-card flex items-center gap-3 p-4 sm:gap-4 transition-colors hover:border-navy dark:hover:border-green-400" data-org-row>
    @if ($rank)<span class="w-6 shrink-0 text-center sm:w-8 font-display text-lg font-semibold k-muted">{{ $rank }}</span>@endif
    @if ($org->logo_url)
        <img src="{{ $org->logo_url }}" alt="" class="h-10 w-10 shrink-0 rounded-lg bg-white object-contain sm:h-12 sm:w-12" loading="lazy">
    @else
        <span class="k-surface flex h-10 w-10 shrink-0 sm:h-12 sm:w-12 items-center justify-center font-display font-semibold k-accent" aria-hidden="true">{{ mb_substr($org->name, 0, 1) }}</span>
    @endif
    <span class="min-w-0 flex-1">
        <span class="line-clamp-2 break-words sm:block font-display text-base font-semibold leading-snug sm:truncate sm:text-lg">{{ $org->name }}</span>
        <span class="k-meta block truncate">{{ $org->typeLabel() }}@if($org->country) · {{ $org->country }}@endif @if($org->sectors->isNotEmpty()) · {{ $org->sectors->pluck('name')->take(3)->implode(', ') }}@endif</span>
    </span>
    <x-index.grade :grade="$org->overall_grade" :score="$org->overall_score" class="shrink-0" />
</a>
