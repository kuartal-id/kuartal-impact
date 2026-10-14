<?php

namespace App\Http\Controllers;

use App\Models\Index\Campaign;
use App\Models\Index\Organisation;
use App\Models\Index\Pillar;
use App\Models\Index\Region;
use App\Models\Index\Sector;
use App\Models\Index\Setting;
use App\Support\ImpactIndex\OrganisationSearch;
use App\Support\ImpactIndex\Scoring;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Kuartal Impact Index (public). */
class ImpactIndexController extends Controller
{
    public function index(Request $request): View
    {
        $params = $request->only(['q', 'region', 'sector', 'type', 'grade', 'min', 'max', 'sort']);
        $orgs = OrganisationSearch::query($params)->paginate(24)->withQueryString();

        return view('index.index', [
            'orgs' => $orgs, 'params' => $params, 'sectors' => Sector::ordered(), 'regions' => Region::tree(),
            'campaigns' => Campaign::active()->get(), 'total' => Organisation::published()->count(),
            'filtered' => collect($params)->except('sort')->filter(fn ($v) => filled($v))->isNotEmpty(),
        ]);
    }

    public function methodology(): View
    {
        return view('index.methodology', ['pillars' => Pillar::ordered(), 'version' => Setting::methodologyVersion()]);
    }

    public function rankings(): View
    {
        $rated = Organisation::published()->whereNotNull('overall_score')->with('sectors')->orderByDesc('overall_score')->orderBy('name');

        return view('index.rankings', [
            'leaders' => (clone $rated)->limit(25)->get(),
            'sectors' => Sector::ordered(),
            'regions' => Region::whereNull('parent_id')->with('children')->orderBy('name')->get(),
        ]);
    }

    public function sector(Sector $sector): View
    {
        $orgs = OrganisationSearch::query(['sector' => $sector->slug])->get();

        return view('index.ranking', ['heading' => $sector->name, 'kind' => 'Sector', 'orgs' => $orgs,
            'filterUrl' => route('impact-index', ['sector' => $sector->slug])]);
    }

    public function region(Region $region): View
    {
        $orgs = OrganisationSearch::query(['region' => $region->slug])->get();

        return view('index.ranking', ['heading' => $region->path(), 'kind' => 'Region', 'orgs' => $orgs,
            'filterUrl' => route('impact-index', ['region' => $region->slug]), 'children' => $region->children]);
    }

    public function show(Organisation $organisation): View
    {
        abort_unless($organisation->status === 'published', 404);
        $organisation->load(['sectors', 'regions.parent', 'financials', 'documents', 'pillarScores.criteria', 'pillarScores.evidence', 'campaigns']);
        $scores = $organisation->pillarScores->keyBy('pillar_id');

        return view('index.show', ['org' => $organisation, 'pillars' => Pillar::ordered(), 'scores' => $scores,
            'result' => Scoring::overall(Pillar::ordered(), $scores)]);
    }

    public function campaigns(): View
    {
        return view('index.campaigns', ['active' => Campaign::active()->get(), 'ended' => Campaign::ended()->get()]);
    }

    public function campaign(Campaign $campaign): View
    {
        $orgs = $campaign->organisations()->published()->with('sectors')
            ->orderByRaw('overall_score is null')->orderByDesc('overall_score')->orderBy('name')->get();

        return view('index.campaign', ['campaign' => $campaign, 'orgs' => $orgs]);
    }
}
