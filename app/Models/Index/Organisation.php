<?php

namespace App\Models\Index;

use App\Support\ImpactIndex\Scoring;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Organisation extends Model
{
    protected $table = 'ix_organisations';

    protected $guarded = ['id'];

    protected $casts = [
        'last_reviewed_at' => 'date', 'overall_score' => 'float',
        'programme_pct' => 'float', 'admin_pct' => 'float', 'fundraising_pct' => 'float',
    ];

    public const TYPES = [
        'ngo' => 'NGO', 'charity' => 'Charity', 'social_enterprise' => 'Social enterprise',
        'foundation' => 'Foundation', 'cooperative' => 'Cooperative',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function sectors()
    {
        return $this->belongsToMany(Sector::class, 'ix_organisation_sector', 'organisation_id', 'sector_id')->orderBy('sort');
    }

    public function regions()
    {
        return $this->belongsToMany(Region::class, 'ix_organisation_region', 'organisation_id', 'region_id')->orderBy('name');
    }

    public function financials()
    {
        return $this->hasMany(Financial::class)->orderByDesc('year');
    }

    public function documents()
    {
        return $this->hasMany(Document::class)->orderByDesc('year')->orderBy('title');
    }

    public function pillarScores()
    {
        return $this->hasMany(PillarScore::class);
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'ix_campaign_organisation', 'organisation_id', 'campaign_id');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? Str::headline((string) $this->type);
    }

    public function isRated(): bool
    {
        return $this->overall_score !== null;
    }

    public static function uniqueSlug(string $name, ?int $ignore = null): string
    {
        $base = Str::slug($name) ?: 'organisation';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** Recompute and cache overall score, grade and confidence from published pillars. */
    public function recalculate(): void
    {
        $pillars = Pillar::ordered();
        $scores = $this->pillarScores()->with(['criteria', 'evidence'])->get()->keyBy('pillar_id');
        $result = Scoring::overall($pillars, $scores);
        $this->forceFill([
            'overall_score' => $result['score'],
            'overall_grade' => $result['score'] === null ? null : Scoring::grade($result['score']),
            'confidence' => $result['confidence'],
            'methodology_version' => $result['score'] === null ? null : Setting::methodologyVersion(),
        ])->save();
    }
}
