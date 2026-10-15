<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A newsroom post. Visible publicly when status is published or scheduled
 * and published_at has passed (a scheduled post goes live by itself).
 */
class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'cover_media_id', 'category', 'tags', 'status',
        'published_at', 'meta_title', 'meta_description', 'author_id',
    ];

    protected function casts(): array
    {
        return ['tags' => 'array', 'published_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->whereIn('status', ['published', 'scheduled'])
            ->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isLive(): bool
    {
        return in_array($this->status, ['published', 'scheduled'], true)
            && $this->published_at && $this->published_at->lte(now());
    }

    /** draft | scheduled (future) | published (live). */
    public function state(): string
    {
        if ($this->status === 'draft') {
            return 'draft';
        }

        return $this->isLive() ? 'published' : 'scheduled';
    }

    public function categoryLabel(): string
    {
        return config('kuartalimpact.post_categories.'.$this->category, Str::headline($this->category));
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 220));
    }

    public function summary(): string
    {
        return $this->excerpt ?: Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->body))), 220);
    }

    public static function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'post';
        $base = Str::limit($base, 150, '');
        $slug = $base;
        $n = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
