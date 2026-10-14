<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Post;
use App\Models\SiteEntry;
use App\Support\MediaStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MediaLibraryController extends Controller
{
    public function index(Request $request): View
    {
        $media = Media::query()
            ->when($request->query('kind'), fn ($q, $k) => $q->where('kind', $k))
            ->when($request->query('visibility'), fn ($q, $v) => $q->where('visibility', $v))
            ->latest()->paginate(36)->withQueryString();

        return view('admin.media.index', ['media' => $media]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => MediaStore::rules(),
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'alt_text' => ['nullable', 'string', 'max:250'],
        ]);

        $n = 0;
        foreach ($request->file('files') as $i => $file) {
            MediaStore::store($file, $request->input('visibility'), $request->user()->id, null, $request->input('alt_text'), "files.$i");
            $n++;
        }

        return redirect()->route('admin.media.index')->with('status', "Uploaded {$n} file(s).");
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'alt_text' => ['nullable', 'string', 'max:250'],
            'visibility' => ['required', Rule::in(['public', 'private'])],
        ]);

        if ($data['visibility'] === 'private' && $this->inUse($media)) {
            return back()->withErrors(['visibility' => 'This file is used on the public site (post cover or site content); remove it there first.']);
        }

        $media->update($data);

        return redirect()->route('admin.media.index')->with('status', "Saved \"{$media->title}\".");
    }

    public function destroy(Media $media): RedirectResponse
    {
        MediaStore::delete($media);

        return redirect()->route('admin.media.index')->with('status', "Deleted \"{$media->title}\".");
    }

    private function inUse(Media $media): bool
    {
        return Post::where('cover_media_id', $media->id)->exists() || SiteEntry::where('media_id', $media->id)->exists();
    }
}
