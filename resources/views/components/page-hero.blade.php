@props(['eyebrow', 'title', 'code' => null, 'icon' => null])
{{-- Page opener in kuartal.id's style: centred title and lead on the soft (cream) band. --}}
<section class="k-page-hero">
    <div class="k-container py-16 text-center md:py-20">
        <p class="k-eyebrow mb-4">{{ $eyebrow }}</p>
        <h1 class="mx-auto max-w-4xl text-4xl md:text-5xl leading-[1.1]">{{ $title }}</h1>
        @if (trim($slot) !== '')<div class="mx-auto mt-5 max-w-2xl text-lg text-gray-muted dark:text-white/65">{{ $slot }}</div>@endif
    </div>
</section>
