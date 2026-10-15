<x-layouts.portal title="Campaigns — Impact Index admin">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="font-display text-2xl font-semibold">Campaigns</h1>
        <div class="flex gap-2"><a href="{{ route('impact-index.campaigns') }}" target="_blank" class="k-btn-ghost">View public</a><a href="{{ route('admin.index.campaigns.create') }}" class="k-btn-primary">New campaign</a></div>
    </div>
    <x-admin.card>
        @if ($campaigns->isEmpty())<x-admin.empty>No campaigns.</x-admin.empty>@else
        <table class="k-table"><tr><th>Title</th><th>Status</th><th>Dates</th><th>Organisations</th><th></th></tr>
            @foreach ($campaigns as $c)<tr><td class="font-medium">{{ $c->title }}<span class="block text-xs text-gray-muted">/impact-index/campaigns/{{ $c->slug }}</span></td>
                <td>{{ $c->isActive() ? 'Active (on home + nav)' : ($c->status === 'retired' ? 'Retired' : 'Not running (dates)') }}</td>
                <td class="text-xs">{{ $c->starts_at?->toDateString() ?? '—' }} → {{ $c->ends_at?->toDateString() ?? 'open' }}</td><td>{{ $c->organisations_count }}</td>
                <td class="whitespace-nowrap"><a class="underline" href="{{ route('admin.index.campaigns.edit', $c) }}">Edit</a> · <a class="underline" target="_blank" href="{{ route('impact-index.campaign', $c) }}">View</a></td></tr>@endforeach
        </table>@endif
    </x-admin.card>
</x-layouts.portal>
