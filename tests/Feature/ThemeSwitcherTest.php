<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Three themes (neon / dark / neutral): switcher markup, logo per theme, and
 * persistence. The persistence tests run the real resources/js/theme-boot.js
 * (the same code inlined before first paint) under Node with a fake localStorage.
 */
class ThemeSwitcherTest extends TestCase
{
    use RefreshDatabase;

    private const MAIN = 'https://kuartalimpact.test';

    public function test_every_public_page_has_a_three_way_switcher(): void
    {
        foreach (['/', '/about', '/programmes/mangrow-fund'] as $path) {
            $html = $this->get(self::MAIN.$path)->assertOk()->getContent();
            foreach (['neon', 'dark', 'neutral'] as $theme) {
                $this->assertStringContainsString('data-theme-set="'.$theme.'"', $html, "$path lacks $theme button");
            }
            $this->assertStringContainsString('aria-label="Colour theme"', $html);
            $this->assertStringContainsString('aria-pressed=', $html);
        }
    }

    public function test_theme_is_applied_before_paint_from_storage(): void
    {
        $html = $this->get(self::MAIN.'/')->getContent();
        $head = substr($html, 0, strpos($html, '</head>'));
        $this->assertStringContainsString('window.kuartalImpactThemes', $head);
        $this->assertStringContainsString("localStorage.getItem('kuartal-impact-theme')", $head);
        $this->assertStringContainsString("localStorage.getItem('kuartal-theme')", $head);
        // Inline boot runs before the stylesheet so there is no flash of the wrong theme.
        $boot = strpos($head, 'kuartalImpactThemes.apply') ?: strpos($head, 'T.apply(document');
        $this->assertNotFalse($boot);
        if (($css = strpos($head, 'rel="stylesheet"')) !== false) {
            $this->assertLessThan($css, $boot);
        }
    }

    public function test_logo_versions_and_favicon_come_from_the_owner_artwork(): void
    {
        $html = $this->get(self::MAIN.'/')->getContent();
        $this->assertStringContainsString('images/logo/kuartal-impact-black.webp', $html);
        $this->assertStringContainsString('images/logo/kuartal-impact-lime.webp', $html);
        foreach (['black', 'lime', 'white'] as $v) {
            foreach (['png', 'webp'] as $ext) {
                $this->assertFileExists(public_path("images/logo/kuartal-impact-$v.$ext"));
            }
        }
        $this->assertFileDoesNotExist(public_path('images/logo/source-wordmark-old-navy.png'));
        [$w, $h] = getimagesize(public_path('images/logo/kuartal-impact-white.png'));
        $this->assertSame([1283, 713], [$w, $h], 'logo should be trimmed to the artwork');
        foreach (['favicon.ico', 'favicon.svg', 'favicon-32.png', 'favicon-180.png', 'favicon-512.png'] as $f) {
            $this->assertFileExists(public_path($f));
        }
    }

    /** @return array<string, array{0: array<string, string>, 1: string}> */
    public static function resolutionCases(): array
    {
        return [
            'first visit opens dark' => [[], 'dark'],
            'stored neon is kept' => [['kuartal-impact-theme' => 'neon', 'kuartal-theme' => 'light'], 'neon'],
            'stored neutral is kept' => [['kuartal-impact-theme' => 'neutral', 'kuartal-theme' => 'light'], 'neutral'],
            'stored dark is kept' => [['kuartal-impact-theme' => 'dark', 'kuartal-theme' => 'dark'], 'dark'],
            'other Kuartal site chose light' => [['kuartal-theme' => 'light'], 'neutral'],
            'other Kuartal site chose dark' => [['kuartal-theme' => 'dark'], 'dark'],
            'shared flipped to dark wins over neon' => [['kuartal-impact-theme' => 'neon', 'kuartal-theme' => 'dark'], 'dark'],
            'shared flipped to light restores last light theme' => [['kuartal-impact-theme' => 'dark', 'kuartal-theme' => 'light', 'kuartal-impact-light' => 'neon'], 'neon'],
            'garbage values fall back' => [['kuartal-impact-theme' => 'purple', 'kuartal-theme' => 'sepia'], 'dark'],
        ];
    }

    #[DataProvider('resolutionCases')]
    public function test_stored_choice_resolves_to_a_theme(array $store, string $expected): void
    {
        $out = $this->runBoot('console.log(T.resolve(store(INIT)));', $store);
        $this->assertSame($expected, $out);
    }

    public function test_saving_persists_choice_and_syncs_shared_mode(): void
    {
        $js = <<<'JS'
            const s = store({});
            const seen = [];
            for (const t of ['neon', 'dark', 'neutral', 'neon']) { T.save(s, t); seen.push([t, s.getItem('kuartal-impact-theme'), s.getItem('kuartal-theme'), T.resolve(s)].join(':')); }
            seen.push('light=' + s.getItem('kuartal-impact-light'));
            T.save(s, 'bogus'); seen.push('after-bogus=' + T.resolve(s));
            console.log(seen.join('|'));
        JS;
        $this->assertSame(
            'neon:neon:light:neon|dark:dark:dark:dark|neutral:neutral:light:neutral|neon:neon:light:neon|light=neon|after-bogus=neon',
            $this->runBoot($js)
        );
    }

    public function test_apply_sets_attribute_dark_class_and_survives_disabled_storage(): void
    {
        $js = <<<'JS'
            const cls = new Set(); const attrs = {}; const meta = { c: '', setAttribute(k, v) { this.c = v; } };
            const doc = { documentElement: { style: {}, setAttribute(k, v) { attrs[k] = v; }, classList: { toggle(c, on) { on ? cls.add(c) : cls.delete(c); } } }, querySelector: () => meta };
            T.apply(doc, 'neutral'); const a = attrs['data-theme'] + ',' + cls.has('dark') + ',' + meta.c;
            T.apply(doc, 'dark'); const b = attrs['data-theme'] + ',' + cls.has('dark') + ',' + meta.c;
            const broken = { getItem() { throw new Error('denied'); }, setItem() { throw new Error('denied'); } };
            T.save(broken, 'neon');
            console.log([a, b, T.resolve(broken)].join('|'));
        JS;
        $this->assertSame('neutral,false,#f3eee3|dark,true,#050505|dark', $this->runBoot($js));
    }

    private function runBoot(string $body, array $init = []): string
    {
        $node = (new ExecutableFinder)->find('node');
        if (! $node) {
            $this->markTestSkipped('node is not installed');
        }
        $script = 'globalThis.window = globalThis;'."\n"
            .file_get_contents(resource_path('js/theme-boot.js'))."\n"
            .'const T = window.kuartalImpactThemes;'."\n"
            .'const INIT = '.json_encode((object) $init).';'."\n"
            .'function store(init) { const m = new Map(Object.entries(init)); return { getItem: (k) => (m.has(k) ? m.get(k) : null), setItem: (k, v) => m.set(k, String(v)) }; }'."\n"
            .$body;
        $p = new Process([$node, '-e', $script]);
        $p->mustRun();

        return trim($p->getOutput());
    }
}
