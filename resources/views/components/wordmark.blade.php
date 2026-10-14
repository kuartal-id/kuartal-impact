@props(['tone' => 'auto', 'size' => 'h-7'])
{{-- Kuartal Impact wordmark (public/images/logo): lime artwork on dark, black on light. --}}
<span {{ $attributes->merge(['class' => 'k-wordmark inline-flex items-center']) }}>
    @if ($tone === 'dark')
        <img src="{{ asset('images/logo/kuartal-impact-lime.webp') }}" alt="Kuartal Impact" class="{{ $size }} w-auto" width="900" height="158">
    @elseif ($tone === 'light')
        <img src="{{ asset('images/logo/kuartal-impact-black.webp') }}" alt="Kuartal Impact" class="{{ $size }} w-auto" width="900" height="158">
    @else
        <img src="{{ asset('images/logo/kuartal-impact-black.webp') }}" alt="Kuartal Impact" class="{{ $size }} w-auto dark:hidden" width="900" height="158">
        <img src="{{ asset('images/logo/kuartal-impact-lime.webp') }}" alt="Kuartal Impact" class="{{ $size }} w-auto hidden dark:block" width="900" height="158">
    @endif
</span>
