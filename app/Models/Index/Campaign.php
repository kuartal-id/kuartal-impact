<?php

namespace App\Models\Index;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $table = 'ix_campaigns';

    protected $guarded = ['id'];

    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function organisations()
    {
        return $this->belongsToMany(Organisation::class, 'ix_campaign_organisation', 'campaign_id', 'organisation_id');
    }

    /** Running now: status active and today inside the optional start/end window. */
    public function isActive(): bool
    {
        $today = now()->startOfDay();

        return $this->status === 'active'
            && (! $this->starts_at || $this->starts_at->lte($today))
            && (! $this->ends_at || $this->ends_at->gte($today));
    }

    public function scopeActive($q)
    {
        $today = now()->toDateString();

        return $q->where('status', 'active')
            ->where(fn ($w) => $w->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today))
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today))
            ->orderBy('sort')->orderBy('id');
    }

    /** Retired, or past its end date. */
    public function scopeEnded($q)
    {
        $today = now()->toDateString();

        return $q->where(fn ($w) => $w->where('status', 'retired')->orWhereDate('ends_at', '<', $today))->orderByDesc('ends_at')->orderBy('title');
    }

    public static function activeSafe()
    {
        try {
            return static::active()->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }
}
