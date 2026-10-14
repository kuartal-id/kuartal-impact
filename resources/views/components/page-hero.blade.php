@props(['eyebrow', 'title', 'code' => null])
<section class="k-page-hero k-grid-bg k-scan">
    <div class="k-container relative py-16 md:py-24">
        <p class="k-eyebrow mb-5">// {{ $code ? $code.' · ' : '' }}{{ $eyebrow }}</p>
        <h1 class="max-w-4xl text-4xl leading-[1.05] md:text-6xl">{{ $title }}</h1>
        @if (trim($slot) !== '')<div class="k-lead mt-6 max-w-2xl">{{ $slot }}</div>@endif
    </div>
</section>
