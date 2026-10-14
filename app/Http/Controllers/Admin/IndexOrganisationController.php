<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Index\Organisation;
use App\Models\Index\Pillar;
use App\Models\Index\Region;
use App\Models\Index\Sector;
use App\Support\ImpactIndex\Scoring;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Admin CRUD for Index organisations, plus their pillar scores / sub-criteria / evidence. */
class IndexOrganisationController extends Controller
{
    private const URL = ['nullable', 'string', 'max:500', 'url:http,https'];

    public function index(Request $request): View
    {
        $orgs = Organisation::query()
            ->when($request->query('q'), fn ($q, $t) => $q->where('name', 'like', '%'.$t.'%'))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('name')->paginate(30)->withQueryString();

        return view('admin.index.orgs.index', ['orgs' => $orgs]);
    }

    public function create(): View
    {
        return $this->form(new Organisation(['status' => 'draft', 'type' => 'ngo']));
    }

    public function store(Request $request): RedirectResponse
    {
        $org = DB::transaction(function () use ($request) {
            [$data, $rel] = $this->validated($request, null);
            $org = Organisation::create($data);
            $this->syncRelations($org, $rel);

            return $org;
        });
        $org->recalculate();

        return redirect()->route('admin.index.orgs.edit', $org)->with('status', "Saved \"{$org->name}\".");
    }

    public function edit(Organisation $org): View
    {
        return $this->form($org->load(['sectors', 'regions', 'financials', 'documents']));
    }

    public function update(Request $request, Organisation $org): RedirectResponse
    {
        DB::transaction(function () use ($request, $org) {
            [$data, $rel] = $this->validated($request, $org);
            $org->update($data);
            $this->syncRelations($org, $rel);
        });
        $org->recalculate();

        return redirect()->route('admin.index.orgs.edit', $org)->with('status', "Saved \"{$org->name}\".");
    }

    public function destroy(Organisation $org): RedirectResponse
    {
        $org->delete();

        return redirect()->route('admin.index.orgs.index')->with('status', "Deleted \"{$org->name}\".");
    }

    public function scores(Organisation $org): View
    {
        return view('admin.index.orgs.scores', [
            'org' => $org, 'pillars' => Pillar::ordered(),
            'scores' => $org->pillarScores()->with(['criteria', 'evidence'])->get()->keyBy('pillar_id'),
        ]);
    }

    public function saveScores(Request $request, Organisation $org): RedirectResponse
    {
        $pillars = Pillar::ordered();
        $input = $request->validate([
            'p' => ['array'],
            'p.*.published' => ['nullable', 'boolean'],
            'p.*.notes' => ['nullable', 'string', 'max:5000'],
            'p.*.reviewed_at' => ['nullable', 'date'],
            'p.*.criteria' => ['nullable', 'string', 'max:20000'],
            'p.*.evidence' => ['nullable', 'string', 'max:20000'],
        ])['p'] ?? [];

        $parsed = [];
        $errors = [];
        foreach ($pillars as $p) {
            $row = $input[$p->id] ?? [];
            $criteria = [];
            foreach (self::lines($row['criteria'] ?? '') as $n => $cols) {
                [$label, $score, $weight, $url] = array_pad($cols, 4, null);
                if (! filled($label) || ! is_numeric($score) || $score < 0 || $score > 100
                    || (filled($weight) && (! ctype_digit((string) $weight) || $weight < 1 || $weight > 10))
                    || (filled($url) && ! self::isUrl($url))) {
                    $errors["p.{$p->id}.criteria"] = "{$p->name}: line ".($n + 1).' must be "label | score 0-100 | weight 1-10 | evidence URL".';

                    continue;
                }
                $criteria[] = ['label' => mb_substr($label, 0, 250), 'score' => (int) round((float) $score), 'weight' => filled($weight) ? (int) $weight : 1,
                    'evidence_url' => filled($url) ? $url : null, 'sort' => $n];
            }
            $evidence = [];
            foreach (self::lines($row['evidence'] ?? '') as $n => $cols) {
                [$title, $url] = array_pad($cols, 2, null);
                if (! filled($title) || ! self::isUrl((string) $url)) {
                    $errors["p.{$p->id}.evidence"] = "{$p->name}: evidence line ".($n + 1).' must be "title | https://…".';

                    continue;
                }
                $evidence[] = ['title' => mb_substr($title, 0, 250), 'url' => $url];
            }
            $sources = count($evidence) + count(array_filter($criteria, fn ($c) => $c['evidence_url']));
            $publish = (bool) ($row['published'] ?? false);
            if ($publish && ! Scoring::canPublish($sources, count($criteria))) {
                $errors["p.{$p->id}.published"] = "{$p->name} can't be published without at least one scored sub-criterion and one evidence source.";
            }
            $parsed[$p->id] = compact('criteria', 'evidence', 'publish') + ['notes' => $row['notes'] ?? null, 'reviewed_at' => $row['reviewed_at'] ?? null];
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($org, $parsed) {
            foreach ($parsed as $pillarId => $d) {
                $ps = $org->pillarScores()->firstOrNew(['pillar_id' => $pillarId]);
                if (! $ps->exists && ! $d['criteria'] && ! $d['evidence'] && ! filled($d['notes'])) {
                    continue;
                }
                $ps->fill(['published' => $d['publish'], 'notes' => $d['notes'], 'reviewed_at' => $d['reviewed_at']])->save();
                $ps->criteria()->delete();
                $ps->criteria()->createMany($d['criteria']);
                $ps->evidence()->delete();
                $ps->evidence()->createMany($d['evidence']);
            }
        });
        $org->recalculate();

        return redirect()->route('admin.index.orgs.scores', $org)->with('status', 'Scores saved. Overall: '
            .($org->overall_score === null ? 'Not yet rated' : $org->overall_score.' ('.$org->overall_grade.', '.$org->confidence.' confidence)').'.');
    }

