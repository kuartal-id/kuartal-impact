@props(['project', 'fund' => null])
<article class="k-panel flex flex-col gap-4">
    @if ($project->media)<img src="{{ $project->media->url() }}" alt="{{ $project->title }} logo" class="h-10 w-auto self-start">@endif
    @if ($fund)<a href="{{ route('programmes.show', $fund->slug) }}" class="k-mono hover:underline">{{ $fund->title }}</a>@endif
    <h3 class="text-xl">{{ $project->title }}</h3>
    @if ($project->subtitle)<p class="k-muted">{{ $project->subtitle }}</p>@endif
    @if ($project->body)<p class="text-sm k-muted">{{ $project->body }}</p>@endif
    @if ($project->location)<p class="k-mono-muted">{{ $project->location }}</p>@endif
    @if ($project->url)<a href="{{ $project->url }}" class="k-mono mt-auto hover:underline" rel="noopener">Visit →</a>@endif
</article>
