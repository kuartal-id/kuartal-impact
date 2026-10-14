<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-off import of the content the previous WordPress kuartalimpact.org
 * published (home page, captured 2026-10-14): the founder quote and the
 * how-we-invest / team paragraphs, verbatim; plus the four running funds
 * named by the owner (one-line descriptions only, no figures). Old portfolio
 * entries are NOT imported (owner: archive only). Admins edit or
 * unpublish these in /admin/content like any other entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [
            // The four running programmes, as funds (owner brief 2026-10-14). One line each, as given; no figures.
            ['type' => 'fund', 'group' => 'environment', 'slug' => 'mangrow-fund', 'title' => 'Mangrow Fund',
                'subtitle' => 'Mangrove restoration in NTB.', 'body' => null, 'url' => null, 'location' => 'Nusa Tenggara Barat, Indonesia', 'sort' => 10],
            ['type' => 'fund', 'group' => 'environment', 'slug' => 're-coral-fund', 'title' => 'Re-Coral Fund',
                'subtitle' => 'Coral restoration.', 'body' => null, 'url' => null, 'location' => null, 'sort' => 20],
            ['type' => 'fund', 'group' => 'climate', 'slug' => 're-energize-fund', 'title' => 'Re-Energize Fund',
                'subtitle' => 'Low-carbon energy solutions for communities and small businesses.', 'body' => null, 'url' => null, 'location' => null, 'sort' => 30],
            ['type' => 'fund', 'group' => 'social', 'slug' => 'healing-fund', 'title' => 'Healing Fund',
                'subtitle' => 'Healthcare and medicine access for communities in need.', 'body' => null, 'url' => null, 'location' => null, 'sort' => 40],
            ['type' => 'block', 'group' => 'home.quote', 'title' => 'Home: founder quote', 'subtitle' => null,
                'body' => 'As the world teeters on the brink of major crises, it becomes crucial that we understand where our money is going. Kuartal Impact\'s entire existence is on helping investors ensure that their capital generates profit with purpose.',
                'url' => null, 'sort' => 0],
            ['type' => 'block', 'group' => 'home.quote_by', 'title' => 'Home: quote attribution', 'subtitle' => null,
                'body' => 'Diemas Sukma Hawkins, Founder of Kuartal Impact', 'url' => null, 'sort' => 0],
            ['type' => 'block', 'group' => 'about.how_we_invest', 'title' => 'About: how we invest', 'subtitle' => null,
                'body' => 'Kuartal Impact invests in extraordinary Indonesian businesses, partnering with their leaders to achieve growth, profitability, and lasting impact — with capital that generates profit with purpose.',
                'url' => null, 'sort' => 0],
            ['type' => 'block', 'group' => 'about.team_intro', 'title' => 'About: team introduction', 'subtitle' => null,
                'body' => 'A team of investment professionals and operators grounded in Indonesia\'s markets, driven by one goal: capital that reaches the people and communities who need it most.',
                'url' => null, 'sort' => 0],
        ];
        foreach ($rows as $r) {
            $r += ['slug' => null, 'location' => null];
            $exists = DB::table('site_entries')->where('type', $r['type'])
                ->where($r['type'] === 'block' ? 'group' : 'title', $r['type'] === 'block' ? $r['group'] : $r['title'])->exists();
            if (! $exists) {
                DB::table('site_entries')->insert($r + ['is_published' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        // Content may have been edited by admins since; leave it.
    }
};
