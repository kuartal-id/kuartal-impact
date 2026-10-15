<x-layouts.app :title="$org->name.' — Kuartal Impact Index'" :description="\Illuminate\Support\Str::limit((string) $org->summary, 160)">
    <x-index.subnav />
    <section class="k-section !pt-8">
        <div class="k-container">
            <a href="{{ route('impact-index') }}" class="k-meta underline">← All organisations</a>
            <div class="mt-6 flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                <div class="flex min-w-0 items-start gap-4 sm:gap-5">
                    @if ($org->logo_url)
                        <img src="{{ $org->logo_url }}" alt="{{ $org->name }} logo" class="h-16 w-16 shrink-0 rounded-card sm:h-20 sm:w-20 border border-gray-border bg-white object-contain p-1 dark:border-white/10">
                    @else
                        <span class="k-surface flex h-16 w-16 shrink-0 items-center sm:h-20 sm:w-20 justify-center font-display text-3xl font-semibold k-accent" aria-hidden="true">{{ mb_substr($org->name, 0, 1) }}</span>
                    @endif
                    <div>
                        <p class="k-eyebrow mb-1">{{ $org->typeLabel() }}</p>
                        <h1 class="text-2xl break-words sm:text-3xl md:text-4xl">{{ $org->name }}</h1>
                        <p class="k-meta mt-2">
                            @if($org->headquarters){{ $org->headquarters }}, @endif{{ $org->country }}
                            @if($org->founded_year) · Founded {{ $org->founded_year }}@endif
                            @if($org->registration_number) · Reg. {{ $org->registration_number }}@endif
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($org->sectors as $s)<a class="k-chip" href="{{ route('impact-index.sector', $s) }}">{{ $s->name }}</a>@endforeach
                        </div>
                    </div>
                </div>
                <div class="k-surface p-5 md:min-w-[260px]">
                    <p class="k-eyebrow mb-2">Impact Score</p>
                    <x-index.grade :grade="$org->overall_grade" :score="$org->overall_score" size="lg" />
                    @if ($org->isRated())
                        <p class="k-meta mt-3">Confidence: <strong class="capitalize">{{ $org->confidence }}</strong> ({{ (int) round($result['completeness'] * 100) }}% evidence completeness)</p>
                        <p class="k-meta">Methodology v{{ $org->methodology_version }}</p>
                    @else
                        <p class="k-meta mt-3">Needs at least {{ \App\Support\ImpactIndex\Scoring::MIN_PILLARS }} published pillars with evidence.</p>
                    @endif
                    <p class="k-meta">Last reviewed: {{ $org->last_reviewed_at?->format('j M Y') ?? 'not yet' }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($org->donation_url)<a href="{{ $org->donation_url }}" class="k-btn-primary" rel="noopener nofollow" target="_blank">Donate on their site</a>@endif
                        @if ($org->website)<a href="{{ $org->website }}" class="k-btn-ghost" rel="noopener nofollow" target="_blank">Website</a>@endif
                    </div>
                </div>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-10 lg:grid-cols-[1fr_380px]">
                <div class="min-w-0 space-y-10">
                    <div><h2 class="text-2xl mb-3">What they do</h2><div class="k-prose">{!! nl2br(e($org->summary ?: 'Not provided yet.')) !!}</div></div>
                    <div><h2 class="text-2xl mb-3">How they work</h2><p class="k-meta mb-2">Theory of change</p><div class="k-prose">{!! nl2br(e($org->theory_of_change ?: 'Not provided yet.')) !!}</div></div>

                    <div>
                        <h2 class="text-2xl mb-4">Score by pillar</h2>
                        <div class="space-y-4">
                            @foreach ($pillars as $p)
                                @php $ps = $scores->get($p->id); $live = $ps && $ps->published; @endphp
                                <div class="k-card !p-5" data-pillar="{{ $p->key }}">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div><h3 class="font-display text-lg font-semibold">{{ $p->name }}</h3><p class="k-meta">Weight {{ $p->weight }}%</p></div>
                                        <x-index.grade :grade="$live ? $ps->grade() : null" :score="$live ? $ps->score() : null" />
                                    </div>
                                    @if ($live)
                                        @if ($ps->notes)<p class="mt-3 text-sm">{{ $ps->notes }}</p>@endif
                                        <ul class="mt-3 space-y-1 text-sm">
                                            @foreach ($ps->criteria as $c)<li class="flex justify-between gap-4 border-b border-gray-border py-1 dark:border-white/10"><span>{{ $c->label }}</span><span class="font-medium">{{ $c->score }}</span></li>@endforeach
                                        </ul>
                                        <p class="k-eyebrow mt-4 mb-1">Evidence</p>
                                        <ul class="space-y-1 text-sm">
                                            @foreach ($ps->sources() as $url => $label)<li><a class="underline k-accent break-all" href="{{ $url }}" rel="noopener nofollow" target="_blank">{{ $label }}</a></li>@endforeach
                                        </ul>
                                        @if ($ps->reviewed_at)<p class="k-meta mt-2">Reviewed {{ $ps->reviewed_at->format('j M Y') }}</p>@endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h2 class="text-2xl mb-3">Financials by year</h2>
                        @if ($org->financials->isEmpty())
                            <x-empty-state title="No financial data yet">We add yearly figures from published accounts.</x-empty-state>
                        @else
                            <div class="overflow-x-auto"><table class="k-prose w-full text-sm"><tr><th>Year</th><th>Income</th><th>Expenditure</th><th>Programme spend</th><th>Source</th></tr>
                                @foreach ($org->financials as $f)<tr><td>{{ $f->year }}</td><td>{{ $f->income !== null ? $f->currency.' '.number_format((float) $f->income) : '—' }}</td><td>{{ $f->expenditure !== null ? $f->currency.' '.number_format((float) $f->expenditure) : '—' }}</td><td>{{ $f->programme_spend !== null ? $f->currency.' '.number_format((float) $f->programme_spend) : '—' }}</td><td>@if($f->source_url)<a href="{{ $f->source_url }}" rel="noopener nofollow" target="_blank">Source</a>@endif</td></tr>@endforeach
                            </table></div>
                        @endif
                    </div>
                </div>

                <aside class="min-w-0 space-y-6">
                    <div class="k-surface p-5">
                        <h2 class="font-display text-lg font-semibold mb-3">How money is used</h2>
                        @php $split = ['Programmes' => $org->programme_pct, 'Admin' => $org->admin_pct, 'Fundraising' => $org->fundraising_pct]; $colours = ['Programmes' => 'bg-green-400', 'Admin' => 'bg-navy', 'Fundraising' => 'bg-black/40 dark:bg-white/50']; @endphp
                        @if (collect($split)->filter(fn ($v) => $v !== null)->isEmpty())
                            <p class="k-meta">Not disclosed or not yet reviewed.</p>
                        @else
                            <div class="flex h-4 w-full overflow-hidden rounded-pill bg-white dark:bg-white/10" role="img" aria-label="Programmes {{ $org->programme_pct ?? 0 }}%, admin {{ $org->admin_pct ?? 0 }}%, fundraising {{ $org->fundraising_pct ?? 0 }}%">
                                @foreach ($split as $l => $v)@if($v)<span class="{{ $colours[$l] }}" style="width: {{ min(100, $v) }}%"></span>@endif @endforeach
                            </div>
                            <ul class="mt-3 space-y-1 text-sm">@foreach ($split as $l => $v)<li class="flex items-center gap-2"><span class="h-3 w-3 rounded-sm {{ $colours[$l] }}"></span>{{ $l }}<span class="ml-auto font-medium">{{ $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.').'%' }}</span></li>@endforeach</ul>
                            @if ($org->spending_source_url)<a class="k-meta underline" href="{{ $org->spending_source_url }}" rel="noopener nofollow" target="_blank">Source</a>@endif
                        @endif
                    </div>
                    <div class="k-surface p-5">
                        <h2 class="font-display text-lg font-semibold mb-2">Where they work</h2>
                        @forelse ($org->regions as $r)<a class="k-chip mb-1 mr-1" href="{{ route('impact-index.region', $r) }}">{{ $r->path() }}</a>@empty<p class="k-meta">Not specified.</p>@endforelse
                    </div>
                    <div class="k-surface p-5">
                        <h2 class="font-display text-lg font-semibold mb-2">Funding sources</h2>
                        <p class="text-sm">{!! nl2br(e($org->funding_sources ?: 'Not specified.')) !!}</p>
                    </div>
                    <div class="k-surface p-5">
                        <h2 class="font-display text-lg font-semibold mb-2">Audits &amp; reports</h2>
                        @forelse ($org->documents as $d)
                            <a class="block py-1 text-sm underline k-accent" href="{{ $d->url }}" rel="noopener nofollow" target="_blank">{{ $d->kind === 'audit' ? 'Audit' : 'Report' }}{{ $d->year ? ' '.$d->year : '' }}: {{ $d->title }}</a>
                        @empty<p class="k-meta">None listed yet.</p>@endforelse
                    </div>
                    @if ($org->reviewer_notes)
                        <div class="k-surface p-5"><h2 class="font-display text-lg font-semibold mb-2">Reviewer notes</h2><p class="text-sm">{!! nl2br(e($org->reviewer_notes)) !!}</p></div>
                    @endif
                    <x-index.disclaimer />
                </aside>
            </div>
        </div>
    </section>
</x-layouts.app>
