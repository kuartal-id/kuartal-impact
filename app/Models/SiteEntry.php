<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Admin-managed public content: funds (programmes), portfolio projects, team, partners,
 * impact figures and text blocks. Every public section
 * that depends on facts only the owner can supply reads from here and
 * shows an empty state when nothing is entered (never invented content).
 */
class SiteEntry extends Model
{
    protected $fillable = [
        'type', 'group', 'slug', 'title', 'subtitle', 'body', 'url', 'location', 'starts_at',
        'media_id', 'sort', 'is_published',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'is_published' => 'boolean', 'sort' => 'integer'];
    }

    public function scopeVisible(Builder $q, string $type): Builder
    {
        return $q->where('type', $type)->where('is_published', true)->orderBy('sort')->orderBy('id');
    }

    /** @return Collection<int, static> */
    public static function listed(string $type, ?string $group = null): Collection
    {
        return static::visible($type)->when($group, fn ($q) => $q->where('group', $group))
            ->with('media')->get();
    }

    /** A published text block's body (keys in config kuartalimpact.blocks), or null. */
    public static function block(string $key): ?string
    {
        $body = static::where('type', 'block')->where('group', $key)->where('is_published', true)->value('body');

        return filled($body) ? $body : null;
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /** Focus-area label for this entry's group, if it is one. */
    public function focusArea(): ?array
    {
        return config('kuartalimpact.focus_areas.'.$this->group);
    }

    /** Published funds, in order. */
    public static function funds(): Collection
    {
        return static::listed('fund');
    }

    /** Published impact figures for one fund (metric entries grouped by fund slug). */
    public function metrics(): Collection
    {
        return static::listed('metric', $this->slug);
    }

    /** Published projects backed by this fund (portfolio entries grouped by fund slug). */
    public function projects(): Collection
    {
        return static::listed('portfolio', $this->slug);
    }

    public function link(): ?string
    {
        return $this->url ?: null;
    }
}
