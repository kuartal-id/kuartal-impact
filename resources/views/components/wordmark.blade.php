@props(['tone' => 'auto', 'size' => 'h-10'])
{{-- Kuartal Impact logo (owner's artwork in public/images/logo). auto: black in light mode, neon in dark mode.
     tone="light" pins black, "dark" pins neon, "white" for photo/video grounds. --}}
@php $src = fn ($v) => asset("images/logo/kuartal-impact-$v.webp"); @endphp
<span {{ $attributes->merge(['class' => 'k-wordmark inline-flex items-center']) }}>
    @if (in_array($tone, ['light', 'black'], true))
        <img src="{{ $src('black') }}" alt="Kuartal Impact" class="{{ $size }} w-auto" width="1283" height="713">
    @elseif ($tone === 'dark')
        <img src="{{ $src('lime') }}" alt="Kuartal Impact" class="{{ $size }} w-auto" width="1283" height="713">
    @elseif ($tone === 'white')
        <img src="{{ $src('white') }}" alt="Kuartal Impact" class="{{ $size }} w-auto" width="1283" height="713">
    @else
        <img src="{{ $src('black') }}" alt="Kuartal Impact" class="k-logo-light {{ $size }} w-auto" width="1283" height="713">
        <img src="{{ $src('lime') }}" alt="Kuartal Impact" class="k-logo-dark {{ $size }} w-auto" width="1283" height="713">
    @endif
</span>
