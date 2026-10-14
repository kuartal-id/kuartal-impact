@php
    $type = old('type', $entry->type);
    $help = [
        'fund' => ['title' => 'Fund name', 'subtitle' => 'One-line description', 'url' => 'External link (optional)', 'body' => 'Fund page text (plain text; blank lines start new paragraphs)'],
        'portfolio' => ['title' => 'Project or company name', 'subtitle' => 'One-line description', 'url' => 'Website', 'body' => 'Description (optional)'],
        'person' => ['title' => 'Full name', 'subtitle' => 'Title / role', 'url' => 'Profile link (optional)', 'body' => 'Short bio (optional)'],
        'partner' => ['title' => 'Partner name', 'subtitle' => 'Role in the work (optional)', 'url' => 'Website', 'body' => 'Description (optional)'],
        'metric' => ['title' => 'Figure label (e.g. "Hectares restored")', 'subtitle' => 'Value exactly as it should appear (e.g. "12 ha")', 'url' => 'Source link (optional)', 'body' => 'Note / source / as-of date (optional)'],
        'block' => ['title' => 'Internal label', 'subtitle' => 'Not shown', 'url' => 'Not used', 'body' => 'Text'],
][$type] ?? [];
@endphp
<x-layouts.portal title="{{ $entry->exists ? 'Edit' : 'Add' }} content — Kuartal Impact admin">
    <h1 class="font-display text-2xl font-semibold mb-6">{{ $entry->exists ? 'Edit' : 'Add' }} {{ strtolower(config('kuartalimpact.entry_types.'.$type)) }}</h1>
    <x-admin.card>
        <form method="GET" action="{{ route('admin.entries.create') }}" class="mb-4 flex gap-2 text-sm" @if($entry->exists) hidden @endif>
            <label class="k-label self-center !mb-0" for="t">Type</label>
            <select id="t" name="type" class="k-input w-auto" onchange="this.form.submit()">@foreach (config('kuartalimpact.entry_types') as $k => $v)<option value="{{ $k }}" @selected($type === $k)>{{ $v }}</option>@endforeach</select>
        </form>
        <form method="POST" enctype="multipart/form-data" action="{{ $entry->exists ? route('admin.entries.update', $entry) : route('admin.entries.store') }}" class="grid md:grid-cols-2 gap-4">
            @csrf @if ($entry->exists) @method('PUT') @endif
            <input type="hidden" name="type" value="{{ $type }}">
            @if ($type === 'person')
                <div><label class="k-label" for="group">Section</label><select id="group" class="k-input" name="group">@foreach (config('kuartalimpact.person_groups') as $k => $v)<option value="{{ $k }}" @selected(old('group', $entry->group) === $k)>{{ $v }}</option>@endforeach</select></div>
            @elseif ($type === 'fund')
                <div><label class="k-label" for="slug">Page address (/programmes/…)</label><input id="slug" class="k-input" name="slug" required maxlength="80" pattern="[A-Za-z0-9_-]+" value="{{ old('slug', $entry->slug) }}"></div>
                <div><label class="k-label" for="group">Focus area</label><select id="group" class="k-input" name="group"><option value="">None</option>@foreach (config('kuartalimpact.focus_areas') as $k => $v)<option value="{{ $k }}" @selected(old('group', $entry->group) === $k)>{{ $v['label'] }}</option>@endforeach</select></div>
            @elseif (in_array($type, ['portfolio', 'metric'], true))
                <div class="md:col-span-2"><label class="k-label" for="group">Fund</label><select id="group" class="k-input" name="group" @required($type === 'metric')>@if ($type === 'portfolio')<option value="">None</option>@endif @foreach ($funds as $f)<option value="{{ $f->slug }}" @selected(old('group', $entry->group) === $f->slug)>{{ $f->title }}</option>@endforeach</select></div>
            @elseif ($type === 'block')
                <div class="md:col-span-2"><label class="k-label" for="group">Where it appears</label><select id="group" class="k-input" name="group">@foreach (config('kuartalimpact.blocks') as $k => $v)<option value="{{ $k }}" @selected(old('group', $entry->group) === $k)>{{ $v }}</option>@endforeach</select></div>
            @endif
            <div class="md:col-span-2"><label class="k-label" for="title">{{ $help['title'] ?? 'Title' }}</label><input id="title" class="k-input" name="title" required maxlength="200" value="{{ old('title', $entry->title) }}"></div>
            @if ($type !== 'block')
                <div class="md:col-span-2"><label class="k-label" for="subtitle">{{ $help['subtitle'] }}</label><input id="subtitle" class="k-input" name="subtitle" maxlength="250" value="{{ old('subtitle', $entry->subtitle) }}"></div>
            @endif
            <div class="md:col-span-2"><label class="k-label" for="body">{{ $help['body'] ?? 'Description (optional)' }}</label><textarea id="body" class="k-input" name="body" rows="{{ in_array($type, ['block', 'fund'], true) ? 8 : 3 }}" maxlength="20000" @required($type === 'block')>{{ old('body', $entry->body) }}</textarea></div>
            @if (in_array($type, ['fund', 'portfolio'], true))
                <div class="md:col-span-2"><label class="k-label" for="location">Location (optional)</label><input id="location" class="k-input" name="location" maxlength="200" value="{{ old('location', $entry->location) }}"></div>
            @endif
            @if ($type !== 'block')
                <div class="md:col-span-2"><label class="k-label" for="url">{{ $help['url'] }}</label><input id="url" class="k-input" type="url" name="url" maxlength="500" value="{{ old('url', $entry->url) }}" placeholder="https://"></div>
            @endif
            @if (in_array($type, ['person', 'portfolio', 'partner', 'fund'], true))
                <div><label class="k-label" for="media_id">{{ $type === 'person' ? 'Photo' : ($type === 'fund' ? 'Cover image' : 'Logo') }} from the media library</label><select id="media_id" class="k-input" name="media_id"><option value="">None</option>@foreach ($images as $m)<option value="{{ $m->id }}" @selected((string) old('media_id', $entry->media_id) === (string) $m->id)>{{ $m->title }}</option>@endforeach</select></div>
                <div><label class="k-label" for="image_upload">…or upload</label><input id="image_upload" class="k-input" type="file" name="image_upload" accept=".jpg,.jpeg,.png,.webp,.gif"></div>
            @endif
            <div><label class="k-label" for="sort">Order (lower first)</label><input id="sort" class="k-input" type="number" name="sort" value="{{ old('sort', $entry->sort ?? 0) }}" min="-1000" max="1000"></div>
            <div class="flex items-end"><label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_published" value="0"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $entry->is_published))> Show on the public site</label></div>
            <div class="md:col-span-2 flex gap-3"><button class="k-btn-primary">Save</button><a href="{{ route('admin.entries.index', ['type' => $type]) }}" class="k-btn-ghost">Cancel</a></div>
        </form>
        @if ($entry->exists)
            <form method="POST" action="{{ route('admin.entries.destroy', $entry) }}" class="mt-6 border-t border-gray-border pt-4" onsubmit="return confirm('Delete this entry?')">@csrf @method('DELETE')<button class="text-sm text-red-700 underline">Delete</button></form>
        @endif
    </x-admin.card>
</x-layouts.portal>
