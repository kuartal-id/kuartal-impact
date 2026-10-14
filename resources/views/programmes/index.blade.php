<x-layouts.app title="Programmes — Kuartal Impact" description="Kuartal Impact's running programmes, each run as a fund.">
    <x-page-hero eyebrow="Programmes" title="Our running funds">
        {{ $intro ?? 'Each programme runs as its own fund with one clear job. Figures are published per fund as they are verified.' }}
    </x-page-hero>
    <section class="k-section">
        <div class="k-container">
            @if ($funds->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">@foreach ($funds as $fund)<x-fund-card :fund="$fund" :index="$loop->iteration" />@endforeach</div>
            @else
                <x-empty-state title="No funds published yet" />
            @endif
        </div>
    </section>
</x-layouts.app>
