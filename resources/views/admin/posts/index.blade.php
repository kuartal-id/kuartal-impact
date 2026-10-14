<x-layouts.portal title="Newsroom — Kuartal Impact admin">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="font-display text-2xl font-semibold">Newsroom posts</h1>
        <div class="flex gap-2"><a href="{{ route('newsroom.index') }}" target="_blank" class="k-btn-ghost">View newsroom</a><a href="{{ route('admin.posts.create') }}" class="k-btn-primary">New post</a></div>
    </div>
    <form class="flex flex-wrap gap-3 mb-4 text-sm" method="GET">
        <label class="sr-only" for="pq">Search</label><input id="pq" class="k-input w-56" name="q" value="{{ request('q') }}" placeholder="Search titles">
        <select name="status" class="k-input w-auto" aria-label="Status"><option value="">All statuses</option>@foreach (['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published'] as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
        <select name="category" class="k-input w-auto" aria-label="Category"><option value="">All categories</option>@foreach (config('kuartalimpact.post_categories') as $k => $v)<option value="{{ $k }}" @selected(request('category') === $k)>{{ $v }}</option>@endforeach</select>
        <button class="k-btn-outline">Filter</button>
    </form>
    <x-admin.card>
        @if ($posts->isEmpty())
            <x-admin.empty>No posts match. <a class="underline" href="{{ route('admin.posts.create') }}">Write a post</a>.</x-admin.empty>
        @else
            <div class="overflow-x-auto"><table class="k-table">
                <tr><th>Title</th><th>Category</th><th>Status</th><th>Tags</th><th></th></tr>
                @foreach ($posts as $p)
                    <tr>
                        <td class="font-medium"><div class="flex items-center gap-3">@if($p->cover)<img src="{{ $p->cover->url() }}" alt="" class="h-9 w-14 rounded object-cover">@endif<span>{{ $p->title }}<span class="block text-xs text-gray-muted">/newsroom/{{ $p->slug }}</span></span></div></td>
                        <td>{{ $p->categoryLabel() }}</td>
                        <td><x-admin.state :post="$p" /></td>
                        <td class="text-xs text-gray-muted">{{ implode(', ', $p->tags ?? []) }}</td>
                        <td class="whitespace-nowrap"><a class="underline" href="{{ route('admin.posts.edit', $p) }}">Edit</a>@if($p->isLive()) · <a class="underline" target="_blank" href="{{ route('newsroom.show', $p) }}">View</a>@endif</td>
                    </tr>
                @endforeach
            </table></div>
            <div class="mt-4">{{ $posts->links() }}</div>
        @endif
    </x-admin.card>
</x-layouts.portal>
