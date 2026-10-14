@props(['eyebrow' => null, 'title', 'index' => null, 'align' => 'left'])
<div {{ $attributes->merge(['class' => ($align === 'center' ? 'mx-auto text-center ' : '').'mb-10 max-w-2xl']) }}>
    @if ($eyebrow)<p class="k-eyebrow mb-3">{{ $eyebrow }}</p>@endif
    <h2 class="text-3xl md:text-4xl">{{ $title }}</h2>
    @if (trim($slot) !== '')<p class="mt-4 text-lg text-gray-muted dark:text-white/65">{{ $slot }}</p>@endif
</div>
