@props(['project', 'fund' => null])
<article class="k-card flex flex-col gap-3">
    @if ($project->media)<img src="{{ $project->media->url() }}" alt="{{ $project->title }} logo" class="h-10 w-auto self-start">@endif
    @if ($fund)<a href="{{ route('programmes.show', $fund->slug) }}" class="k-eyebrow hover:underline">{{ $fund->title }}</a>@endif
    <h3 class="text-xl">{{ $project->title }}</h3>
    @if ($project->subtitle)<p class="text-gray-muted dark:text-white/65">{{ $project->subtitle }}</p>@endif
    @if ($project->body)<p class="text-sm text-gray-muted dark:text-white/65">{{ $project->body }}</p>@endif
    @if ($project->location)<p class="k-meta">{{ $project->location }}</p>@endif
    @if ($project->url)<a href="{{ $project->url }}" class="mt-auto font-subhead text-sm font-medium text-navy hover:underline dark:text-green-400" rel="noopener">Visit →</a>@endif
</article>
