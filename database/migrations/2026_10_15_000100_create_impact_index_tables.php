<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kuartal Impact Index: organisations, sectors, regions, scoring pillars with
 * sub-criteria + evidence, financials, documents, campaigns.
 *
 * Seeds ONLY reference data: the five pillars and their default weights,
 * a sector list, Indonesia + its 38 provinces, and four campaign shells with
 * neutral wording. No organisations, no scores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ix_pillars', function (Blueprint $t) {
            $t->id();
            $t->string('key', 40)->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->unsignedInteger('weight')->default(20);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('ix_settings', function (Blueprint $t) {
            $t->string('key', 60)->primary();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('ix_sectors', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('ix_regions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('ix_regions')->nullOnDelete();
            $t->string('level', 12); // country | province | area
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
            $t->index(['parent_id', 'level']);
        });

        Schema::create('ix_organisations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('type', 24); // ngo | charity | social_enterprise | foundation | cooperative
            $t->string('logo_url', 500)->nullable();
            $t->string('country')->nullable();
            $t->string('headquarters')->nullable();
            $t->unsignedSmallInteger('founded_year')->nullable();
            $t->string('website', 500)->nullable();
            $t->string('registration_number')->nullable();
            $t->text('summary')->nullable();           // what they do
            $t->text('theory_of_change')->nullable();  // how they work
            $t->text('funding_sources')->nullable();
            $t->decimal('programme_pct', 5, 2)->nullable();
            $t->decimal('admin_pct', 5, 2)->nullable();
            $t->decimal('fundraising_pct', 5, 2)->nullable();
            $t->string('spending_source_url', 500)->nullable();
            $t->string('donation_url', 500)->nullable();
            $t->date('last_reviewed_at')->nullable();
            $t->text('reviewer_notes')->nullable();
            $t->string('status', 12)->default('draft'); // draft | published
            // Cached results of App\Support\ImpactIndex\Scoring (recomputed on every save).
            $t->decimal('overall_score', 5, 1)->nullable();
            $t->string('overall_grade', 3)->nullable();
            $t->string('confidence', 8)->nullable();
            $t->string('methodology_version', 20)->nullable();
            $t->timestamps();
            $t->index('name');
            $t->index(['status', 'overall_score']);
            $t->index('type');
            $t->index('overall_grade');
        });

        Schema::create('ix_organisation_sector', function (Blueprint $t) {
            $t->foreignId('organisation_id')->constrained('ix_organisations')->cascadeOnDelete();
            $t->foreignId('sector_id')->constrained('ix_sectors')->cascadeOnDelete();
            $t->primary(['organisation_id', 'sector_id']);
            $t->index('sector_id');
        });

        Schema::create('ix_organisation_region', function (Blueprint $t) {
            $t->foreignId('organisation_id')->constrained('ix_organisations')->cascadeOnDelete();
            $t->foreignId('region_id')->constrained('ix_regions')->cascadeOnDelete();
            $t->primary(['organisation_id', 'region_id']);
            $t->index('region_id');
        });

        Schema::create('ix_financials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organisation_id')->constrained('ix_organisations')->cascadeOnDelete();
            $t->unsignedSmallInteger('year');
            $t->string('currency', 3)->default('IDR');
            $t->decimal('income', 20, 2)->nullable();
            $t->decimal('expenditure', 20, 2)->nullable();
            $t->decimal('programme_spend', 20, 2)->nullable();
            $t->string('source_url', 500)->nullable();
            $t->timestamps();
        });

        Schema::create('ix_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organisation_id')->constrained('ix_organisations')->cascadeOnDelete();
            $t->string('kind', 12); // audit | report
            $t->string('title');
            $t->unsignedSmallInteger('year')->nullable();
            $t->string('url', 500);
            $t->timestamps();
        });

        Schema::create('ix_pillar_scores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organisation_id')->constrained('ix_organisations')->cascadeOnDelete();
            $t->foreignId('pillar_id')->constrained('ix_pillars')->cascadeOnDelete();
            $t->boolean('published')->default(false);
            $t->text('notes')->nullable();
            $t->date('reviewed_at')->nullable();
            $t->timestamps();
            $t->unique(['organisation_id', 'pillar_id']);
        });

        Schema::create('ix_criteria', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pillar_score_id')->constrained('ix_pillar_scores')->cascadeOnDelete();
            $t->string('label');
            $t->unsignedTinyInteger('score'); // 0..100
            $t->unsignedTinyInteger('weight')->default(1);
            $t->string('evidence_url', 500)->nullable();
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('ix_evidence', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pillar_score_id')->constrained('ix_pillar_scores')->cascadeOnDelete();
            $t->string('title');
            $t->string('url', 500);
            $t->timestamps();
        });

        Schema::create('ix_campaigns', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('intro')->nullable();
            $t->text('give_safely')->nullable();
            $t->string('status', 10)->default('active'); // active | retired
            $t->date('starts_at')->nullable();
            $t->date('ends_at')->nullable();
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('ix_campaign_organisation', function (Blueprint $t) {
            $t->foreignId('campaign_id')->constrained('ix_campaigns')->cascadeOnDelete();
            $t->foreignId('organisation_id')->constrained('ix_organisations')->cascadeOnDelete();
            $t->primary(['campaign_id', 'organisation_id']);
        });

        $now = now();
        $pillars = [
            ['impact', 'Impact & Outcomes', 'Evidence that programmes achieve the change they aim for: measured outcomes, not only activities.', 30],
            ['financial', 'Financial Health & Efficiency', 'How money is raised and used: programme share, reserves, cost control and audited accounts.', 20],
            ['transparency', 'Transparency & Disclosure', 'What the organisation publishes: reports, financial statements, legal registration, how donations are used.', 20],
            ['governance', 'Governance & Accountability', 'Independent board, conflict-of-interest policy, complaints and safeguarding processes.', 15],
            ['community', 'Community & Local Leadership', 'Whether affected communities shape and lead the work, and local staff and partners hold decisions.', 15],
        ];
        foreach ($pillars as $i => [$key, $name, $desc, $w]) {
            DB::table('ix_pillars')->insert(['key' => $key, 'name' => $name, 'description' => $desc, 'weight' => $w, 'sort' => $i, 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('ix_settings')->insert(['key' => 'methodology_version', 'value' => '1.0', 'created_at' => $now, 'updated_at' => $now]);

        $sectors = ['Climate & Environment', 'Forests & Biodiversity', 'Oceans & Coasts', 'Disaster Relief & Recovery', 'Humanitarian Aid',
            'Health', 'Education', 'Poverty & Livelihoods', 'Water & Sanitation', 'Food & Agriculture', 'Children & Youth',
            'Women & Gender Equality', 'Disability & Inclusion', 'Human Rights', 'Animal Welfare', 'Clean Energy'];
        foreach ($sectors as $i => $s) {
            DB::table('ix_sectors')->insert(['name' => $s, 'slug' => \Illuminate\Support\Str::slug($s), 'sort' => $i, 'created_at' => $now, 'updated_at' => $now]);
        }

        $id = DB::table('ix_regions')->insertGetId(['level' => 'country', 'name' => 'Indonesia', 'slug' => 'indonesia', 'created_at' => $now, 'updated_at' => $now]);
        $provinces = ['Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau', 'Jambi', 'Sumatera Selatan',
            'Kepulauan Bangka Belitung', 'Bengkulu', 'Lampung', 'DKI Jakarta', 'Jawa Barat', 'Banten', 'Jawa Tengah',
            'DI Yogyakarta', 'Jawa Timur', 'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Kalimantan Barat',
            'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara', 'Sulawesi Utara', 'Gorontalo',
            'Sulawesi Tengah', 'Sulawesi Barat', 'Sulawesi Selatan', 'Sulawesi Tenggara', 'Maluku', 'Maluku Utara',
            'Papua', 'Papua Barat', 'Papua Barat Daya', 'Papua Tengah', 'Papua Pegunungan', 'Papua Selatan'];
        foreach ($provinces as $p) {
            DB::table('ix_regions')->insert(['parent_id' => $id, 'level' => 'province', 'name' => $p, 'slug' => \Illuminate\Support\Str::slug($p), 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('ix_regions')->insert(['level' => 'country', 'name' => 'Palestine', 'slug' => 'palestine', 'created_at' => $now, 'updated_at' => $now]);

        $safe = 'Give through an organisation\'s own website or a payment page it links to. Check its legal registration and that '
            .'it publishes reports and accounts. Be wary of personal bank accounts, unsolicited messages and urgent pressure to pay. '
            .'Prefer unrestricted donations to organisations already working in the area.';
        $campaigns = [
            ['Save Kalimantan', 'save-kalimantan', 'Forest and peatland fires in Kalimantan cause seasonal haze across the region. This page lists organisations working on fire prevention, peatland restoration, forest protection and community health in Kalimantan.'],
            ['Save Sumatera', 'save-sumatera', 'Forest and peatland fires in Sumatera cause seasonal haze across the region. This page lists organisations working on fire prevention, peatland restoration, forest protection and community health in Sumatera.'],
            ['NTT Recovery', 'ntt-recovery', 'Communities in East Nusa Tenggara (Nusa Tenggara Timur) are rebuilding after disasters. This page lists organisations supporting recovery, shelter, water, livelihoods and preparedness in NTT.'],
            ['Palestine', 'palestine', 'This page lists organisations providing humanitarian relief to people in Palestine, such as food, water, shelter and medical care.'],
        ];
        foreach ($campaigns as $i => [$title, $slug, $intro]) {
            DB::table('ix_campaigns')->insert(['title' => $title, 'slug' => $slug, 'intro' => $intro, 'give_safely' => $safe,
                'status' => 'active', 'starts_at' => $now->toDateString(), 'sort' => $i, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        foreach (['ix_campaign_organisation', 'ix_campaigns', 'ix_evidence', 'ix_criteria', 'ix_pillar_scores', 'ix_documents',
            'ix_financials', 'ix_organisation_region', 'ix_organisation_sector', 'ix_organisations', 'ix_regions', 'ix_sectors',
            'ix_settings', 'ix_pillars'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