    private function form(Organisation $org): View
    {
        return view('admin.index.orgs.form', ['org' => $org, 'sectors' => Sector::ordered(), 'regions' => Region::tree()]);
    }

    private function validated(Request $request, ?Organisation $org): array
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('ix_organisations', 'slug')->ignore($org?->id)],
            'type' => ['required', Rule::in(array_keys(Organisation::TYPES))],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'logo_url' => self::URL, 'website' => self::URL, 'donation_url' => self::URL, 'spending_source_url' => self::URL,
            'country' => ['nullable', 'string', 'max:120'],
            'headquarters' => ['nullable', 'string', 'max:200'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:'.(date('Y') + 1)],
            'registration_number' => ['nullable', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:10000'],
            'theory_of_change' => ['nullable', 'string', 'max:10000'],
            'funding_sources' => ['nullable', 'string', 'max:5000'],
            'programme_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'admin_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fundraising_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'last_reviewed_at' => ['nullable', 'date'],
            'reviewer_notes' => ['nullable', 'string', 'max:10000'],
            'sectors' => ['nullable', 'array'], 'sectors.*' => ['integer', 'exists:ix_sectors,id'],
            'regions' => ['nullable', 'array'], 'regions.*' => ['integer', 'exists:ix_regions,id'],
            'financials' => ['nullable', 'string', 'max:20000'],
            'documents' => ['nullable', 'string', 'max:20000'],
        ]);
        $sum = (float) ($d['programme_pct'] ?? 0) + (float) ($d['admin_pct'] ?? 0) + (float) ($d['fundraising_pct'] ?? 0);
        if ($sum > 100.05) {
            throw ValidationException::withMessages(['programme_pct' => 'Programme + admin + fundraising can\'t exceed 100%.']);
        }

        $fin = [];
        foreach (self::lines($d['financials'] ?? '') as $n => $c) {
            [$year, $cur, $inc, $exp, $prog, $url] = array_pad($c, 6, null);
            $v = Validator::make(compact('year', 'cur', 'inc', 'exp', 'prog', 'url'), [
                'year' => ['required', 'integer', 'min:1900', 'max:2100'], 'cur' => ['required', 'alpha', 'size:3'],
                'inc' => ['nullable', 'numeric'], 'exp' => ['nullable', 'numeric'], 'prog' => ['nullable', 'numeric'], 'url' => self::URL]);
            if ($v->fails()) {
                throw ValidationException::withMessages(['financials' => 'Financials line '.($n + 1).' must be "year | currency | income | expenditure | programme spend | source URL".']);
            }
            $fin[] = ['year' => (int) $year, 'currency' => strtoupper($cur), 'income' => filled($inc) ? $inc : null,
                'expenditure' => filled($exp) ? $exp : null, 'programme_spend' => filled($prog) ? $prog : null, 'source_url' => filled($url) ? $url : null];
        }
        $docs = [];
        foreach (self::lines($d['documents'] ?? '') as $n => $c) {
            [$kind, $year, $title, $url] = array_pad($c, 4, null);
            if (! in_array(strtolower((string) $kind), ['audit', 'report'], true) || (filled($year) && ! ctype_digit($year)) || ! filled($title) || ! self::isUrl((string) $url)) {
                throw ValidationException::withMessages(['documents' => 'Documents line '.($n + 1).' must be "audit|report | year | title | https://…".']);
            }
            $docs[] = ['kind' => strtolower($kind), 'year' => filled($year) ? (int) $year : null, 'title' => mb_substr($title, 0, 250), 'url' => $url];
        }

        $data = collect($d)->except(['sectors', 'regions', 'financials', 'documents', 'slug'])->all();
        $data['slug'] = filled($d['slug'] ?? null) ? $d['slug'] : ($org?->slug ?? Organisation::uniqueSlug($d['name']));

        return [$data, ['sectors' => $d['sectors'] ?? [], 'regions' => $d['regions'] ?? [], 'financials' => $fin, 'documents' => $docs]];
    }

    private function syncRelations(Organisation $org, array $rel): void
    {
        $org->sectors()->sync($rel['sectors']);
        $org->regions()->sync($rel['regions']);
        $org->financials()->delete();
        $org->financials()->createMany($rel['financials']);
        $org->documents()->delete();
        $org->documents()->createMany($rel['documents']);
    }

    /** "a | b | c" lines -> [[a, b, c], ...], blank lines skipped. */
    public static function lines(?string $text): array
    {
        return collect(preg_split('/\R/', (string) $text))->map(fn ($l) => trim($l))->filter()
            ->map(fn ($l) => array_map('trim', explode('|', $l)))->values()->all();
    }

    private static function isUrl(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url);
    }
}
