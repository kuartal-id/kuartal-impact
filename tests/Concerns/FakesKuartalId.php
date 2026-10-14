<?php

namespace Tests\Concerns;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

/**
 * Fakes Kuartal ID (id.kuartal.id) for feature tests: a throwaway RSA
 * key pair published as the JWKS, id_tokens signed with it, and
 * Http::fake() responses for /oauth/token, /oauth/userinfo, /oauth/jwks.
 * No real network calls are made (Http::preventStrayRequests()).
 */
trait FakesKuartalId
{
    protected const KID = 'test-key-1';

    protected const BASE = 'https://id.kuartal.test';

    protected const CLIENT_ID = 'test-client-id';

    protected const NONCE = 'test-nonce-value';

    private static ?array $kuartalKeys = null;

    protected function setUpKuartalId(): void
    {
        config([
            'services.kuartal_id.base_url' => self::BASE,
            'services.kuartal_id.issuer' => self::BASE,
            'services.kuartal_id.client_id' => self::CLIENT_ID,
            'services.kuartal_id.client_secret' => 'test-client-secret',
            'services.kuartal_id.redirect' => 'http://localhost/auth/kuartal-id/callback',
        ]);

        Http::preventStrayRequests();
    }

    /** @return array{private: string, jwk: array<string, string>} */
    protected static function kuartalKeyPair(): array
    {
        if (self::$kuartalKeys === null) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $private);
            $rsa = openssl_pkey_get_details($key)['rsa'];
            self::$kuartalKeys = [
                'private' => $private,
                'jwk' => [
                    'kty' => 'RSA', 'use' => 'sig', 'alg' => 'RS256', 'kid' => self::KID,
                    'n' => JWT::urlsafeB64Encode($rsa['n']),
                    'e' => JWT::urlsafeB64Encode($rsa['e']),
                ],
            ];
        }

        return self::$kuartalKeys;
    }

    /** @param  array<string, mixed>  $overrides  (null value = remove claim) */
    protected function makeIdToken(array $overrides = [], ?string $privateKey = null): string
    {
        $claims = array_merge([
            'iss' => self::BASE,
            'sub' => 'kuartal-sub-1',
            'aud' => self::CLIENT_ID,
            'iat' => time(),
            'exp' => time() + 600,
            'nonce' => self::NONCE,
            'email' => 'person@example.com',
            'email_verified' => true,
        ], $overrides);

        return JWT::encode(array_filter($claims, fn ($v) => $v !== null), $privateKey ?? self::kuartalKeyPair()['private'], 'RS256', self::KID);
    }

    /**
     * @param  array<string, mixed>  $userInfo  merged over the default userinfo claims
     * @param  array<string, mixed>  $idToken  id_token claim overrides
     * @param  array<string, mixed>  $tokenResponse  merged over the token response (id_token => null removes it)
     */
    protected function fakeKuartalId(array $userInfo = [], array $idToken = [], array $tokenResponse = []): void
    {
        $sub = $idToken['sub'] ?? 'kuartal-sub-1';
        $tokens = array_filter(array_merge([
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'id_token' => $this->makeIdToken($idToken),
        ], $tokenResponse), fn ($v) => $v !== null);

        Http::fake([
            self::BASE.'/oauth/token' => Http::response($tokens),
            self::BASE.'/oauth/userinfo' => Http::response(array_merge([
                'sub' => $sub,
                'email' => 'person@example.com',
                'email_verified' => true,
                'name' => 'Test Person',
                'picture' => null,
            ], $userInfo)),
            self::BASE.'/oauth/jwks' => Http::response(['keys' => [self::kuartalKeyPair()['jwk']]]),
        ]);
    }

    /** Hit the callback as if this browser had just been sent to Kuartal ID. */
    protected function kuartalCallback(array $session = []): TestResponse
    {
        return $this->withSession(array_merge([
            'kuartal_id.state' => 'test-state',
            'kuartal_id.nonce' => self::NONCE,
            'kuartal_id.verifier' => str_repeat('v', 64),
        ], $session))->get('/auth/kuartal-id/callback?state=test-state&code=test-code');
    }
}
