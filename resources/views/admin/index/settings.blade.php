<x-layouts.portal title="Index settings — Kuartal Impact admin">
    <h1 class="font-display text-2xl font-semibold mb-6">Impact Index settings</h1>
    <x-admin.card title="Pillar weights & methodology version">
        <form method="POST" action="{{ route('admin.index.weights') }}" class="grid gap-4">
            @csrf @method('PUT')
            <p class="text-sm text-gray-muted">Weights must add up to 100. Saving recalculates every organisation. Weights and descriptions are shown on the public methodology page; bump the version when you change them.</p>
            <div><label class="k-label" for="version">Methodology version</label><input id="version" class="k-input w-32" name="version" value="{{ old('version', $version) }}"></div>
            @foreach ($pillars as $p)
                <div class="grid gap-3 md:grid-cols-[220px_100px_1fr] items-start">
                    <span class="font-medium pt-2">{{ $p->name }}</span>
                    <input class="k-input" type="number" min="0" max="100" name="weights[{{ $p->id }}]" value="{{ old('weights.'.$p->id, $p->weight) }}" aria-label="{{ $p->name }} weight">
                    <textarea class="k-input" rows="2" name="descriptions[{{ $p->id }}]" aria-label="{{ $p->name }} description">{{ old('descriptions.'.$p->id, $p->description) }}</textarea>
                </div>
            @endforeach
            <div><button class="k-btn-primary">Save weights</button></div>
        </form>
    </x-admin.card>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-admin.card title="Sectors">
            <form method="POST" action="{{ route('admin.index.sectors.store') }}" class="flex gap-2 mb-4">@csrf<input class="k-input" name="name" placeholder="New sector" required aria-label="New sector"><button class="k-btn-outline">Add</button></form>
            <table class="k-table">@foreach ($sectors as $s)<tr>
                <td><form method="POST" action="{{ route('admin.index.sectors.update', $s) }}" class="flex gap-2">@csrf @method('PUT')<input class="k-input" name="name" value="{{ $s->name }}" aria-label="Sector name"><input class="k-input w-20" name="sort" value="{{ $s->sort }}" aria-label="Order"><button class="underline text-sm">Save</button></form></td>
                <td><form method="POST" action="{{ route('admin.index.sectors.destroy', $s) }}" onsubmit="return confirm('Delete sector?')">@csrf @method('DELETE')<button class="text-sm text-red-700 underline">Delete</button></form></td></tr>@endforeach</table>
        </x-admin.card>
        <x-admin.card title="Regions (country › province › area)">
            <form method="POST" action="{{ route('admin.index.regions.store') }}" class="grid gap-2 md:grid-cols-[1fr_120px_1fr_auto] mb-4">@csrf
                <input class="k-input" name="name" placeholder="New region" required aria-label="Region name">
                <select class="k-input" name="level" aria-label="Level">@foreach (\App\Models\Index\Region::LEVELS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                <select class="k-input" name="parent_id" aria-label="Parent"><option value="">— no parent (country) —</option>@foreach ($regions as $r)<option value="{{ $r->id }}">{{ str_repeat('— ', $r->depth) }}{{ $r->name }}</option>@endforeach</select>
                <button class="k-btn-outline">Add</button></form>
            <div class="max-h-[520px] overflow-y-auto"><table class="k-table">@foreach ($regions as $r)<tr>
                <td style="padding-left: {{ 0.5 + $r->depth * 1.25 }}rem"><form method="POST" action="{{ route('admin.index.regions.update', $r) }}" class="flex gap-2">@csrf @method('PUT')<input class="k-input" name="name" value="{{ $r->name }}" aria-label="Region name"><input type="hidden" name="level" value="{{ $r->level }}"><input type="hidden" name="parent_id" value="{{ $r->parent_id }}"><span class="text-xs text-gray-muted pt-2">{{ $r->level }}</span><button class="underline text-sm">Save</button></form></td>
                <td><form method="POST" action="{{ route('admin.index.regions.destroy', $r) }}" onsubmit="return confirm('Delete region and its sub-regions?')">@csrf @method('DELETE')<button class="text-sm text-red-700 underline">Delete</button></form></td></tr>@endforeach</table></div>
        </x-admin.card>
    </div>
</x-layouts.portal>
