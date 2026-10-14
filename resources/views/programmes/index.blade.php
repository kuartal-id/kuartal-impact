<x-layouts.app title="Programmes — Kuartal Impact" description="Kuartal Impact's running programmes, each run as a fund.">
    <x-page-hero eyebrow="Programmes" code="F" title="Our running funds">
        {{ $intro ?? 'Each programme runs as its own fund with one clear job. Figures are published per fund as they are verified.' }}
    </x-page-hero>
    <section class="k-section">
        <div class="k-container-wide">
            @if ($funds->isNotEmpty())
                <div class="grid gap-5 md:grid-cols-2">@foreach ($funds as $fund)<x-fund-card :fund="$fund" :index="$loop->iteration" />@endforeach</div>
            @else
                <x-empty-state title="No funds published yet" />
            @endif
        </div>
    </section>
</x-layouts.app>
