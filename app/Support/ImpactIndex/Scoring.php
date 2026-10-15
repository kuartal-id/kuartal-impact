<?php

namespace App\Support\ImpactIndex;

use Illuminate\Support\Collection;

/**
 * Kuartal Impact Index scoring maths. Plain and documented on /impact-index/methodology.
 *
 * - Pillar score: weighted mean of its sub-criteria (each 0..100, weight 1..10), one decimal.
 * - A pillar counts only when published, and it can be published only with >= 1 evidence source.
 * - Overall: weighted mean of published pillar scores using the admin pillar weights, re-normalised
 *   over the pillars that are published. Needs at least MIN_PILLARS published pillars, else "Not yet rated".
 * - Confidence: evidence completeness = (published pillars / all pillars) x (criteria with an evidence link / all criteria).
 */
class Scoring
{
    public const MIN_PILLARS = 3;

    /** Lower bounds, best first. */
    public const GRADES = ['A+' => 90, 'A' => 80, 'B' => 70, 'C' => 60, 'D' => 0];

    public const CONFIDENCE = ['high' => 0.8, 'medium' => 0.5, 'low' => 0.0];

    /** @param array<int, array{score:int|float, weight?:int|float}> $criteria */
    public static function pillarScore(array $criteria): ?float
    {
        $sum = 0.0;
        $weights = 0.0;
        foreach ($criteria as $c) {
            $w = max(0, (float) ($c['weight'] ?? 1));
            $s = min(100, max(0, (float) $c['score']));
            $sum += $s * $w;
            $weights += $w;
        }

        return $weights > 0 ? round($sum / $weights, 1) : null;
    }

    public static function grade(float $score): string
    {
        foreach (self::GRADES as $g => $min) {
            if ($score >= $min) {
                return $g;
            }
        }

        return 'D';
    }

    /** Score range for a grade band, e.g. 'A' => [80, 89.9]. */
    public static function band(string $grade): ?array
    {
        $keys = array_keys(self::GRADES);
        $i = array_search($grade, $keys, true);
        if ($i === false) {
            return null;
        }
        $min = self::GRADES[$grade];
        $max = $i === 0 ? 100 : self::GRADES[$keys[$i - 1]] - 0.1;

        return [$min, $max];
    }

    /** A pillar may be published only with at least one evidence source (listed source or criterion link). */
    public static function canPublish(int $evidenceCount, int $criteriaCount): bool
    {
        return $evidenceCount >= 1 && $criteriaCount >= 1;
    }

    /**
     * @param  Collection  $pillars  Pillar models (id, weight)
     * @param  Collection  $scores  PillarScore models keyed by pillar_id (criteria + evidence loaded)
     * @return array{score: ?float, confidence: ?string, completeness: float, published: int}
     */
    public static function overall(Collection $pillars, Collection $scores): array
    {
        $sum = 0.0;
        $weights = 0.0;
        $published = 0;
        $criteria = 0;
        $withEvidence = 0;
        foreach ($pillars as $p) {
            $ps = $scores->get($p->id);
            if (! $ps || ! $ps->published) {
                continue;
            }
            $score = $ps->score();
            if ($score === null || ! self::canPublish(count($ps->sources()), $ps->criteria->count())) {
                continue;
            }
            $published++;
            $sum += $score * $p->weight;
            $weights += $p->weight;
            $criteria += $ps->criteria->count();
            $withEvidence += $ps->criteria->filter(fn ($c) => filled($c->evidence_url))->count();
        }

        $completeness = self::completeness($published, $pillars->count(), $withEvidence, $criteria);
        if ($published < self::MIN_PILLARS || $weights <= 0) {
            return ['score' => null, 'confidence' => null, 'completeness' => $completeness, 'published' => $published];
        }

        return ['score' => round($sum / $weights, 1), 'confidence' => self::confidence($completeness),
            'completeness' => $completeness, 'published' => $published];
    }

    public static function completeness(int $published, int $totalPillars, int $criteriaWithEvidence, int $criteria): float
    {
        if ($totalPillars === 0 || $criteria === 0) {
            return 0.0;
        }

        return round(($published / $totalPillars) * ($criteriaWithEvidence / $criteria), 2);
    }

    public static function confidence(float $completeness): string
    {
        foreach (self::CONFIDENCE as $label => $min) {
            if ($completeness >= $min) {
                return $label;
            }
        }

        return 'low';
    }
}
