@php $sel = old('organisations', $campaign->exists ? $campaign->organisations->pluck('id')->all() : []); @endphp
<x-layouts.portal title="{{ $campaign->exists ? 'Edit' : 'New' }} campaign — Impact Index admin">
    <h1 class="font-display text-2xl font-semibold mb-6">{{ $campaign->exists ? $campaign->title : 'New campaign' }}</h1>
    <form method="POST" action="{{ $campaign->exists ? route('admin.index.campaigns.update', $campaign) : route('admin.index.campaigns.store') }}" class="grid gap-6 lg:grid-cols-[1fr_360px]">
        @csrf @if ($campaign->exists) @method('PUT') @endif
        <div><x-admin.card>
            <div class="grid gap-4">
                <div><label class="k-label" for="title">Title</label><input id="title" class="k-input !text-lg" name="title" required value="{{ old('title', $campaign->title) }}"></div>
                <div><label class="k-label" for="slug">Slug (URL stays when retired)</label><input id="slug" class="k-input" name="slug" value="{{ old('slug', $campaign->slug) }}"></div>
                <div><label class="k-label" for="intro">Intro (neutral, factual; no casualty figures)</label><textarea id="intro" class="k-input" name="intro" rows="5">{{ old('intro', $campaign->intro) }}</textarea></div>
                <div><label class="k-label" for="give_safely">How to give safely</label><textarea id="give_safely" class="k-input" name="give_safely" rows="4">{{ old('give_safely', $campaign->give_safely) }}</textarea></div>
            </div>
        </x-admin.card></div>
        <div>
            <x-admin.card title="Status">
                <div class="grid gap-4">
                    <div><label class="k-label" for="status">Status</label><select id="status" class="k-input" name="status"><option value="active" @selected(old('status', $campaign->status) === 'active')>Active</option><option value="retired" @selected(old('status', $campaign->status) === 'retired')>Retired (hidden from nav/home; URL shows "ended")</option></select></div>
                    <div><label class="k-label" for="starts_at">Starts</label><input id="starts_at" type="date" class="k-input" name="starts_at" value="{{ old('starts_at', $campaign->starts_at?->toDateString()) }}"></div>
                    <div><label class="k-label" for="ends_at">Ends (optional)</label><input id="ends_at" type="date" class="k-input" name="ends_at" value="{{ old('ends_at', $campaign->ends_at?->toDateString()) }}"></div>
                    <div><label class="k-label" for="sort">Order</label><input id="sort" class="k-input" name="sort" value="{{ old('sort', $campaign->sort) }}"></div>
                    <button class="k-btn-primary">Save</button>
                </div>
            </x-admin.card>
            <x-admin.card title="Linked organisations">
                @if ($orgs->isEmpty())<p class="text-sm text-gray-muted">No organisations in the Index yet.</p>@else
                <div class="grid max-h-80 gap-1 overflow-y-auto text-sm">@foreach ($orgs as $o)<label class="flex items-center gap-2"><input type="checkbox" name="organisations[]" value="{{ $o->id }}" @checked(in_array($o->id, $sel))> {{ $o->name }} <span class="text-xs text-gray-muted">{{ $o->status }}{{ $o->overall_grade ? ' · '.$o->overall_grade : '' }}</span></label>@endforeach</div>@endif
            </x-admin.card>
        </div>
    </form>
    @if ($campaign->exists)<form method="POST" action="{{ route('admin.index.campaigns.destroy', $campaign) }}" onsubmit="return confirm('Delete campaign? Prefer retiring it.')">@csrf @method('DELETE')<button class="text-sm text-red-700 underline">Delete campaign</button></form>@endif
</x-layouts.portal>
