@props(['eyebrow' => null, 'title', 'index' => null])
<div {{ $attributes->merge(['class' => 'mb-10 flex flex-col gap-3 md:mb-14']) }}>
    @if ($eyebrow)<p class="k-eyebrow">{{ $index ? $index.' / ' : '' }}{{ $eyebrow }}</p>@endif
    <h2 class="max-w-3xl text-3xl leading-tight md:text-5xl">{{ $title }}</h2>
    @if (trim($slot) !== '')<div class="k-lead max-w-2xl">{{ $slot }}</div>@endif
</div>
