<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\SiteEntry;
use Illuminate\View\View;

/**
 * Public pages. Everything factual comes from admin-managed SiteEntry rows
 * or config; a section with no data renders an empty state, never filler.
 */
class SiteController extends Controller
{
    public function home(): View
    {
        return view('home', [
            'funds' => SiteEntry::funds(),
            'posts' => Post::live()->with('cover')->latest('published_at')->limit(3)->get(),
            'quote' => SiteEntry::block('home.quote'),
            'quoteBy' => SiteEntry::block('home.quote_by'),
            'partners' => SiteEntry::listed('partner'),
        ]);
    }

    public function about(): View
    {
        return view('about', [
            'howWeInvest' => SiteEntry::block('about.how_we_invest'),
            'teamIntro' => SiteEntry::block('about.team_intro'),
            'people' => SiteEntry::listed('person')->groupBy('group'),
        ]);
    }

    public function focusAreas(): View
    {
        $funds = SiteEntry::funds();

        return view('focus-areas', ['fundsByArea' => $funds->groupBy('group')]);
    }

    public function portfolio(): View
    {
        return view('portfolio', [
            'intro' => SiteEntry::block('portfolio.intro'),
            'projects' => SiteEntry::listed('portfolio'),
            'funds' => SiteEntry::funds()->keyBy('slug'),
        ]);
    }

    public function programmes(): View
    {
        return view('programmes.index', [
            'intro' => SiteEntry::block('programmes.intro'),
            'funds' => SiteEntry::funds(),
        ]);
    }

    public function programme(string $slug): View
    {
        $fund = SiteEntry::where('type', 'fund')->where('slug', $slug)->where('is_published', true)->with('media')->firstOrFail();
        $funds = SiteEntry::funds();

        return view('programmes.show', [
            'fund' => $fund,
            'index' => $funds->search(fn ($f) => $f->is($fund)) + 1,
            'metrics' => $fund->metrics(),
            'projects' => $fund->projects(),
            'posts' => Post::live()->whereJsonContains('tags', $fund->slug)->latest('published_at')->limit(3)->get(),
            'others' => $funds->reject(fn ($f) => $f->is($fund))->values(),
        ]);
    }

    public function contact(): View
    {
        return view('contact', ['contacts' => config('kuartal.contacts')]);
    }
}
