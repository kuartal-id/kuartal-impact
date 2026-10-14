<?php

namespace Tests\Unit;

use App\Support\SafeRedirect;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://kuartalimpact.org']);
    }

    /** @return array<string, array{0: mixed, 1: ?string}> */
    public static function targets(): array
    {
        return [
            'relative path' => ['/portal', '/portal'],
            'relative with query' => ['/jobs?team=eng#top', '/jobs?team=eng#top'],
            'same origin absolute' => ['https://kuartalimpact.org/admin/jobs', '/admin/jobs'],
            'same origin explicit port' => ['https://kuartalimpact.org:443/admin', '/admin'],
            'same origin uppercase host' => ['https://KUARTALIMPACT.org/x', '/x'],
            'userinfo trick' => ['https://kuartalimpact.org@evil.com', null],
            'userinfo trick with path' => ['https://kuartalimpact.org@evil.com/portal', null],
            'suffix domain' => ['https://kuartalimpact.org.evil.com/', null],
            'other port' => ['https://kuartalimpact.org:8443/', null],
            'http downgrade' => ['http://kuartalimpact.org/', null],
            'protocol relative' => ['//evil.com', null],
            'slash backslash' => ['/\\evil.com', null],
            'backslash anywhere' => ['/foo\\bar', null],
            'tab trick' => ["/\t/evil.com", null],
            'javascript' => ['javascript:alert(1)', null],
            'data' => ['data:text/html,hi', null],
            'relative no slash' => ['portal', null],
            'empty' => ['', null],
            'null' => [null, null],
            'array' => [['/portal'], null],
        ];
    }

    #[DataProvider('targets')]
    public function test_sanitize(mixed $target, ?string $expected): void
    {
        $this->assertSame($expected, SafeRedirect::sanitize($target));
    }
}
