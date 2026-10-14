<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Post;
use App\Models\SiteEntry;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'counts' => [
                ['label' => 'Published posts', 'value' => Post::live()->count(), 'route' => 'admin.posts.index'],
                ['label' => 'Drafts & scheduled', 'value' => Post::count() - Post::live()->count(), 'route' => 'admin.posts.index'],
                ['label' => 'Funds', 'value' => SiteEntry::where('type', 'fund')->count(), 'route' => 'admin.entries.index', 'params' => ['type' => 'fund']],
                ['label' => 'Fund figures', 'value' => SiteEntry::where('type', 'metric')->count(), 'route' => 'admin.entries.index', 'params' => ['type' => 'metric']],
                ['label' => 'Projects', 'value' => SiteEntry::where('type', 'portfolio')->count(), 'route' => 'admin.entries.index', 'params' => ['type' => 'portfolio']],
                ['label' => 'Media files', 'value' => Media::count(), 'route' => 'admin.media.index'],
            ],
            'recentPosts' => Post::latest('updated_at')->limit(5)->get(),
            'checklist' => $this->checklist(),
        ]);
    }

    /** Site sections still waiting for owner-supplied content. */
    private function checklist(): array
    {
        $missingBlocks = collect(config('kuartalimpact.blocks'))
            ->reject(fn ($label, $key) => SiteEntry::block($key) !== null);
        $fundsWithoutText = SiteEntry::where('type', 'fund')->where(fn ($q) => $q->whereNull('body')->orWhere('body', ''))->pluck('title');
        $fundsWithoutFigures = SiteEntry::where('type', 'fund')->get()
            ->filter(fn ($f) => SiteEntry::where('type', 'metric')->where('group', $f->slug)->doesntExist())->pluck('title');

        return array_filter([
            'Newsroom: publish the first post' => Post::live()->doesntExist(),
            'Team: add team members' => SiteEntry::where('type', 'person')->doesntExist(),
            'Partners: add partners' => SiteEntry::where('type', 'partner')->doesntExist(),
            'Projects: add projects backed by the funds' => SiteEntry::where('type', 'portfolio')->doesntExist(),
        ])
            + $fundsWithoutText->mapWithKeys(fn ($t) => ["Fund page text: $t" => true])->all()
            + $fundsWithoutFigures->mapWithKeys(fn ($t) => ["Fund figures: $t" => true])->all()
            + $missingBlocks->mapWithKeys(fn ($label) => ['Text: '.$label => true])->all();
    }
}
