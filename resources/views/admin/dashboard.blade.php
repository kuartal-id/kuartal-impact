<x-layouts.portal title="Admin — Kuartal Impact">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-display text-2xl font-semibold">Dashboard</h1>
            <p class="text-sm text-gray-muted">kuartalimpact.org funds, newsroom and site content.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.posts.create') }}" class="k-btn-primary">New post</a>
            <a href="{{ route('admin.media.index') }}" class="k-btn-outline">Upload media</a>
            <a href="{{ route('admin.entries.create', ['type' => 'metric']) }}" class="k-btn-outline">Add fund figure</a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 mb-6">
        @foreach ($counts as $c)
            <a href="{{ route($c['route'], $c['params'] ?? []) }}" class="k-stat hover:border-green transition-colors">
                <span class="block font-display text-2xl font-semibold">{{ $c['value'] }}</span>
                <span class="block text-xs text-gray-muted mt-1">{{ $c['label'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-admin.card title="Still to fill in" class="lg:col-span-2">
            @if (empty($checklist))
                <x-admin.empty>Every public section has content.</x-admin.empty>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach (array_keys($checklist) as $item)
                        <li class="flex gap-2"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-amber-400" aria-hidden="true"></span>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
        </x-admin.card>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-admin.card title="Recent posts">
            @forelse ($recentPosts as $p)
                <div class="flex justify-between gap-4 border-b border-gray-border/60 py-2 text-sm">
                    <a class="underline" href="{{ route('admin.posts.edit', $p) }}">{{ $p->title }}</a>
                    <x-admin.state :post="$p" />
                </div>
            @empty
                <x-admin.empty>No posts yet. <a class="underline" href="{{ route('admin.posts.create') }}">Write the first one</a>.</x-admin.empty>
            @endforelse
        </x-admin.card>
    </div>
</x-layouts.portal>
