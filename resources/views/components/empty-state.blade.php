@props(['title', 'icon' => null])
<div {{ $attributes->merge(['class' => 'k-empty']) }} role="status">
    <h3>&gt; {{ $title }}</h3>
    @if (trim($slot) !== '')<p>{{ $slot }}</p>@endif
</div>
