<x-layouts.portal title="Scores — {{ $org->name }} — Impact Index admin">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="font-display text-2xl font-semibold">Scores: {{ $org->name }}</h1>
        <div class="flex gap-3 text-sm"><a class="k-btn-outline" href="{{ route('admin.index.orgs.edit', $org) }}">Profile</a><span>Overall: <strong>{{ $org->overall_score === null ? 'Not yet rated' : $org->overall_score.' ('.$org->overall_grade.', '.$org->confidence.')' }}</strong></span></div>
    </div>
    <p class="mb-4 text-sm text-gray-muted">Sub-criteria: one per line, <code>label | score 0–100 | weight 1–10 (optional) | evidence URL (optional)</code>. Evidence sources: <code>title | URL</code>. A pillar can be published only with at least one sub-criterion and one evidence source. The overall score needs {{ \App\Support\ImpactIndex\Scoring::MIN_PILLARS }} published pillars. <a class="underline" target="_blank" href="{{ route('impact-index.methodology') }}">Methodology</a>.</p>
    <form method="POST" action="{{ route('admin.index.orgs.scores.save', $org) }}">
        @csrf @method('PUT')
        @foreach ($pillars as $p)
            @php $ps = $scores->get($p->id); $base = "p.{$p->id}"; @endphp
            <x-admin.card :title="$p->name.' (weight '.$p->weight.'%)'">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div><label class="k-label">Sub-criteria</label><textarea class="k-input font-mono" rows="5" name="p[{{ $p->id }}][criteria]" placeholder="Outcome data published | 70 | 2 | https://…">{{ old($base.'.criteria', $ps ? $ps->criteria->map(fn ($c) => implode(' | ', array_filter([$c->label, $c->score, $c->weight, $c->evidence_url], fn ($v) => $v !== null)))->implode("\n") : '') }}</textarea></div>
                    <div><label class="k-label">Evidence sources</label><textarea class="k-input font-mono" rows="5" name="p[{{ $p->id }}][evidence]" placeholder="Annual report 2025 | https://…">{{ old($base.'.evidence', $ps ? $ps->evidence->map(fn ($e) => $e->title.' | '.$e->url)->implode("\n") : '') }}</textarea></div>
                    <div><label class="k-label">Public notes</label><textarea class="k-input" rows="2" name="p[{{ $p->id }}][notes]">{{ old($base.'.notes', $ps?->notes) }}</textarea></div>
                    <div class="flex flex-wrap items-end gap-4">
                        <div><label class="k-label">Reviewed</label><input type="date" class="k-input" name="p[{{ $p->id }}][reviewed_at]" value="{{ old($base.'.reviewed_at', $ps?->reviewed_at?->toDateString()) }}"></div>
                        <input type="hidden" name="p[{{ $p->id }}][published]" value="0">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="p[{{ $p->id }}][published]" value="1" @checked(old($base.'.published', $ps?->published))> Published</label>
                        @if ($ps && $ps->score() !== null)<span class="text-sm">Pillar score: <strong>{{ $ps->score() }} ({{ $ps->grade() }})</strong></span>@endif
                    </div>
                </div>
            </x-admin.card>
        @endforeach
        <button class="k-btn-primary">Save scores</button>
    </form>
</x-layouts.portal>
