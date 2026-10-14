<?php

namespace App\Support\ImpactIndex;

use App\Models\Index\Organisation;
use App\Models\Index\Region;
use App\Models\Index\Sector;
use Illuminate\Database\Eloquent\Builder;

/**
 * Public search + filters for the Index. Every filter is a GET parameter so URLs are shareable:
 * q, region (slug, includes sub-regions), sector (slug), type, grade (A+..D), min, max, sort (score|name|recent).
 */
class OrganisationSearch
{
    public const SORTS = ['score' => 'Highest score', 'name' => 'Name A–Z', 'recent' => 'Recently reviewed'];

    public static function query(array $p): Builder
    {
        $q = Organisation::published()->with(['sectors', 'regions']);

        if (filled($p['q'] ?? null)) {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($p['q'])).'%';
            $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('summary', 'like', $term)
                ->orWhere('headquarters', 'like', $term)->orWhere('country', 'like', $term));
        }
        if (filled($p['region'] ?? null) && $region = Region::where('slug', $p['region'])->first()) {
            $ids = $region->selfAndDescendantIds();
            $q->whereHas('regions', fn ($r) => $r->whereIn('ix_regions.id', $ids));
        }
        if (filled($p['sector'] ?? null) && $sector = Sector::where('slug', $p['sector'])->first()) {
            $q->whereHas('sectors', fn ($s) => $s->where('ix_sectors.id', $sector->id));
        }
        if (filled($p['type'] ?? null) && isset(Organisation::TYPES[$p['type']])) {
            $q->where('type', $p['type']);
        }
        if (filled($p['grade'] ?? null)) {
            $p['grade'] === 'unrated' ? $q->whereNull('overall_score')
                : (isset(Scoring::GRADES[$p['grade']]) ? $q->where('overall_grade', $p['grade']) : null);
        }
        if (is_numeric($p['min'] ?? null)) {
            $q->where('overall_score', '>=', (float) $p['min']);
        }
        if (is_numeric($p['max'] ?? null)) {
            $q->where('overall_score', '<=', (float) $p['max']);
        }

        return match ($p['sort'] ?? 'score') {
            'name' => $q->orderBy('name'),
            'recent' => $q->orderByRaw('last_reviewed_at is null')->orderByDesc('last_reviewed_at')->orderBy('name'),
            default => $q->orderByRaw('overall_score is null')->orderByDesc('overall_score')->orderBy('name'),
        };
    }
}
