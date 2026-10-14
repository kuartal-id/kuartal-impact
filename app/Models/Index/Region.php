<?php

namespace App\Models\Index;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** Hierarchical: country > province > area. */
class Region extends Model
{
    protected $table = 'ix_regions';

    protected $guarded = ['id'];

    public const LEVELS = ['country' => 'Country', 'province' => 'Province', 'area' => 'Area'];

    public function parent()
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Region::class, 'parent_id')->orderBy('name');
    }

    public function organisations()
    {
        return $this->belongsToMany(Organisation::class, 'ix_organisation_region', 'region_id', 'organisation_id');
    }

    /** This region's id plus every descendant's id (filtering by a country includes its provinces and areas). */
    public function selfAndDescendantIds(): array
    {
        $all = static::query()->get(['id', 'parent_id'])->groupBy('parent_id');
        $ids = [$this->id];
        $queue = [$this->id];
        while ($queue) {
            $next = array_shift($queue);
            foreach ($all->get($next, collect()) as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $ids;
    }

    public function path(): string
    {
        $parts = [$this->name];
        $p = $this->parent;
        while ($p) {
            array_unshift($parts, $p->name);
            $p = $p->parent;
        }

        return implode(' › ', $parts);
    }

    /** Flat list in tree order with depth, for <select>s. */
    public static function tree(): Collection
    {
        $all = static::orderBy('name')->get()->groupBy(fn ($r) => $r->parent_id ?? 0);
        $out = collect();
        $walk = function ($parentId, $depth) use (&$walk, $all, $out) {
            foreach ($all->get($parentId, collect()) as $r) {
                $r->depth = $depth;
                $out->push($r);
                $walk($r->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $out;
    }
}
