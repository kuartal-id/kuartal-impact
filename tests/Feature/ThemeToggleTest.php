<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * kuartal.id-style light/dark mode (replaces the old neon/dark/neutral switcher):
 * one round toggle, the shared 'kuartal-theme' key applied before first paint,
 * and the owner's logos (black in light mode, neon in dark mode).
 */
class ThemeToggleTest extends TestCase
{
    use RefreshDatabase;

    private const MAIN = 'https://kuartalimpact.test';

    public function test_public_pages_have_a_light_dark_toggle_and_no_three_way_switcher(): void
    {
        foreach (['/', '/about', '/contact', '/programmes/mangrow-fund'] as $path) {
            $html = $this->get(self::MAIN.$path)->assertOk()->getContent();
            $this->assertStringContainsString('data-theme-toggle', $html, "$path lacks the dark-mode toggle");
            $this->assertStringContainsString('aria-label="Toggle dark mode"', $html);
            $this->assertStringContainsString('data-search-toggle', $html, "$path lacks the search button");
            $this->assertStringNotContainsString('data-theme-set=', $html);
            $this->assertStringNotContainsString('aria-label="Colour theme"', $html);
        }
    }

    public function test_shared_theme_is_applied_before_the_stylesheet(): void
    {
        $html = $this->get(self::MAIN.'/')->getContent();
        $head = substr($html, 0, strpos($html, '</head>'));
        $this->assertStringContainsString("localStorage.getItem('kuartal-theme')", $head);
        $this->assertStringContainsString("prefers-color-scheme: dark", $head);
        $this->assertStringNotContainsString('kuartal-impact-theme', $head);
        $boot = strpos($head, "localStorage.getItem('kuartal-theme')");
        if (($css = strpos($head, 'rel="stylesheet"')) !== false) {
            $this->assertLessThan($css, $boot);
        }
        $this->assertStringNotContainsString('class="dark"', substr($html, 0, strpos($html, '<head>')), 'no forced dark mode; follows kuartal.id');
    }

    public function test_toggle_script_uses_the_kuartal_shared_key(): void
    {
        $js = file_get_contents(resource_path('js/theme-toggle.js'));
        $this->assertStringContainsString("'kuartal-theme'", $js);
        $this->assertStringContainsString('[data-theme-toggle]', $js);
        $this->assertFileDoesNotExist(resource_path('js/theme-boot.js'));
    }

    public function test_logo_versions_and_favicon_come_from_the_owner_artwork(): void
    {
        $html = $this->get(self::MAIN.'/')->getContent();
        $this->assertStringContainsString('images/logo/kuartal-impact-black.webp', $html);
        $this->assertStringContainsString('images/logo/kuartal-impact-lime.webp', $html);
        $this->assertStringContainsString('images/logo/kuartal-impact-white.webp', $html, 'white logo over the hero footage');
        foreach (['black', 'lime', 'white'] as $v) {
            foreach (['png', 'webp'] as $ext) {
                $this->assertFileExists(public_path("images/logo/kuartal-impact-$v.$ext"));
            }
        }
        [$w, $h] = getimagesize(public_path('images/logo/kuartal-impact-white.png'));
        $this->assertSame([1283, 713], [$w, $h], 'logo should be trimmed to the artwork');
        foreach (['favicon.ico', 'favicon.svg', 'favicon-32.png', 'favicon-180.png', 'favicon-512.png'] as $f) {
            $this->assertFileExists(public_path($f));
        }
    }

    public function test_hero_uses_real_kuartal_footage_only(): void
    {
        $html = $this->get(self::MAIN.'/')->getContent();
        $this->assertStringContainsString('https://kuartal.id/videos/kuartal-hero.mp4', $html);
        $this->assertFileExists(public_path('images/hero-poster.jpg'));
    }
}
