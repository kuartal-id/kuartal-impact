<?php

namespace App\Support;

/**
 * Decides whether a post-login "send me back to X" target (the
 * ?redirect= query string or the Referer header) is safe to redirect to.
 *
 * Only two shapes are accepted:
 *   - a relative path starting with exactly one "/" (not "//" or "/\",
 *     which browsers treat as protocol-relative URLs to another host);
 *   - an absolute http(s) URL whose scheme, host and port exactly match
 *     APP_URL's (parsed, not string-prefix compared -- so
 *     "https://kuartalimpact.org@evil.com" and
 *     "https://kuartalimpact.org.evil.com" are rejected).
 *
 * Anything else -- other hosts, userinfo, backslashes, control
 * characters, javascript:/data: URLs -- returns null.
 */
class SafeRedirect
{
    /** Returns a same-origin relative path ("/portal?x=1"), or null if unsafe. */
    public static function sanitize(mixed $target): ?string
    {
        if (! is_string($target) || $target === '' || strlen($target) > 2000) {
            return null;
        }

        // Backslashes and control/whitespace characters are normalised
        // inconsistently by browsers ("/\evil.com", "/\tevil.com"...).
        if (str_contains($target, '\\') || preg_match('/[\x00-\x20\x7F]/', $target)) {
            return null;
        }

        if (str_starts_with($target, '/')) {
            return str_starts_with($target, '//') ? null : $target;
        }

        $parts = parse_url($target);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])
            || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $sameOrigin = false;

        // This app serves one origin (APP_URL);
        // a target on either one is ours.
        foreach ([config('app.url')] as $origin) {
            $app = parse_url((string) $origin);

            if (is_array($app) && isset($app['scheme'], $app['host'])
                && in_array($scheme, ['http', 'https'], true)
                && $scheme === strtolower($app['scheme'])
                && strtolower($parts['host']) === strtolower($app['host'])
                && self::port($parts) === self::port($app)) {
                $sameOrigin = true;
            }
        }

        if (! $sameOrigin) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        if (! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        return $path
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }

    /** @param  array<string, mixed>  $url */
    private static function port(array $url): int
    {
        return (int) ($url['port'] ?? (strtolower($url['scheme']) === 'https' ? 443 : 80));
    }
}
