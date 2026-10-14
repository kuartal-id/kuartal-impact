<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\SiteEntry;
use App\Support\MediaStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin CRUD for SiteEntry (portfolio, programmes, team, partners, figures, text blocks). */
class EntryController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type');
        $entries = SiteEntry::with('media')
            ->when(array_key_exists((string) $type, config('kuartalimpact.entry_types')), fn ($q) => $q->where('type', $type))
            ->orderBy('type')->orderBy('group')->orderBy('sort')->orderByDesc('starts_at')->orderBy('id')
            ->paginate(50)->withQueryString();

        return view('admin.entries.index', ['entries' => $entries, 'type' => $type]);
    }

    public function create(Request $request): View
    {
        $type = array_key_exists((string) $request->query('type'), config('kuartalimpact.entry_types')) ? $request->query('type') : 'portfolio';

        return $this->form(new SiteEntry(['type' => $type, 'is_published' => true, 'sort' => 0, 'group' => $request->query('group')]));
    }

    public function store(Request $request): RedirectResponse
    {
        $entry = SiteEntry::create($this->validated($request));

        return redirect()->route('admin.entries.index', ['type' => $entry->type])->with('status', "Saved \"{$entry->title}\".");
    }

    public function edit(SiteEntry $entry): View
    {
        return $this->form($entry);
    }

    public function update(Request $request, SiteEntry $entry): RedirectResponse
    {
        $entry->update($this->validated($request));

        return redirect()->route('admin.entries.index', ['type' => $entry->type])->with('status', "Saved \"{$entry->title}\".");
    }

    public function destroy(SiteEntry $entry): RedirectResponse
    {
        $entry->delete();

        return redirect()->route('admin.entries.index', ['type' => $entry->type])->with('status', "Deleted \"{$entry->title}\".");
    }

    private function form(SiteEntry $entry): View
    {
        return view('admin.entries.form', [
            'entry' => $entry,
            'funds' => SiteEntry::where('type', 'fund')->orderBy('sort')->get(['id', 'slug', 'title']),
            'images' => Media::where('kind', 'image')->where('visibility', 'public')->latest()->limit(60)->get(),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(config('kuartalimpact.entry_types')))],
            'group' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'body' => ['nullable', 'string', 'max:20000'],
            'url' => ['nullable', 'url:https,http', 'max:500'],
            'location' => ['nullable', 'string', 'max:200'],
            'starts_at' => ['nullable', 'date'],
            'media_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where('kind', 'image')->where('visibility', 'public')],
            'image_upload' => MediaStore::rules(false, true),
            'sort' => ['nullable', 'integer', 'min:-1000', 'max:1000'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $type = $data['type'];
        if ($type === 'person') {
            $request->validate(['group' => ['required', Rule::in(array_keys(config('kuartalimpact.person_groups')))]]);
        } elseif ($type === 'block') {
            $request->validate([
                'group' => ['required', Rule::in(array_keys(config('kuartalimpact.blocks')))],
                'body' => ['required', 'string'],
            ]);
        } elseif ($type === 'fund') {
            $request->validate([
                'group' => ['nullable', Rule::in(array_keys(config('kuartalimpact.focus_areas')))],
                'slug' => ['required', 'alpha_dash', 'max:80', Rule::unique('site_entries', 'slug')->ignore($request->route('entry'))],
            ]);
            $data['slug'] = strtolower((string) $request->input('slug'));
        } elseif (in_array($type, ['portfolio', 'metric'], true)) {
            $request->validate(['group' => [$type === 'metric' ? 'required' : 'nullable', Rule::exists('site_entries', 'slug')->where('type', 'fund')]]);
        } else {
            $data['group'] = null;
        }

        if ($request->hasFile('image_upload')) {
            $data['media_id'] = MediaStore::store($request->file('image_upload'), 'public', $request->user()->id,
                $data['title'], $data['title'], 'image_upload')->id;
        }
        unset($data['image_upload']);

        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
