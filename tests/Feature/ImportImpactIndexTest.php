<?php

namespace Tests\Feature;

use App\Models\Index\Organisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportImpactIndexTest extends TestCase
{
    use RefreshDatabase;

    private function file(array $items): string
    {
        $p = tempnam(sys_get_temp_dir(), 'ix').'.json';
        file_put_contents($p, json_encode($items));

        return $p;
    }

    private function pillar(int $score): array
    {
        return ['criteria' => [['label' => 'Criterion', 'score' => $score, 'evidence_url' => 'https://example.org/report']],
            'evidence' => [['title' => 'Report', 'url' => 'https://example.org/report']]];
    }

    public function test_imports_and_scores_with_three_pillars(): void
    {
        $f = $this->file([[
            'name' => 'Test Foundation', 'type' => 'foundation', 'sectors' => ['oceans-coasts'], 'regions' => ['bali'],
            'last_reviewed_at' => '2026-10-14',
            'pillars' => ['impact' => $this->pillar(80), 'transparency' => $this->pillar(70), 'governance' => $this->pillar(60)],
        ], ['name' => 'Thin Co', 'type' => 'company', 'pillars' => ['impact' => $this->pillar(70)]]]);

        $this->artisan('index:import', ['file' => $f])->assertSuccessful();

        $org = Organisation::where('slug', 'test-foundation')->first();
        // (80*30 + 70*20 + 60*15) / 65 = 72.3
        $this->assertSame(72.3, $org->overall_score);
        $this->assertSame('B', $org->overall_grade);
        $this->assertSame('medium', $org->confidence); // 3/5 pillars x all criteria evidenced = 0.6
        $this->assertSame('published', $org->status);
        $this->assertSame(['bali'], $org->regions->pluck('slug')->all());
        $this->assertNull(Organisation::where('slug', 'thin-co')->first()->overall_score);

        // Re-import updates in place, not duplicates.
        $this->artisan('index:import', ['file' => $f])->assertSuccessful();
        $this->assertSame(2, Organisation::count());
    }

    public function test_invalid_file_writes_nothing(): void
    {
        $f = $this->file([
            ['name' => 'Good', 'type' => 'ngo', 'pillars' => ['impact' => $this->pillar(80)]],
            ['name' => 'Bad', 'type' => 'ngo', 'sectors' => ['no-such-sector']],
            ['name' => 'No evidence', 'type' => 'ngo', 'pillars' => ['impact' => ['criteria' => [['label' => 'x', 'score' => 50]]]]],
        ]);

        $this->artisan('index:import', ['file' => $f])->assertFailed();
        $this->assertSame(0, Organisation::count());
    }
}
