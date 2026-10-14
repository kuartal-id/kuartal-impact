<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The repository root is the web root on Hostinger Git deploy, so /.htaccess is the only
 * thing between the internet and .env, the SQLite database, logs and CVs. This evaluates
 * its rules offline (PCRE ~ Apache's regex engine). The same deny list is checked against
 * real Apache in CI and against the live site by bin/check-webroot.sh.
 */
class WebRootHtaccessTest extends TestCase
{
    /** @return array{rules: list<array{0:string,1:bool}>, files: string, acme: string} */
    private static function rules(): array
    {
        $htaccess = file_get_contents(dirname(__DIR__, 2).'/.htaccess');

        preg_match_all('/^\s*RewriteRule\s+(\S+)\s+-\s+\[([^\]]*F[^\]]*)\]/m', $htaccess, $m, PREG_SET_ORDER);
        $rules = array_map(fn ($r) => [$r[1], str_contains($r[2], 'NC')], $m);

        preg_match('/<FilesMatch "([^"]+)">/', $htaccess, $files);
        preg_match('/^\s*RewriteRule\s+(\S+)\s+-\s+\[L\]/m', $htaccess, $acme);

        return ['rules' => $rules, 'files' => $files[1] ?? '', 'acme' => $acme[1] ?? ''];
    }

    /** Would Apache refuse this URL path (no leading slash, as in per-directory context)? */
    private static function denied(string $path): bool
    {
        $r = self::rules();
        $path = ltrim(rawurldecode($path), '/');

        if ($r['acme'] !== '' && preg_match('#'.$r['acme'].'#', $path)) {
            return false;
        }

        foreach ($r['rules'] as [$pattern, $nocase]) {
            if (preg_match('#'.$pattern.'#'.($nocase ? 'i' : ''), $path)) {
                return true;
            }
        }

        $basename = basename($path === '' ? '/' : $path);

        return $basename !== '' && (bool) preg_match('#'.$r['files'].'#', $basename);
    }

    public static function deniedPaths(): array
    {
        $paths = [
            '.env', '.env.save', '.env.backup', '.env.production', '.env.example', 'public/.env',
            '.git/config', '.git/HEAD', '.git/objects/ab/cdef', '.github/workflows/deploy.yml', '.ai/check.py',
            '.gitignore', '.htaccess', 'public/.htaccess',
            'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'artisan', 'phpunit.xml', 'vite.config.js',
            'README.md', 'AGENTS.md', 'docs/HANDOFF.md', 'docs/DEPLOY.md',
            'app/Models/User.php', 'APP/Models/User.php', 'app', 'bootstrap/app.php', 'bootstrap/deploy-build.json',
            'bootstrap/cache/config.php', 'config/app.php', 'database/database.sqlite', 'database/migrations/x.php',
            'routes/web.php', 'resources/views/welcome.blade.php', 'tests/TestCase.php', 'bin/check-webroot.sh',
            'storage/logs/laravel.log', 'storage/app/private/resumes/cv.pdf', 'storage/app/deployed_sha',
            'storage/framework/sessions/abc', 'Storage/logs/laravel.log', 'vendor/autoload.php', 'Vendor/autoload.php',
            'node_modules/vite/package.json', 'laravel.log', 'public/laravel.log', 'error_log', 'public/error_log',
            'backup.sql', 'db.sqlite', 'database.sqlite3', 'x.env', 'id_rsa.pem', 'oauth-private.key', '.env%00', 'other.xml', 'public/feed.xml', 'sitemap.xml.bak',
        ];

        return array_combine($paths, array_map(fn ($p) => [$p], $paths));
    }

    #[DataProvider('deniedPaths')]
    public function test_path_is_refused(string $path): void
    {
        $this->assertTrue(self::denied($path), "/{$path} must be refused by /.htaccess");
    }

    public static function publicPaths(): array
    {
        $paths = [
            '', 'jobs', 'jobs/senior-engineer', 'jobs/senior-engineer/apply', 'portal', 'admin/applications/1/resume',
            'auth/kuartal-id/callback', 'up', 'favicon.ico', 'favicon-32.png', 'images/logo.svg',
            'build/manifest.json', 'build/assets/app-D_8d2bBI.js', 'build/assets/app-abc.css', 'robots.txt', 'sitemap.xml',
            'apply/1/thank-you', 'faq', 'teams', 'contact', 'why-kuartal', 'life-at-kuartal', 'employment-info',
            '.well-known/acme-challenge/token123',
        ];

        return array_combine(array_map(fn ($p) => "/{$p}", $paths), array_map(fn ($p) => [$p], $paths));
    }

    #[DataProvider('publicPaths')]
    public function test_public_path_is_served(string $path): void
    {
        $this->assertFalse(self::denied($path), "/{$path} must not be refused by /.htaccess");
    }

    public function test_every_application_route_is_reachable(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        preg_match_all("/Route::(?:get|post|put|patch|delete|match|any|view|redirect)\(\s*'([^']*)'/", $routes, $m);

        $this->assertNotEmpty($m[1]);

        foreach ($m[1] as $uri) {
            $sample = preg_replace('/\{[^}]+\}/', 'x', ltrim($uri, '/'));
            $this->assertFalse(self::denied($sample), "Route /{$uri} would be blocked by /.htaccess");
        }
    }

    public function test_rewrites_everything_else_into_public_and_disables_listings(): void
    {
        $htaccess = file_get_contents(dirname(__DIR__, 2).'/.htaccess');

        $this->assertMatchesRegularExpression('/^Options -Indexes/m', $htaccess);
        $this->assertStringContainsString('RewriteCond %{REQUEST_URI} !^/public/', $htaccess);
        $this->assertMatchesRegularExpression('#^\s*RewriteRule \^\(\.\*\)\$ public/\$1 \[L\]#m', $htaccess);
        $this->assertStringContainsString('<IfModule !mod_rewrite.c>', $htaccess, 'Without mod_rewrite everything must be denied.');
    }

    public function test_data_directories_carry_their_own_deny_all(): void
    {
        foreach (['storage', 'database', 'bootstrap'] as $dir) {
            $file = dirname(__DIR__, 2)."/{$dir}/.htaccess";
            $this->assertFileExists($file);
            $this->assertStringContainsString('Require all denied', file_get_contents($file));
        }
    }
}
