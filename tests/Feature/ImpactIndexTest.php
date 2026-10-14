<?php

namespace Tests\Feature;

use App\Models\Index\Campaign;
use App\Models\Index\Organisation;
use App\Models\Index\Pillar;
use App\Models\Index\PillarScore;
use App\Models\Index\Region;
use App\Models\Index\Sector;
use App\Models\User;
use App\Support\ImpactIndex\OrganisationSearch;
use App\Support\ImpactIndex\Scoring;
use App\Support\KuartalId\SessionTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpactIndexTest extends TestCase
{
    use RefreshDatabase;

    private const MAIN = 'https://kuartalimpact.test';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kuartal_id.entitlements_refresh_minutes' => 600]);
    }

    private function asAdmin(): static
    {
        return $this->actingAs(User::factory()->create(['entitlements' => ['kuartalimpact.admin']]))
            ->withSession([SessionTokens::CHECKED_AT_KEY => time()]);
    }

    private function org(array $a = []): Organisation
    {
        static $n = 0;
        $n++;

        return Organisation::create($a + ['name' => "Example Org {$n}", 'slug' => "example-org-{$n}", 'type' => 'ngo', 'status' => 'published']);
    }

    /** @param array<string, array{0: array, 1: bool}> $byKey pillar key => [criteria [[score, weight, url]], published] */
    private function score(Organisation $org, array $byKey): Organisation
    {
        foreach ($byKey as $key => [$criteria, $published]) {
            $ps = PillarScore::create(['organisation_id' => $org->id, 'pillar_id' => Pillar::where('key', $key)->value('id'), 'published' => $published]);
            foreach ($criteria as $i => [$s, $w, $url]) {
                $ps->criteria()->create(['label' => "c{$i}", 'score' => $s, 'weight' => $w, 'evidence_url' => $url]);
            }
        }
        $org->recalculate();

        return $org->fresh();
    }

    // ---- scoring maths -------------------------------------------------

    public function test_pillar_score_is_weighted_mean_and_clamped(): void
    {
        $this->assertSame(75.0, Scoring::pillarScore([['score' => 60, 'weight' => 1], ['score' => 90, 'weight' => 1]]));
        $this->assertSame(82.5, Scoring::pillarScore([['score' => 60, 'weight' => 1], ['score' => 90, 'weight' => 3]]));
        $this->assertSame(100.0, Scoring::pillarScore([['score' => 150]]));
        $this->assertNull(Scoring::pillarScore([]));
    }

    public function test_grades_and_bands(): void
    {
        $this->assertSame('A+', Scoring::grade(90));
        $this->assertSame('A', Scoring::grade(89.9));
        $this->assertSame('B', Scoring::grade(70));
        $this->assertSame('C', Scoring::grade(60));
        $this->assertSame('D', Scoring::grade(59.9));
        $this->assertSame([80, 89.9], Scoring::band('A'));
        $this->assertSame([0, 59.9], Scoring::band('D'));
    }

    public function test_seeded_weights_sum_to_100_and_no_orgs_seeded(): void
    {
        $this->assertSame(100, (int) Pillar::sum('weight'));
        $this->assertSame(5, Pillar::count());
        $this->assertSame(0, Organisation::count());
        $this->assertSame(38, Region::where('level', 'province')->whereHas('parent', fn ($q) => $q->where('slug', 'indonesia'))->count());
    }

    public function test_overall_is_weighted_and_renormalised_over_published_pillars(): void
    {
        // impact 80 (w30), financial 70 (w20), transparency 90 (w20) => 5600/70 = 80.0
        $org = $this->score($this->org(), [
            'impact' => [[[80, 1, 'https://e.test/1']], true],
            'financial' => [[[70, 1, 'https://e.test/2']], true],
            'transparency' => [[[90, 1, 'https://e.test/3']], true],
            'governance' => [[[10, 1, 'https://e.test/4']], false], // unpublished: ignored
        ]);
        $this->assertSame(80.0, $org->overall_score);
        $this->assertSame('A', $org->overall_grade);
        $this->assertSame('medium', $org->confidence); // 3/5 x 1.0 = 0.6
        $this->assertSame('1.0', $org->methodology_version);
    }

    public function test_confidence_reflects_evidence_completeness(): void
    {
        $all = fn ($url) => [[[70, 1, $url], [70, 1, 'https://e.test/x']], true];
        $org = $this->score($this->org(), ['impact' => $all('https://a'), 'financial' => $all('https://b'), 'transparency' => $all('https://c'),
            'governance' => $all('https://d'), 'community' => $all('https://e')]);
        $this->assertSame('high', $org->confidence);
        $this->assertSame('low', Scoring::confidence(Scoring::completeness(3, 5, 2, 6)));
    }

    public function test_fewer_than_three_published_pillars_is_not_yet_rated(): void
    {
        $org = $this->score($this->org(), ['impact' => [[[80, 1, 'https://e.test/1']], true], 'financial' => [[[70, 1, 'https://e.test/2']], true]]);
        $this->assertNull($org->overall_score);
        $this->get(self::MAIN.'/impact-index/org/'.$org->slug)->assertOk()->assertSee('Not yet rated');
    }

    public function test_weight_change_recalculates(): void
    {
        $org = $this->score($this->org(), ['impact' => [[[100, 1, 'https://a']], true], 'financial' => [[[50, 1, 'https://b']], true], 'transparency' => [[[50, 1, 'https://c']], true]]);
        $this->assertSame(71.4, $org->overall_score); // (100*30 + 50*20 + 50*20) / 70 = 71.4
        $weights = Pillar::ordered()->mapWithKeys(fn ($p) => [$p->id => ['impact' => 60, 'financial' => 10, 'transparency' => 10, 'governance' => 10, 'community' => 10][$p->key]])->all();
        $this->asAdmin()->put(self::MAIN.'/admin/index/weights', ['version' => '1.1', 'weights' => $weights])->assertSessionHasNoErrors();
        $org->refresh();
        $this->assertSame(87.5, $org->overall_score); // (6000+500+500)/80
        $this->assertSame('1.1', $org->methodology_version);

        $bad = array_map(fn () => 10, $weights);
        $this->asAdmin()->put(self::MAIN.'/admin/index/weights', ['version' => '1.2', 'weights' => $bad])->assertSessionHasErrors('weights');
    }

    // ---- publish rules -------------------------------------------------

    public function test_pillar_cannot_be_published_without_evidence(): void
    {
        $org = $this->org();
        $pid = Pillar::where('key', 'impact')->value('id');
        $this->asAdmin()->put(self::MAIN."/admin/index/organisations/{$org->id}/scores", ['p' => [$pid => ['criteria' => 'Outcomes | 80', 'published' => 1]]])
            ->assertSessionHasErrors("p.{$pid}.published");
        $this->assertSame(0, PillarScore::count());

        $this->asAdmin()->put(self::MAIN."/admin/index/organisations/{$org->id}/scores", ['p' => [$pid => [
            'criteria' => 'Outcomes | 80 | 2', 'evidence' => 'Annual report | https://example.org/report.pdf', 'published' => 1]]])
            ->assertSessionHasNoErrors();
        $ps = PillarScore::first();
        $this->assertTrue($ps->published);
        $this->assertSame(80.0, $ps->score());
    }

    public function test_unpublished_pillar_and_draft_org_hidden(): void
    {
        $draft = $this->org(['name' => 'Hidden Draft Org', 'status' => 'draft']);
        $this->get(self::MAIN.'/impact-index/org/'.$draft->slug)->assertNotFound();
        $this->get(self::MAIN.'/impact-index')->assertDontSee('Hidden Draft Org');

        $org = $this->score($this->org(), ['governance' => [[[12, 1, 'https://secret.test/gov']], false]]);
        $this->get(self::MAIN.'/impact-index/org/'.$org->slug)->assertOk()->assertDontSee('secret.test/gov');
    }

    public function test_published_score_shows_evidence_sources(): void
    {
        $org = $this->score($this->org(), ['impact' => [[[80, 1, 'https://ev.test/impact']], true], 'financial' => [[[70, 1, 'https://ev.test/fin']], true],
            'transparency' => [[[90, 1, 'https://ev.test/tr']], true]]);
        $this->get(self::MAIN.'/impact-index/org/'.$org->slug)->assertOk()
            ->assertSee('https://ev.test/impact')->assertSee('80.0')->assertSee('Methodology v1.0')->assertSee('not financial');
    }

    // ---- search & filters ----------------------------------------------

    public function test_search_filters(): void
    {
        $health = Sector::where('slug', 'health')->first();
        $ntt = Region::where('slug', 'nusa-tenggara-timur')->first();
        $area = Region::create(['parent_id' => $ntt->id, 'level' => 'area', 'name' => 'Kupang', 'slug' => 'kupang']);

        $a = $this->score($this->org(['name' => 'Alpha Clinic Example', 'type' => 'charity']), [
            'impact' => [[[95, 1, 'https://a']], true], 'financial' => [[[95, 1, 'https://a']], true], 'transparency' => [[[95, 1, 'https://a']], true]]);
        $a->sectors()->attach($health);
        $a->regions()->attach($area);
        $b = $this->score($this->org(['name' => 'Beta Forest Example', 'type' => 'foundation']), [
            'impact' => [[[65, 1, 'https://b']], true], 'financial' => [[[65, 1, 'https://b']], true], 'transparency' => [[[65, 1, 'https://b']], true]]);
        $c = $this->org(['name' => 'Gamma Unrated Example', 'type' => 'cooperative']);

        $names = fn (array $p) => OrganisationSearch::query($p)->pluck('name')->all();
        $this->assertSame(['Alpha Clinic Example'], $names(['q' => 'clinic']));
        $this->assertSame(['Alpha Clinic Example'], $names(['region' => 'indonesia'])); // descendant area counts
        $this->assertSame(['Alpha Clinic Example'], $names(['sector' => 'health']));
        $this->assertSame(['Beta Forest Example'], $names(['type' => 'foundation']));
        $this->assertSame(['Alpha Clinic Example'], $names(['grade' => 'A+']));
        $this->assertSame(['Gamma Unrated Example'], $names(['grade' => 'unrated']));
        $this->assertSame(['Beta Forest Example'], $names(['min' => 60, 'max' => 70]));
        $this->assertSame(['Alpha Clinic Example', 'Beta Forest Example', 'Gamma Unrated Example'], $names(['sort' => 'score']));
        $this->assertSame(['Alpha Clinic Example', 'Beta Forest Example', 'Gamma Unrated Example'], $names(['sort' => 'name']));
        $this->assertSame([], $names(['q' => '%']));

        $this->get(self::MAIN.'/impact-index?sector=health&sort=name')->assertOk()->assertSee('Alpha Clinic Example')->assertDontSee('Beta Forest Example');
        $this->get(self::MAIN.'/impact-index/sector/health')->assertOk()->assertSee('Alpha Clinic Example');
        $this->get(self::MAIN.'/impact-index/region/nusa-tenggara-timur')->assertOk()->assertSee('Alpha Clinic Example');
        $this->get(self::MAIN.'/impact-index/rankings')->assertOk()->assertSee('Alpha Clinic Example')->assertDontSee('Gamma Unrated Example');
    }

    // ---- public pages & campaigns --------------------------------------

    public function test_public_pages_and_empty_states(): void
    {
        $this->get(self::MAIN.'/index')->assertRedirect('/impact-index');
        $this->get(self::MAIN.'/impact-index')->assertOk()->assertSee('The Index is being built')->assertSee('hello@kuartal.id');
        $this->get(self::MAIN.'/impact-index/methodology')->assertOk()->assertSee('Impact &amp; Outcomes', false)->assertSee('30%')->assertSee('v1.0');
        $this->get(self::MAIN.'/impact-index/rankings')->assertOk()->assertSee('No rated organisations yet');
        $this->get(self::MAIN.'/')->assertOk()->assertSee('Impact Index');
    }

    public function test_seeded_campaigns_active_on_home(): void
    {
        $this->assertSame(['Save Kalimantan', 'Save Sumatera', 'NTT Recovery', 'Palestine'], Campaign::orderBy('sort')->pluck('title')->all());
        $home = $this->get(self::MAIN.'/')->assertOk();
        foreach (['save-kalimantan', 'save-sumatera', 'ntt-recovery', 'palestine'] as $slug) {
            $home->assertSee('/impact-index/campaigns/'.$slug);
            $this->get(self::MAIN.'/impact-index/campaigns/'.$slug)->assertOk()->assertSee('How to give safely')->assertDontSee('This campaign has ended');
        }
    }

    public function test_campaign_retirement(): void
    {
        $c = Campaign::where('slug', 'ntt-recovery')->first();
        $org = $this->org(['name' => 'Linked Example Org']);
        $c->organisations()->attach($org);
        $this->get(self::MAIN.'/impact-index/campaigns/ntt-recovery')->assertSee('Linked Example Org');

        $c->update(['status' => 'retired']);
        $this->get(self::MAIN.'/')->assertOk()->assertDontSee('/impact-index/campaigns/ntt-recovery');
        $this->get(self::MAIN.'/impact-index/campaigns/ntt-recovery')->assertOk()->assertSee('This campaign has ended')->assertSee('Linked Example Org');
        $this->get(self::MAIN.'/impact-index/campaigns')->assertOk()->assertSee('Past campaigns')->assertSee('NTT Recovery');

        // Past end date also ends it.
        Campaign::where('slug', 'palestine')->update(['ends_at' => now()->subDay()->toDateString()]);
        $this->assertFalse(Campaign::where('slug', 'palestine')->first()->isActive());
        $this->get(self::MAIN.'/')->assertDontSee('/impact-index/campaigns/palestine');
    }

    // ---- admin ---------------------------------------------------------

    public function test_admin_requires_entitlement(): void
    {
        $this->get(self::MAIN.'/admin/index/organisations')->assertRedirect();
        $this->actingAs(User::factory()->create(['entitlements' => []]))->withSession([SessionTokens::CHECKED_AT_KEY => time()])
            ->get(self::MAIN.'/admin/index/organisations')->assertForbidden();
    }

    public function test_admin_crud_organisation_and_campaign(): void
    {
        $health = Sector::where('slug', 'health')->first();
        $bali = Region::where('slug', 'bali')->first();
        $this->asAdmin()->get(self::MAIN.'/admin/index/organisations')->assertOk();
        $this->asAdmin()->get(self::MAIN.'/admin/index/organisations/new')->assertOk();
        $this->asAdmin()->post(self::MAIN.'/admin/index/organisations', [
            'name' => 'Example Admin Org', 'type' => 'social_enterprise', 'status' => 'published', 'website' => 'https://example.org',
            'programme_pct' => 80, 'admin_pct' => 15, 'fundraising_pct' => 5, 'sectors' => [$health->id], 'regions' => [$bali->id],
            'financials' => '2025 | IDR | 1000 | 900 | 700 | https://example.org/fs', 'documents' => 'audit | 2025 | Audit | https://example.org/a.pdf',
        ])->assertSessionHasNoErrors();
        $org = Organisation::where('name', 'Example Admin Org')->firstOrFail();
        $this->assertCount(1, $org->financials);
        $this->assertCount(1, $org->documents);
        $this->get(self::MAIN.'/impact-index/org/'.$org->slug)->assertOk()->assertSee('Programmes')->assertSee('Bali')->assertSee('Not yet rated');

        $this->asAdmin()->post(self::MAIN.'/admin/index/organisations', ['name' => 'X', 'type' => 'ngo', 'status' => 'draft', 'website' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('website');
        $this->asAdmin()->post(self::MAIN.'/admin/index/organisations', ['name' => 'Y', 'type' => 'ngo', 'status' => 'draft', 'programme_pct' => 90, 'admin_pct' => 20])
            ->assertSessionHasErrors('programme_pct');

        $c = Campaign::where('slug', 'palestine')->first();
        $this->asAdmin()->get(self::MAIN."/admin/index/campaigns/{$c->id}/edit")->assertOk();
        $this->asAdmin()->put(self::MAIN."/admin/index/campaigns/{$c->id}", ['title' => 'Palestine', 'status' => 'retired', 'organisations' => [$org->id]])->assertSessionHasNoErrors();
        $this->assertSame('retired', $c->fresh()->status);
        $this->asAdmin()->get(self::MAIN.'/admin/index/settings')->assertOk();
        $this->asAdmin()->post(self::MAIN.'/admin/index/regions', ['name' => 'Denpasar', 'level' => 'area', 'parent_id' => $bali->id])->assertSessionHasNoErrors();
        $this->assertSame('Indonesia › Bali › Denpasar', Region::where('slug', 'denpasar')->first()->path());
    }
}
