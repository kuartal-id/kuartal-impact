@props(['grade' => null, 'score' => null, 'size' => 'md'])
@php $cls = match (true) { $grade === null => 'k-grade-none', in_array($grade, ['A+', 'A']) => 'k-grade-top', $grade === 'B' => 'k-grade-mid', default => 'k-grade-low' }; @endphp
@if ($grade === null)
    <span {{ $attributes->merge(['class' => 'k-grade '.$cls]) }}>Not yet rated</span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
        <span class="k-grade {{ $cls }} {{ $size === 'lg' ? 'text-2xl px-3 py-2' : '' }}" aria-label="Grade {{ $grade }}">{{ $grade }}</span>
        @if ($score !== null)<span class="font-display {{ $size === 'lg' ? 'text-3xl' : 'text-base' }} font-semibold">{{ number_format($score, 1) }}<span class="k-meta"> / 100</span></span>@endif
    </span>
@endif
