@php
    $fin = old('financials', $org->exists ? $org->financials->map(fn ($f) => implode(' | ', [$f->year, $f->currency, ...array_map(fn ($v) => $v === null ? '' : $v + 0, [$f->income, $f->expenditure, $f->programme_spend]), $f->source_url]))->implode("\n") : '');
    $docs = old('documents', $org->exists ? $org->documents->map(fn ($d) => implode(' | ', [$d->kind, $d->year, $d->title, $d->url]))->implode("\n") : '');
    $selSectors = old('sectors', $org->exists ? $org->sectors->pluck('id')->all() : []);
    $selRegions = old('regions', $org->exists ? $org->regions->pluck('id')->all() : []);
@endphp
<x-layouts.portal title="{{ $org->exists ? 'Edit' : 'New' }} organisation — Impact Index admin">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="font-display text-2xl font-semibold">{{ $org->exists ? $org->name : 'New organisation' }}</h1>
        @if ($org->exists)<div class="flex gap-3 text-sm"><a class="k-btn-outline" href="{{ route('admin.index.orgs.scores', $org) }}">Scores &amp; evidence</a>@if($org->status === 'published')<a class="underline" target="_blank" href="{{ route('impact-index.show', $org) }}">View live</a>@endif</div>@endif
    </div>
    <p class="mb-4 text-sm text-gray-muted">Enter only facts you can source. Leave unknown fields empty; never estimate.</p>
    <form method="POST" action="{{ $org->exists ? route('admin.index.orgs.update', $org) : route('admin.index.orgs.store') }}" class="grid gap-6 lg:grid-cols-[1fr_360px]">
        @csrf @if ($org->exists) @method('PUT') @endif
        <div>
            <x-admin.card title="Profile">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2"><label class="k-label" for="name">Name</label><input id="name" class="k-input !text-lg" name="name" required maxlength="200" value="{{ old('name', $org->name) }}"></div>
                    <div><label class="k-label" for="slug">Slug (empty = from name)</label><input id="slug" class="k-input" name="slug" value="{{ old('slug', $org->slug) }}"></div>
                    <div><label class="k-label" for="type">Type</label><select id="type" name="type" class="k-input">@foreach (\App\Models\Index\Organisation::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('type', $org->type) === $k)>{{ $v }}</option>@endforeach</select></div>
                    @foreach (['logo_url' => 'Logo URL (https)', 'website' => 'Website', 'donation_url' => 'Donation link', 'country' => 'Country', 'headquarters' => 'HQ (city)', 'founded_year' => 'Founded (year)', 'registration_number' => 'Registration / legal number'] as $k => $l)
                        <div><label class="k-label" for="{{ $k }}">{{ $l }}</label><input id="{{ $k }}" class="k-input" name="{{ $k }}" value="{{ old($k, $org->$k) }}"></div>
                    @endforeach
                    <div class="md:col-span-2"><label class="k-label" for="summary">What they do</label><textarea id="summary" class="k-input" name="summary" rows="4">{{ old('summary', $org->summary) }}</textarea></div>
                    <div class="md:col-span-2"><label class="k-label" for="theory_of_change">How they work (theory of change)</label><textarea id="theory_of_change" class="k-input" name="theory_of_change" rows="4">{{ old('theory_of_change', $org->theory_of_change) }}</textarea></div>
                    <div class="md:col-span-2"><label class="k-label" for="funding_sources">Funding sources</label><textarea id="funding_sources" class="k-input" name="funding_sources" rows="2">{{ old('funding_sources', $org->funding_sources) }}</textarea></div>
                </div>
            </x-admin.card>
            <x-admin.card title="How money is used (% of spending)">
                <div class="grid gap-4 md:grid-cols-4">
                    @foreach (['programme_pct' => 'Programmes %', 'admin_pct' => 'Admin %', 'fundraising_pct' => 'Fundraising %', 'spending_source_url' => 'Source URL'] as $k => $l)
                        <div><label class="k-label" for="{{ $k }}">{{ $l }}</label><input id="{{ $k }}" class="k-input" name="{{ $k }}" value="{{ old($k, $org->$k) }}"></div>
                    @endforeach
                </div>
            </x-admin.card>
            <x-admin.card title="Financials by year">
                <label class="k-label" for="financials">One line per year: year | currency | income | expenditure | programme spend | source URL</label>
                <textarea id="financials" class="k-input font-mono" name="financials" rows="4" placeholder="2025 | IDR | 1000000 | 900000 | 700000 | https://…">{{ $fin }}</textarea>
            </x-admin.card>
            <x-admin.card title="Audits & reports">
                <label class="k-label" for="documents">One per line: audit|report | year | title | URL</label>
                <textarea id="documents" class="k-input font-mono" name="documents" rows="4" placeholder="audit | 2025 | Audited financial statements | https://…">{{ $docs }}</textarea>
            </x-admin.card>
        </div>
        <div>
            <x-admin.card title="Publishing">
                <div class="grid gap-4">
                    <div><label class="k-label" for="status">Status</label><select id="status" name="status" class="k-input"><option value="draft" @selected(old('status', $org->status) === 'draft')>Draft (hidden)</option><option value="published" @selected(old('status', $org->status) === 'published')>Published</option></select></div>
                    <div><label class="k-label" for="last_reviewed_at">Last reviewed</label><input id="last_reviewed_at" type="date" class="k-input" name="last_reviewed_at" value="{{ old('last_reviewed_at', $org->last_reviewed_at?->toDateString()) }}"></div>
                    <div><label class="k-label" for="reviewer_notes">Reviewer notes (public)</label><textarea id="reviewer_notes" class="k-input" name="reviewer_notes" rows="4">{{ old('reviewer_notes', $org->reviewer_notes) }}</textarea></div>
                    <button class="k-btn-primary">Save</button>
                </div>
            </x-admin.card>
            <x-admin.card title="Sectors">
                <div class="grid gap-1 text-sm">@foreach ($sectors as $s)<label class="flex items-center gap-2"><input type="checkbox" name="sectors[]" value="{{ $s->id }}" @checked(in_array($s->id, $selSectors))> {{ $s->name }}</label>@endforeach</div>
            </x-admin.card>
            <x-admin.card title="Regions impacted">
                <label class="k-label" for="regions">Ctrl/Cmd-click to select several</label>
                <select id="regions" name="regions[]" multiple size="14" class="k-input">@foreach ($regions as $r)<option value="{{ $r->id }}" @selected(in_array($r->id, $selRegions))>{{ str_repeat('— ', $r->depth) }}{{ $r->name }}</option>@endforeach</select>
            </x-admin.card>
        </div>
    </form>
    @if ($org->exists)
        <form method="POST" action="{{ route('admin.index.orgs.destroy', $org) }}" onsubmit="return confirm('Delete this organisation and all its scores?')">@csrf @method('DELETE')<button class="text-sm text-red-700 underline">Delete organisation</button></form>
    @endif
</x-layouts.portal>
