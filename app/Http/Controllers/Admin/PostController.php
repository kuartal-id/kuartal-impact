<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Post;
use App\Support\HtmlSanitizer;
use App\Support\MediaStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $posts = Post::with('cover')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('category'), fn ($q, $c) => $q->where('category', $c))
            ->when($request->query('q'), fn ($q, $t) => $q->where('title', 'like', '%'.$t.'%'))
            ->orderByRaw('published_at is null desc')->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('admin.posts.index', ['posts' => $posts]);
    }

    public function create(): View
    {
        return $this->form(new Post(['status' => 'draft', 'category' => 'announcement']));
    }

    public function store(Request $request): RedirectResponse
    {
        $post = Post::create($this->validated($request, null) + ['author_id' => $request->user()->id]);

        return redirect()->route('admin.posts.edit', $post)->with('status', "Saved \"{$post->title}\" ({$post->state()}).");
    }

    public function edit(Post $post): View
    {
        return $this->form($post);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $post->update($this->validated($request, $post));

        return redirect()->route('admin.posts.edit', $post)->with('status', "Saved \"{$post->title}\" ({$post->state()}).");
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', "Deleted \"{$post->title}\".");
    }

    private function form(Post $post): View
    {
        return view('admin.posts.form', [
            'post' => $post,
            'images' => Media::where('kind', 'image')->where('visibility', 'public')->latest()->limit(60)->get(),
        ]);
    }

    private function validated(Request $request, ?Post $post): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('posts', 'slug')->ignore($post?->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:200000'],
            'category' => ['required', Rule::in(array_keys(config('kuartalimpact.post_categories')))],
            'tags' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'cover_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where('kind', 'image')->where('visibility', 'public')],
            'cover_upload' => MediaStore::rules(false, true),
            'cover_alt' => ['nullable', 'string', 'max:250'],
        ]);

        $publishedAt = filled($data['published_at'] ?? null) ? Carbon::parse($data['published_at']) : null;
        if ($data['status'] === 'scheduled') {
            if (! $publishedAt || $publishedAt->isPast()) {
                throw ValidationException::withMessages(['published_at' => 'A scheduled post needs a publish date in the future.']);
            }
        } elseif ($data['status'] === 'published') {
            $publishedAt ??= ($post?->published_at ?? now());
        }

        if ($request->hasFile('cover_upload')) {
            $data['cover_media_id'] = MediaStore::store($request->file('cover_upload'), 'public', $request->user()->id,
                $data['title'], $data['cover_alt'] ?? $data['title'], 'cover_upload')->id;
        }

        $tags = collect(explode(',', (string) ($data['tags'] ?? '')))
            ->map(fn ($t) => Str::limit(trim(Str::lower($t)), 40, ''))->filter()->unique()->take(12)->values()->all();

        return [
            'title' => $data['title'],
            'slug' => filled($data['slug'] ?? null) ? $data['slug'] : ($post?->slug ?? Post::uniqueSlug($data['title'])),
            'excerpt' => $data['excerpt'] ?? null,
            'body' => HtmlSanitizer::clean($data['body'] ?? ''),
            'category' => $data['category'],
            'tags' => $tags,
            'status' => $data['status'],
            'published_at' => $publishedAt,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'cover_media_id' => $data['cover_media_id'] ?? null,
        ];
    }
}
