<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Index\Campaign;
use App\Models\Index\Organisation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IndexCampaignController extends Controller
{
    public function index(): View
    {
        return view('admin.index.campaigns.index', ['campaigns' => Campaign::withCount('organisations')->orderBy('status')->orderBy('sort')->get()]);
    }

    public function create(): View
    {
        return $this->form(new Campaign(['status' => 'active']));
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validated($request, null);
        $c = Campaign::create(collect($d)->except('organisations')->all());
        $c->organisations()->sync($d['organisations'] ?? []);

        return redirect()->route('admin.index.campaigns.edit', $c)->with('status', "Saved \"{$c->title}\".");
    }

    public function edit(Campaign $campaign): View
    {
        return $this->form($campaign->load('organisations'));
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $d = $this->validated($request, $campaign);
        $campaign->update(collect($d)->except('organisations')->all());
        $campaign->organisations()->sync($d['organisations'] ?? []);

        return redirect()->route('admin.index.campaigns.edit', $campaign)->with('status', "Saved \"{$campaign->title}\".");
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()->route('admin.index.campaigns.index')->with('status', "Deleted \"{$campaign->title}\".");
    }

    private function form(Campaign $c): View
    {
        return view('admin.index.campaigns.form', ['campaign' => $c, 'orgs' => Organisation::orderBy('name')->get(['id', 'name', 'status', 'overall_grade'])]);
    }

    private function validated(Request $request, ?Campaign $c): array
    {
        $d = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('ix_campaigns', 'slug')->ignore($c?->id)],
            'intro' => ['nullable', 'string', 'max:5000'],
            'give_safely' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'retired'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'organisations' => ['nullable', 'array'], 'organisations.*' => ['integer', 'exists:ix_organisations,id'],
        ]);
        $d['slug'] = filled($d['slug'] ?? null) ? $d['slug'] : ($c?->slug ?? Str::slug($d['title']));
        $d['sort'] = (int) ($d['sort'] ?? 0);

        return $d;
    }
}
