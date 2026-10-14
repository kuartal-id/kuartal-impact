@props(['title' => null])
<section {{ $attributes->merge(['class' => 'bg-white rounded-card border border-gray-border p-5 mb-6']) }}>
    @if ($title)<h2 class="font-display text-lg font-semibold mb-4">{{ $title }}</h2>@endif
    {{ $slot }}
</section>
