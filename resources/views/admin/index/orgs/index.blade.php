<x-layouts.portal title="Impact Index — Kuartal Impact admin">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="font-display text-2xl font-semibold">Impact Index organisations</h1>
        <div class="flex gap-2"><a href="{{ route('impact-index') }}" target="_blank" class="k-btn-ghost">View Index</a><a href="{{ route('admin.index.settings') }}" class="k-btn-ghost">Weights, sectors, regions</a><a href="{{ route('admin.index.orgs.create') }}" class="k-btn-primary">New organisation</a></div>
    </div>
    <form class="flex flex-wrap gap-3 mb-4 text-sm" method="GET">
        <input class="k-input w-56" name="q" value="{{ request('q') }}" placeholder="Search names" aria-label="Search">
        <select name="status" class="k-input w-auto" aria-label="Status"><option value="">All</option><option value="draft" @selected(request('status') === 'draft')>Draft</option><option value="published" @selected(request('status') === 'published')>Published</option></select>
        <button class="k-btn-outline">Filter</button>
    </form>
    <x-admin.card>
        @if ($orgs->isEmpty())
            <x-admin.empty>No organisations yet. Only add organisations you have reviewed with real evidence. <a class="underline" href="{{ route('admin.index.orgs.create') }}">Add one</a>.</x-admin.empty>
        @else
            <div class="overflow-x-auto"><table class="k-table">
                <tr><th>Name</th><th>Type</th><th>Status</th><th>Score</th><th>Confidence</th><th>Reviewed</th><th></th></tr>
                @foreach ($orgs as $o)
                    <tr><td class="font-medium">{{ $o->name }}</td><td>{{ $o->typeLabel() }}</td><td>{{ ucfirst($o->status) }}</td>
                        <td>{{ $o->overall_score === null ? 'Not yet rated' : $o->overall_score.' ('.$o->overall_grade.')' }}</td><td class="capitalize">{{ $o->confidence ?? '—' }}</td>
                        <td>{{ $o->last_reviewed_at?->toDateString() ?? '—' }}</td>
                        <td class="whitespace-nowrap"><a class="underline" href="{{ route('admin.index.orgs.edit', $o) }}">Edit</a> · <a class="underline" href="{{ route('admin.index.orgs.scores', $o) }}">Scores</a>@if($o->status === 'published') · <a class="underline" target="_blank" href="{{ route('impact-index.show', $o) }}">View</a>@endif</td></tr>
                @endforeach
            </table></div>
            <div class="mt-4">{{ $orgs->links() }}</div>
        @endif
    </x-admin.card>
</x-layouts.portal>
