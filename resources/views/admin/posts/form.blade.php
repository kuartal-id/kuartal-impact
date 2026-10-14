<x-layouts.portal title="{{ $post->exists ? 'Edit' : 'New' }} post — Kuartal Impact admin">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="font-display text-2xl font-semibold">{{ $post->exists ? 'Edit post' : 'New post' }}</h1>
        @if ($post->exists)<div class="flex items-center gap-3"><x-admin.state :post="$post" />@if($post->isLive())<a class="underline text-sm" target="_blank" href="{{ route('newsroom.show', $post) }}">View live</a>@endif</div>@endif
    </div>
    <form method="POST" enctype="multipart/form-data" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}" class="grid gap-6 lg:grid-cols-[1fr_340px]">
        @csrf @if ($post->exists) @method('PUT') @endif
        <div>
            <x-admin.card>
                <div class="grid gap-4">
                    <div><label class="k-label" for="title">Title</label><input id="title" class="k-input !text-lg" name="title" required maxlength="200" value="{{ old('title', $post->title) }}" data-slug-source></div>
                    <div><label class="k-label" for="slug">Slug (URL: /newsroom/…; empty = from title)</label><input id="slug" class="k-input" name="slug" maxlength="160" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{{ old('slug', $post->slug) }}" data-slug-target></div>
                    <div><label class="k-label" for="excerpt">Excerpt (shown on cards and in RSS)</label><textarea id="excerpt" class="k-input" name="excerpt" rows="2" maxlength="500">{{ old('excerpt', $post->excerpt) }}</textarea></div>
                    <div data-rich-editor>
                        <label class="k-label" for="body">Body</label>
                        <div class="k-editor-bar" data-editor-bar hidden role="toolbar" aria-label="Formatting">
                            <button type="button" data-cmd="formatBlock" data-value="p">¶</button>
                            <button type="button" data-cmd="formatBlock" data-value="h2">H2</button>
                            <button type="button" data-cmd="formatBlock" data-value="h3">H3</button>
                            <button type="button" data-cmd="bold"><b>B</b></button>
                            <button type="button" data-cmd="italic"><i>I</i></button>
                            <button type="button" data-cmd="insertUnorderedList">• List</button>
                            <button type="button" data-cmd="insertOrderedList">1. List</button>
                            <button type="button" data-cmd="formatBlock" data-value="blockquote">Quote</button>
                            <button type="button" data-cmd="link">Link</button>
                            <button type="button" data-cmd="unlink">Unlink</button>
                            <button type="button" data-cmd="image">Image</button>
                            <button type="button" data-cmd="removeFormat">Clear</button>
                            <button type="button" data-cmd="source" class="ml-auto">HTML</button>
                        </div>
                        <div class="k-editor k-prose" contenteditable="true" data-editor-area hidden aria-label="Post body"></div>
                        <textarea id="body" class="k-input font-mono" name="body" rows="16">{{ old('body', $post->body) }}</textarea>
                        <p class="mt-1 text-xs text-gray-muted">Scripts, styles and embeds are stripped on save. Images: copy a URL from the <a class="underline" href="{{ route('admin.media.index') }}" target="_blank">media library</a>.</p>
                    </div>
                </div>
            </x-admin.card>
            <x-admin.card title="SEO">
                <div class="grid gap-4">
                    <div><label class="k-label" for="meta_title">Meta title (empty = post title)</label><input id="meta_title" class="k-input" name="meta_title" maxlength="160" value="{{ old('meta_title', $post->meta_title) }}"></div>
                    <div><label class="k-label" for="meta_description">Meta description (empty = excerpt)</label><textarea id="meta_description" class="k-input" name="meta_description" rows="2" maxlength="320">{{ old('meta_description', $post->meta_description) }}</textarea></div>
                </div>
            </x-admin.card>
        </div>
        <div>
            <x-admin.card title="Publishing">
                <div class="grid gap-4">
                    <div><label class="k-label" for="status">Status</label>
                        <select id="status" class="k-input" name="status">@foreach (['draft' => 'Draft (hidden)', 'scheduled' => 'Scheduled (goes live at publish date)', 'published' => 'Published'] as $k => $v)<option value="{{ $k }}" @selected(old('status', $post->status) === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div><label class="k-label" for="published_at">Publish date ({{ config('app.timezone') }}; empty = now when published)</label><input id="published_at" class="k-input" type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}"></div>
                    <div><label class="k-label" for="category">Category</label><select id="category" class="k-input" name="category">@foreach (config('kuartalimpact.post_categories') as $k => $v)<option value="{{ $k }}" @selected(old('category', $post->category) === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div><label class="k-label" for="tags">Tags (comma-separated)</label><input id="tags" class="k-input" name="tags" maxlength="500" value="{{ old('tags', implode(', ', $post->tags ?? [])) }}"></div>
                    <button class="k-btn-primary w-full">Save</button>
                </div>
            </x-admin.card>
            <x-admin.card title="Cover image">
                @if ($post->cover)<img src="{{ $post->cover->url() }}" alt="" class="mb-3 w-full rounded-lg">@endif
                <label class="k-label" for="cover_media_id">From the media library</label>
                <select id="cover_media_id" class="k-input mb-3" name="cover_media_id"><option value="">None</option>@foreach ($images as $m)<option value="{{ $m->id }}" @selected((string) old('cover_media_id', $post->cover_media_id) === (string) $m->id)>{{ $m->title }} ({{ $m->width }}×{{ $m->height }})</option>@endforeach</select>
                <label class="k-label" for="cover_upload">…or upload a new one (JPG/PNG/WebP/GIF, max {{ intdiv(config('kuartalimpact.media.max_image_kb'), 1024) }} MB)</label>
                <input id="cover_upload" class="k-input mb-3" type="file" name="cover_upload" accept=".jpg,.jpeg,.png,.webp,.gif">
                <label class="k-label" for="cover_alt">Alt text for an uploaded cover</label>
                <input id="cover_alt" class="k-input" name="cover_alt" maxlength="250" value="{{ old('cover_alt') }}">
            </x-admin.card>
        </div>
    </form>
    @if ($post->exists)
        <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" onsubmit="return confirm('Delete this post?')">@csrf @method('DELETE')<button class="text-sm text-red-700 underline">Delete post</button></form>
    @endif
</x-layouts.portal>
