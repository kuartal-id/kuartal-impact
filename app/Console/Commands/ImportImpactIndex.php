<?php

namespace App\Console\Commands;

use App\Models\Index\Organisation;
use App\Models\Index\Pillar;
use App\Models\Index\Region;
use App\Models\Index\Sector;
use App\Support\ImpactIndex\Scoring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Loads researched organisations into the Impact Index from a JSON file.
 *
 *   php artisan index:import path/to/organisations.json [--dry-run]
 *
 * File: a JSON array of organisations. Each item:
 *   name, slug?, type (Organisation::TYPES key), website?, logo_url?, country?, headquarters?, founded_year?,
 *   summary?, theory_of_change?, funding_sources?, reviewer_notes?, last_reviewed_at? (Y-m-d), status? (draft|published),
 *   sectors: [sector slug], regions: [region slug],
 *   pillars: { <pillar key>: { notes?, reviewed_at?, criteria: [{label, score 0-100, weight? 1-10, evidence_url?}],
 *                              evidence: [{title, url}] } }
 *
 * Rules (same as the admin): a pillar listed in the file is published only if it has at least one criterion and one
 * evidence source; pillars not listed stay unrated. Organisations are matched by slug and updated in place; their
 * pillar scores are replaced by the file's. Everything is validated first; any error aborts with nothing written.
 */
class ImportImpactIndex extends Command
{
    protected $signature = 'index:import {file : JSON file to load} {--dry-run : Validate and report only}';

