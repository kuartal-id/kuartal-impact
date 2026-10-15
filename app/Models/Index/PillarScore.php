<?php

namespace App\Models\Index;

use App\Support\ImpactIndex\Scoring;
use Illuminate\Database\Eloquent\Model;

class PillarScore extends Model
{
    protected $table = 'ix_pillar_scores';

    protected $guarded = ['id'];

    protected $casts = ['published' => 'boolean', 'reviewed_at' => 'date'];

    public function pillar()
    {
        return $this->belongsTo(Pillar::class);
    }

    public function criteria()
    {
        return $this->hasMany(Criterion::class)->orderBy('sort')->orderBy('id');
    }

    public function evidence()
    {
        return $this->hasMany(Evidence::class);
    }

    public function score(): ?float
    {
        return Scoring::pillarScore($this->criteria->map(fn ($c) => ['score' => $c->score, 'weight' => $c->weight])->all());
    }

    public function grade(): ?string
    {
        $s = $this->score();

        return $s === null ? null : Scoring::grade($s);
    }

    /** Every evidence link behind this pillar: listed sources plus per-criterion links. */
    public function sources(): array
    {
        $out = [];
        foreach ($this->evidence as $e) {
            $out[$e->url] = $e->title;
        }
        foreach ($this->criteria as $c) {
            if ($c->evidence_url && ! isset($out[$c->evidence_url])) {
                $out[$c->evidence_url] = $c->label;
            }
        }

        return $out;
    }
}
