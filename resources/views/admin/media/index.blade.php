<x-layouts.portal title="Media library — Kuartal Impact admin">
    <h1 class="font-display text-2xl font-semibold mb-6">Media library</h1>
    <x-admin.card title="Upload">
        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.media.store') }}" class="grid gap-4 md:grid-cols-[2fr_1fr_1fr_auto] md:items-end">
            @csrf
            <div><label class="k-label" for="files">Files — images (JPG, PNG, WebP, GIF; max {{ intdiv(config('kuartalimpact.media.max_image_kb'), 1024) }} MB) or PDFs (max {{ intdiv(config('kuartalimpact.media.max_pdf_kb'), 1024) }} MB); up to 10</label>
                <input id="files" class="k-input" type="file" name="files[]" multiple required accept=".jpg,.jpeg,.png,.webp,.gif,.pdf"></div>
            <div><label class="k-label" for="visibility">Visibility</label>
                <select id="visibility" class="k-input" name="visibility"><option value="public">Public (anyone with the link)</option><option value="private">Private (admins only)</option></select></div>
            <div><label class="k-label" for="alt_text">Alt text (images)</label><input id="alt_text" class="k-input" name="alt_text" maxlength="250"></div>
            <button class="k-btn-primary">Upload</button>
        </form>
        <p class="mt-3 text-xs text-gray-muted">Files are stored outside the web root and served by the app. SVG and other active formats are refused.</p>
    </x-admin.card>

    <form class="flex flex-wrap gap-3 mb-4 text-sm" method="GET">
        <select name="kind" class="k-input w-auto" aria-label="Type"><option value="">All types</option><option value="image" @selected(request('kind') === 'image')>Images</option><option value="pdf" @selected(request('kind') === 'pdf')>PDFs</option></select>
        <select name="visibility" class="k-input w-auto" aria-label="Visibility"><option value="">Public &amp; private</option><option value="public" @selected(request('visibility') === 'public')>Public</option><option value="private" @selected(request('visibility') === 'private')>Private</option></select>
        <button class="k-btn-outline">Filter</button>
    </form>

    @if ($media->isEmpty())
        <x-admin.card><x-admin.empty>No files yet.</x-admin.empty></x-admin.card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($media as $m)
                <div class="bg-white rounded-card border border-gray-border overflow-hidden flex flex-col">
                    <a href="{{ route('admin.media.file', $m) }}" target="_blank" class="block aspect-[4/3] bg-gray-bg">
                        @if ($m->isImage())<img src="{{ route('admin.media.file', $m) }}" alt="{{ $m->alt_text ?? '' }}" class="h-full w-full object-cover" loading="lazy">
                        @else<span class="flex h-full items-center justify-center text-gray-muted"><x-group-icon icon="document" class="h-12 w-12" /></span>@endif
                    </a>
                    <div class="p-4 text-sm flex-1 flex flex-col gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="k-pill {{ $m->isPublic() ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">{{ ucfirst($m->visibility) }}</span>
                            <span class="text-xs text-gray-muted">{{ strtoupper($m->kind) }} · {{ $m->humanSize() }}@if($m->width) · {{ $m->width }}×{{ $m->height }}@endif</span>
                        </div>
                        @if ($m->isPublic())<button type="button" class="text-left text-xs underline" data-copy="{{ $m->url() }}">Copy public URL</button>@endif
                        <details>
                            <summary class="cursor-pointer text-xs text-gray-muted">Edit {{ \Illuminate\Support\Str::limit($m->title, 40) }}</summary>
                            <form method="POST" action="{{ route('admin.media.update', $m) }}" class="mt-2 grid gap-2">
                                @csrf @method('PUT')
                                <input class="k-input" name="title" value="{{ $m->title }}" aria-label="Title" maxlength="200">
                                <input class="k-input" name="alt_text" value="{{ $m->alt_text }}" aria-label="Alt text" placeholder="Alt text" maxlength="250">
                                <select class="k-input" name="visibility" aria-label="Visibility"><option value="public" @selected($m->isPublic())>Public</option><option value="private" @selected(! $m->isPublic())>Private</option></select>
                                <button class="k-btn-outline !py-1.5">Save</button>
                            </form>
                            <form method="POST" action="{{ route('admin.media.destroy', $m) }}" class="mt-2" onsubmit="return confirm('Delete this file?')">@csrf @method('DELETE')<button class="text-xs text-red-700 underline">Delete</button></form>
                        </details>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $media->links() }}</div>
    @endif
</x-layouts.portal>