    protected $description = 'Import researched organisations, pillar scores and evidence into the Impact Index';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }
        $items = json_decode((string) file_get_contents($path), true);
        if (! is_array($items) || ! array_is_list($items)) {
            $this->error('The file must contain a JSON array of organisations.');

            return self::FAILURE;
        }

        $pillars = Pillar::ordered()->keyBy('key');
        $sectors = Sector::pluck('id', 'slug');
        $regions = Region::pluck('id', 'slug');
        $errors = [];
        $slugs = [];
        foreach ($items as $i => $it) {
            $at = '#'.($i + 1).' '.($it['name'] ?? '?');
            if (! filled($it['name'] ?? null)) {
                $errors[] = "{$at}: name is required.";
            }
            if (! isset(Organisation::TYPES[$it['type'] ?? ''])) {
                $errors[] = "{$at}: unknown type \"".($it['type'] ?? '').'".';
            }
            $slug = $it['slug'] ?? \Illuminate\Support\Str::slug((string) ($it['name'] ?? ''));
            if (isset($slugs[$slug])) {
                $errors[] = "{$at}: duplicate slug {$slug}.";
            }
            $slugs[$slug] = true;
            foreach (['website', 'logo_url'] as $f) {
                if (filled($it[$f] ?? null) && ! self::isUrl($it[$f])) {
                    $errors[] = "{$at}: {$f} must be an http(s) URL.";
                }
            }
            if (! in_array($it['status'] ?? 'published', ['draft', 'published'], true)) {
                $errors[] = "{$at}: status must be draft or published.";
            }
            foreach ((array) ($it['sectors'] ?? []) as $s) {
                if (! isset($sectors[$s])) {
                    $errors[] = "{$at}: unknown sector \"{$s}\".";
                }
            }
            foreach ((array) ($it['regions'] ?? []) as $r) {
                if (! isset($regions[$r])) {
                    $errors[] = "{$at}: unknown region \"{$r}\".";
                }
            }
            foreach ((array) ($it['pillars'] ?? []) as $key => $p) {
                if (! isset($pillars[$key])) {
                    $errors[] = "{$at}: unknown pillar \"{$key}\".";

                    continue;
                }
                $criteria = (array) ($p['criteria'] ?? []);
                foreach ($criteria as $c) {
                    $score = $c['score'] ?? null;
                    $w = $c['weight'] ?? 1;
                    if (! filled($c['label'] ?? null) || ! is_numeric($score) || $score < 0 || $score > 100
                        || ! is_int($w) || $w < 1 || $w > 10 || (filled($c['evidence_url'] ?? null) && ! self::isUrl($c['evidence_url']))) {
                        $errors[] = "{$at} / {$key}: each criterion needs label, score 0-100, weight 1-10 and an http(s) evidence_url if given.";
                    }
                }
                foreach ((array) ($p['evidence'] ?? []) as $e) {
                    if (! filled($e['title'] ?? null) || ! self::isUrl((string) ($e['url'] ?? ''))) {
                        $errors[] = "{$at} / {$key}: each evidence item needs a title and an http(s) url.";
                    }
                }
                $sources = count((array) ($p['evidence'] ?? [])) + count(array_filter($criteria, fn ($c) => filled($c['evidence_url'] ?? null)));
                if (! Scoring::canPublish($sources, count($criteria))) {
                    $errors[] = "{$at} / {$key}: a rated pillar needs at least one criterion and one evidence source.";
                }
            }
        }
        if ($errors) {
            foreach ($errors as $e) {
                $this->error($e);
            }
            $this->error(count($errors).' problem(s); nothing was written.');

            return self::FAILURE;
        }
        if ($this->option('dry-run')) {
            $this->info(count($items).' organisation(s) valid. Dry run: nothing written.');

            return self::SUCCESS;
        }

        $created = $updated = 0;
        try {
            DB::transaction(function () use ($items, $pillars, $sectors, $regions, &$created, &$updated) {
                foreach ($items as $it) {
                    $slug = $it['slug'] ?? \Illuminate\Support\Str::slug($it['name']);
                    $org = Organisation::firstOrNew(['slug' => $slug]);
                    $org->exists ? $updated++ : $created++;
                    $org->fill(array_intersect_key($it, array_flip(['name', 'type', 'website', 'logo_url', 'country', 'headquarters',
                        'founded_year', 'summary', 'theory_of_change', 'funding_sources', 'reviewer_notes', 'last_reviewed_at'])));
                    $org->status = $it['status'] ?? 'published';
                    $org->save();
                    $org->sectors()->sync(array_map(fn ($s) => $sectors[$s], (array) ($it['sectors'] ?? [])));
                    $org->regions()->sync(array_map(fn ($r) => $regions[$r], (array) ($it['regions'] ?? [])));
                    $org->pillarScores()->delete();
                    foreach ((array) ($it['pillars'] ?? []) as $key => $p) {
                        $ps = $org->pillarScores()->create(['pillar_id' => $pillars[$key]->id, 'published' => true,
                            'notes' => $p['notes'] ?? null, 'reviewed_at' => $p['reviewed_at'] ?? ($it['last_reviewed_at'] ?? null)]);
                        foreach (array_values((array) ($p['criteria'] ?? [])) as $n => $c) {
                            $ps->criteria()->create(['label' => mb_substr($c['label'], 0, 250), 'score' => (int) round((float) $c['score']),
                                'weight' => $c['weight'] ?? 1, 'evidence_url' => $c['evidence_url'] ?? null, 'sort' => $n]);
                        }
                        foreach ((array) ($p['evidence'] ?? []) as $e) {
                            $ps->evidence()->create(['title' => mb_substr($e['title'], 0, 250), 'url' => $e['url']]);
                        }
                    }
                    $org->recalculate();
                }
            });
        } catch (Throwable $e) {
            $this->error('Import failed and was rolled back: '.$e->getMessage());

            return self::FAILURE;
        }

        $rated = Organisation::whereIn('slug', array_keys($slugs))->whereNotNull('overall_score')->count();
        $this->info("Imported: {$created} created, {$updated} updated; {$rated} with an overall score.");

        return self::SUCCESS;
    }

    private static function isUrl(mixed $u): bool
    {
        return is_string($u) && filter_var($u, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $u);
    }
}
