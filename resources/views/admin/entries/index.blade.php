<x-layouts.portal title="Site content — Kuartal Impact admin">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-2">
        <h1 class="font-display text-2xl font-semibold">Site content</h1>
        <a href="{{ route('admin.entries.create', ['type' => $type ?: 'fund']) }}" class="k-btn-primary">Add {{ strtolower(config('kuartalimpact.entry_types.'.($type ?: 'fund'))) }}</a>
    </div>
    <p class="text-sm text-gray-muted mb-6">Funds, fund figures, projects, team, partners and text blocks shown on the public pages. Sections stay as tidy empty states until filled.</p>
    <div class="flex flex-wrap gap-2 mb-4 text-sm">
        <a href="{{ route('admin.entries.index') }}" class="k-pill {{ ! $type ? 'bg-black text-white' : 'bg-white border border-gray-border' }}">All</a>
        @foreach (config('kuartalimpact.entry_types') as $k => $v)
            <a href="{{ route('admin.entries.index', ['type' => $k]) }}" class="k-pill {{ $type === $k ? 'bg-black text-white' : 'bg-white border border-gray-border' }}">{{ $v }}</a>
        @endforeach
    </div>
    <x-admin.card>
        @if ($entries->isEmpty())
            <x-admin.empty>Nothing here yet.</x-admin.empty>
        @else
            <div class="overflow-x-auto"><table class="k-table">
                <tr><th>Type</th><th>Title</th><th>Details</th><th>Order</th><th>Shown</th><th></th></tr>
                @foreach ($entries as $e)
                    <tr>
                        <td>{{ config('kuartalimpact.entry_types.'.$e->type) }}@if($e->group)<span class="block text-xs text-gray-muted">{{ config('kuartalimpact.person_groups.'.$e->group) ?? config('kuartalimpact.blocks.'.$e->group) ?? config('kuartalimpact.focus_areas.'.$e->group.'.label') ?? $e->group }}</span>@endif</td>
                        <td class="font-medium">{{ $e->title }}</td>
                        <td class="text-xs text-gray-muted">{{ collect([$e->starts_at?->format('j M Y H:i'), $e->subtitle, $e->location])->filter()->implode(' · ') }}</td>
                        <td>{{ $e->sort }}</td>
                        <td>{{ $e->is_published ? 'Yes' : 'Hidden' }}</td>
                        <td><a class="underline" href="{{ route('admin.entries.edit', $e) }}">Edit</a></td>
                    </tr>
                @endforeach
            </table></div>
            <div class="mt-4">{{ $entries->links() }}</div>
        @endif
    </x-admin.card>
</x-layouts.portal>
