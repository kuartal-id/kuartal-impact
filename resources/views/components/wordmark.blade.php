@props(['tone' => 'auto', 'size' => 'h-10'])
{{-- Kuartal Impact logo (public/images/logo, the owner's artwork, trimmed). Per theme:
     dark -> lime, neon and neutral -> black. tone="light"/"dark" pins one version; tone="white" for dark photo grounds. --}}
<span {{ $attributes->merge(['class' => 'k-wordmark inline-flex items-center']) }}>
    @if (in_array($tone, ['light', 'black'], true))
        <img src="{{ asset('images/logo/kuartal-impact-black.webp') }}" alt="Kuartal Impact" class="{{ $size }} w-auto" width="1283" height="713">
    @elseif ($tone === 'dark')
        <img src="{{ asset('images/logo/kuartal-impact-lime.webp') }}" alt="Kuartal Impact" class="{{ $size }} w-auto" width="1283" height="713">
    @elseif ($tone === 'white')
        <img src="{{ asset('images/logo/kuartal-impact-white.webp') }}" alt="Kuartal Impact" class="{{ $size }} w-auto" width="1283" height="713">
    @else
        <img src="{{ asset('images/logo/kuartal-impact-black.webp') }}" alt="Kuartal Impact" class="k-logo-black {{ $size }} w-auto" width="1283" height="713">
        <img src="{{ asset('images/logo/kuartal-impact-lime.webp') }}" alt="Kuartal Impact" class="k-logo-lime {{ $size }} w-auto" width="1283" height="713">
    @endif
</span>
