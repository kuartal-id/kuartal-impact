<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SiteEntry;
use App\Models\User;
use App\Support\KuartalId\SessionTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    private const MAIN = 'https://kuartalimpact.test';

    private const PAGES = ['/', '/about', '/focus-areas', '/portfolio', '/programmes', '/contact', '/newsroom',
        '/programmes/mangrow-fund', '/programmes/re-coral-fund', '/programmes/re-energize-fund', '/programmes/healing-fund'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kuartal_id.entitlements_refresh_minutes' => 600]);
    }

    private function admin(): static
    {
        return $this->actingAs(User::factory()->create(['entitlements' => ['kuartalimpact.admin']]))
            ->withSession([SessionTokens::CHECKED_AT_KEY => time()]);
    }

    public function test_every_public_page_renders_without_placeholder_text(): void
    {
        foreach (self::PAGES as $path) {
            $html = $this->get(self::MAIN.$path)->assertOk()->getContent();
            foreach (['[SUPPLY', 'lorem', 'ipsum', 'TODO', 'coming soon', 'placeholder', 'TBD', 'Investor Relations', 'Kuartal Group Newsroom'] as $bad) {
                $this->assertStringNotContainsStringIgnoringCase($bad, strip_tags($html), "{$path} shows \"{$bad}\"");
            }
        }
    }

    public function test_the_four_funds_are_seeded_with_their_own_pages(): void
    {
        $this->assertSame(['Mangrow Fund', 'Re-Coral Fund', 'Re-Energize Fund', 'Healing Fund'], SiteEntry::funds()->pluck('title')->all());
        $this->get(self::MAIN.'/programmes')->assertSee('Mangrow Fund')->assertSee('Healing Fund');
        $this->get(self::MAIN.'/programmes/mangrow-fund')->assertSee('Mangrove restoration in NTB.')
            ->assertSee('Figures pending verification')->assertSee('No projects listed yet');
        $this->get(self::MAIN.'/programmes/nope')->assertNotFound();
    }

    public function test_old_portfolio_is_not_on_the_new_site(): void
    {
        foreach (['/', '/portfolio'] as $p) {
            $this->get(self::MAIN.$p)->assertDontSee('PHCHC')->assertDontSee('Kuartal Energy');
        }
        $this->get(self::MAIN.'/portfolio')->assertSee('Portfolio listings to be published');
    }

    public function test_no_invented_numbers_on_home(): void
    {
        $html = strip_tags($this->get(self::MAIN.'/')->getContent());
        foreach (['288M', '1.05M', 'People Reached', '5.0%'] as $bad) {
            $this->assertStringNotContainsString($bad, $html);
        }
        $this->get(self::MAIN.'/')->assertSee('No posts yet')->assertSee('Diemas Sukma Hawkins');
    }

    public function test_fund_figures_and_projects_come_from_admin_entries(): void
    {
        SiteEntry::create(['type' => 'metric', 'group' => 'mangrow-fund', 'title' => 'Hectares planted', 'subtitle' => '42 ha', 'is_published' => true]);
        SiteEntry::create(['type' => 'metric', 'group' => 'mangrow-fund', 'title' => 'Hidden figure', 'subtitle' => '9', 'is_published' => false]);
        SiteEntry::create(['type' => 'portfolio', 'group' => 'mangrow-fund', 'title' => 'Test Project', 'is_published' => true]);
        $this->get(self::MAIN.'/programmes/mangrow-fund')->assertSee('Hectares planted')->assertSee('42 ha')
            ->assertDontSee('Hidden figure')->assertSee('Test Project')->assertDontSee('Figures pending verification');
        $this->get(self::MAIN.'/portfolio')->assertSee('Test Project')->assertSee('Mangrow Fund');
        $this->get(self::MAIN.'/programmes/re-coral-fund')->assertDontSee('42 ha');
    }

    public function test_admin_can_edit_a_fund_and_add_a_figure(): void
    {
        $fund = SiteEntry::where('slug', 're-coral-fund')->firstOrFail();
        $this->admin()->put(self::MAIN.'/admin/content/'.$fund->id, [
            'type' => 'fund', 'slug' => 're-coral-fund', 'group' => 'environment', 'title' => 'Re-Coral Fund',
            'subtitle' => 'Coral restoration.', 'body' => "Paragraph one.\n\nParagraph two.", 'sort' => 20, 'is_published' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->get(self::MAIN.'/programmes/re-coral-fund')->assertSee('Paragraph two.');

        $this->admin()->post(self::MAIN.'/admin/content', ['type' => 'metric', 'group' => 'nope', 'title' => 'X', 'subtitle' => '1'])
            ->assertSessionHasErrors('group');
        $this->admin()->post(self::MAIN.'/admin/content', ['type' => 'metric', 'group' => 're-coral-fund', 'title' => 'Reef sites', 'subtitle' => '3', 'is_published' => 1])
            ->assertSessionHasNoErrors();
        $this->get(self::MAIN.'/programmes/re-coral-fund')->assertSee('Reef sites');

        $this->admin()->post(self::MAIN.'/admin/content', ['type' => 'fund', 'slug' => 'healing-fund', 'title' => 'Dup'])
            ->assertSessionHasErrors('slug');
    }

    public function test_admin_pages_render_and_require_entitlement(): void
    {
        Post::create(['title' => 'Draft post', 'slug' => 'draft-post', 'category' => 'announcement', 'status' => 'draft']);
        $this->admin()->get(self::MAIN.'/admin')->assertOk()->assertSee('Still to fill in')->assertSee('Fund figures: Mangrow Fund');
        foreach (['/admin/posts', '/admin/media', '/admin/content', '/admin/content/new?type=fund', '/admin/content/new?type=metric'] as $p) {
            $this->admin()->get(self::MAIN.$p)->assertOk();
        }
        $this->actingAs(User::factory()->create(['entitlements' => []]))->withSession([SessionTokens::CHECKED_AT_KEY => time()])
            ->get(self::MAIN.'/admin')->assertForbidden();
    }

    public function test_contact_emails_and_legacy_redirects(): void
    {
        $this->get(self::MAIN.'/contact')->assertSee('hello@kuartal.id')->assertSee('ir@kuartal.id')->assertSee('media@kuartal.id');
        $this->get(self::MAIN.'/how-we-invest')->assertRedirect();
        $this->get(self::MAIN.'/our-team')->assertRedirect();
    }

    public function test_mobile_menu_and_theme_sync_markup(): void
    {
        $html = $this->get(self::MAIN.'/')->getContent();
        $this->assertStringContainsString('data-nav-toggle', $html);
        $this->assertStringContainsString('aria-controls="k-mobile-nav"', $html);
        $this->assertStringContainsString("localStorage.getItem('kuartal-theme')", $html);
    }

    public function test_sitemap_robots_and_404(): void
    {
        $this->get(self::MAIN.'/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset', false)->assertSee(self::MAIN.'/about', false);
        $this->get(self::MAIN.'/no-such-page')->assertNotFound()
            ->assertSee("We couldn&#039;t find that page", false)->assertSee('Back to home');
        $this->assertStringContainsString('Sitemap: https://kuartalimpact.org/sitemap.xml', file_get_contents(public_path('robots.txt')));
    }

    public function test_no_cream_full_width_bands_in_light_mode(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertMatchesRegularExpression('/\.k-band \{ @apply [^}]*\bbg-white\b/', $css);
        $this->assertMatchesRegularExpression('/\.k-page-hero \{ @apply [^}]*\bbg-white\b/', $css);
        foreach (glob(resource_path('views/{,*/,*/*/}*.blade.php'), GLOB_BRACE) as $view) {
            $this->assertDoesNotMatchRegularExpression('/<(section|footer|body|div class="border-t)[^>]*\bbg-(gray-bg|cream)\b/', file_get_contents($view), $view);
        }
    }
}
