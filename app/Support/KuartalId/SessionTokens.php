<?php

namespace App\Support\KuartalId;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Keeps the Kuartal ID access/refresh tokens for the current login in
 * the session, encrypted with APP_KEY (the session payload itself is
 * not encrypted by default), so App\Http\Middleware\
 * RefreshKuartalIdEntitlements can re-check entitlements at
 * /oauth/userinfo without sending the person back through sign-in.
 *
 * Tokens live only as long as the session: a session restored from a
 * "remember me" cookie has none, which forces a fresh Kuartal ID sign-in
 * before entitlement-gated pages.
 */
class SessionTokens
{
    public const KEY = 'kuartal_id.tokens';

    public const CHECKED_AT_KEY = 'kuartal_id.entitlements_checked_at';

    /** @param  array<string, mixed>  $tokenResponse */
    public static function store(Session $session, array $tokenResponse, ?string $previousRefreshToken = null): void
    {
        $session->put(self::KEY, Crypt::encryptString(json_encode([
            'access_token' => (string) $tokenResponse['access_token'],
            'refresh_token' => $tokenResponse['refresh_token'] ?? $previousRefreshToken,
            'expires_at' => time() + (int) ($tokenResponse['expires_in'] ?? 3600),
        ])));
    }

    /** @return array{access_token: string, refresh_token: ?string, expires_at: int}|null */
    public static function get(Session $session): ?array
    {
        $encrypted = $session->get(self::KEY);

        if (! is_string($encrypted)) {
            return null;
        }

        try {
            $tokens = json_decode(Crypt::decryptString($encrypted), true);
        } catch (Throwable) {
            return null;
        }

        return is_array($tokens) && is_string($tokens['access_token'] ?? null) ? $tokens : null;
    }

    public static function markChecked(Session $session): void
    {
        $session->put(self::CHECKED_AT_KEY, time());
    }

    public static function checkedAt(Session $session): int
    {
        return (int) $session->get(self::CHECKED_AT_KEY, 0);
    }
}
