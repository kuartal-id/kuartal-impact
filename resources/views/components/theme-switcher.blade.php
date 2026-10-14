@props(['labels' => true, 'id' => 'theme'])
{{-- Neon / Dark / Neutral. Buttons are wired by resources/js/theme-toggle.js; choice persists in localStorage. --}}
<div {{ $attributes->merge(['class' => 'k-theme-switch']) }} role="group" aria-label="Colour theme" data-theme-switcher>
    @foreach (['neon' => 'Neon', 'dark' => 'Dark', 'neutral' => 'Neutral'] as $key => $label)
        <button type="button" data-theme-set="{{ $key }}" aria-pressed="{{ $key === 'dark' ? 'true' : 'false' }}" title="{{ $label }} theme">
            <span class="k-swatch k-swatch-{{ $key }}" aria-hidden="true"></span>
            <span class="{{ $labels ? '' : 'sr-only' }}">{{ $label }}</span>
        </button>
    @endforeach
</div>
