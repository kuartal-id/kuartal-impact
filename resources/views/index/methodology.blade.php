@php $S = \App\Support\ImpactIndex\Scoring::class; @endphp
<x-layouts.app title="Methodology — Kuartal Impact Index" description="How the Kuartal Impact Index grades organisations: five evidence-based pillars, a weighted Impact Score and a confidence level.">
    <x-page-hero eyebrow="Kuartal Impact Index · Methodology v{{ $version }}" title="How we score organisations">
        Plain rules, published weights, and a source behind every number.
    </x-page-hero>
    <x-index.subnav />
    <section class="k-section !pt-10">
        <div class="k-container max-w-4xl k-prose">
            <h2>In short</h2>
            <ol>
                <li>We review an organisation's own published material and independent records: reports, audited accounts, registration, evaluations.</li>
                <li>Each of five <strong>pillars</strong> is scored 0–100 from smaller <strong>sub-criteria</strong>. Each sub-criterion is scored 0–100 and can carry a weight (1–10). The pillar score is the weighted average of its sub-criteria.</li>
                <li>A pillar is shown only when it is <strong>published</strong>, and a pillar cannot be published without at least one evidence source. Every published score lists its sources.</li>
                <li>The <strong>Impact Score</strong> (0–100) is the weighted average of the published pillar scores, using the weights below. If some pillars are not yet published, the weights of the published ones are scaled up to 100%.</li>
                <li>An organisation needs at least {{ $S::MIN_PILLARS }} published pillars to get an Impact Score. Until then it shows <strong>Not yet rated</strong>.</li>
            </ol>

            <h2>The five pillars and their weights</h2>
            <div class="not-prose overflow-x-auto"><table class="k-prose w-full text-sm">
                <tr><th>Pillar</th><th>What we look at</th><th class="text-right">Weight</th></tr>
                @foreach ($pillars as $p)<tr><td class="font-semibold">{{ $p->name }}</td><td>{{ $p->description }}</td><td class="text-right">{{ $p->weight }}%</td></tr>@endforeach
            </table></div>

            <h2>Grades</h2>
            <p>Each pillar score and the overall score get a letter grade:</p>
            <ul>
                @foreach ($S::GRADES as $g => $min)@php $b = $S::band($g); @endphp<li><strong>{{ $g }}</strong>: {{ $b[0] }}{{ $g === 'A+' ? '–100' : '–'.rtrim(rtrim(number_format($b[1], 1), '0'), '.') }}</li>@endforeach
            </ul>

            <h2>Confidence level</h2>
            <p>Confidence tells you how complete the evidence is, not how good the organisation is. Evidence completeness = (published pillars ÷ all pillars) × (sub-criteria with their own evidence link ÷ all scored sub-criteria).</p>
            <ul>
                <li><strong>High</strong>: completeness of 80% or more</li>
                <li><strong>Medium</strong>: 50% to 79%</li>
                <li><strong>Low</strong>: under 50%</li>
            </ul>

            <h2>A worked example</h2>
            <p>Suppose Impact &amp; Outcomes scores 80 (weight 30), Financial Health 70 (weight 20) and Transparency 90 (weight 20), and the other two pillars are not yet published. The Impact Score is (80×30 + 70×20 + 90×20) ÷ (30+20+20) = 5,600 ÷ 70 = <strong>80.0, grade A</strong>. Confidence would be at most 3 ÷ 5 = 60%, so <strong>Medium</strong>.</p>

            <h2>What we never do</h2>
            <ul>
                <li>We do not invent data or scores. If we cannot find evidence, the pillar stays unpublished.</li>
                <li>Organisations do not pay to be listed or to change a grade.</li>
                <li>Campaign pages only group organisations; they do not change scores.</li>
            </ul>

            <h2>Versions, reviews and corrections</h2>
            <p>This is methodology version <strong>{{ $version }}</strong>. Each profile shows the version it was scored under and when it was last reviewed. Weights may change in a new version; when they do, we say so here.</p>
            <p>Organisations can request a correction or a re-review by writing to <a href="mailto:{{ config('kuartal.contact_email', 'hello@kuartal.id') }}">{{ config('kuartal.contact_email', 'hello@kuartal.id') }}</a> with links to their evidence.</p>
            <x-index.disclaimer class="not-prose mt-8" />
        </div>
    </section>
</x-layouts.app>
