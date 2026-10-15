<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Index\Organisation;
use App\Models\Index\Pillar;
use App\Models\Index\Region;
use App\Models\Index\Sector;
use App\Models\Index\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Pillar weights + methodology version, sectors and regions. */
class IndexTaxonomyController extends Controller
{
    public function index(): View
    {
        return view('admin.index.settings', ['pillars' => Pillar::ordered(), 'sectors' => Sector::ordered(),
            'regions' => Region::tree(), 'version' => Setting::methodologyVersion()]);
    }

    public function saveWeights(Request $request): RedirectResponse
    {
        $pillars = Pillar::ordered();
        $d = $request->validate([
            'version' => ['required', 'string', 'max:20'],
            'weights' => ['required', 'array'], 'weights.*' => ['required', 'integer', 'min:0', 'max:100'],
            'descriptions' => ['nullable', 'array'], 'descriptions.*' => ['nullable', 'string', 'max:1000'],
        ]);
        if (array_sum(array_map('intval', $d['weights'])) !== 100) {
            throw ValidationException::withMessages(['weights' => 'Pillar weights must add up to 100.']);
        }
        foreach ($pillars as $p) {
            $p->update(['weight' => (int) ($d['weights'][$p->id] ?? $p->weight), 'description' => $d['descriptions'][$p->id] ?? $p->description]);
        }
        Setting::put('methodology_version', $d['version']);
        Organisation::query()->each(fn ($o) => $o->recalculate());

        return back()->with('status', 'Weights saved and all scores recalculated.');
    }

    public function storeSector(Request $request): RedirectResponse
    {
        $d = $request->validate(['name' => ['required', 'string', 'max:120']]);
        Sector::create(['name' => $d['name'], 'slug' => $this->slug('ix_sectors', $d['name']), 'sort' => (int) Sector::max('sort') + 1]);

        return back()->with('status', 'Sector added.');
    }

    public function updateSector(Request $request, Sector $sector): RedirectResponse
    {
        $sector->update($request->validate(['name' => ['required', 'string', 'max:120'], 'sort' => ['nullable', 'integer', 'min:0']]));

        return back()->with('status', 'Sector saved.');
    }

    public function destroySector(Sector $sector): RedirectResponse
    {
        $sector->delete();

        return back()->with('status', 'Sector deleted.');
    }

    public function storeRegion(Request $request): RedirectResponse
    {
        $d = $this->regionData($request, null);
        Region::create($d + ['slug' => $this->slug('ix_regions', $d['name'])]);

        return back()->with('status', 'Region added.');
    }

    public function updateRegion(Request $request, Region $region): RedirectResponse
    {
        $d = $this->regionData($request, $region);
        if ($d['parent_id'] && in_array((int) $d['parent_id'], $region->selfAndDescendantIds(), true)) {
            throw ValidationException::withMessages(['parent_id' => 'A region can\'t sit inside itself.']);
        }
        $region->update($d);

        return back()->with('status', 'Region saved.');
    }

    public function destroyRegion(Region $region): RedirectResponse
    {
        $region->delete();

        return back()->with('status', 'Region deleted.');
    }

    private function regionData(Request $request, ?Region $r): array
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'level' => ['required', Rule::in(array_keys(Region::LEVELS))],
            'parent_id' => ['nullable', 'integer', 'exists:ix_regions,id'],
        ]);
        if ($d['level'] === 'country') {
            $d['parent_id'] = null;
        } elseif (empty($d['parent_id'])) {
            throw ValidationException::withMessages(['parent_id' => 'Provinces and areas need a parent region.']);
        }

        return ['name' => $d['name'], 'level' => $d['level'], 'parent_id' => $d['parent_id'] ?? null];
    }

    private function slug(string $table, string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $i = 2;
        while (\DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
