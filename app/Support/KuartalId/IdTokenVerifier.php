<?php

namespace App\Support\KuartalId;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Validates the OpenID Connect id_token Kuartal ID returns from
 * /oauth/token: RS256 signature against the published JWKS (cached, and
 * re-fetched at most once a minute when an unknown `kid` shows up after a
 * key rotation), `iss` == the Kuartal ID issuer, `aud` == our client id,
 * `exp`/`iat` within a 60s clock-skew leeway, and `nonce` == the one we
 * stored in the session before redirecting to /oauth/authorize.
 *
 * Any failure throws KuartalIdException -- never trust the token's
 * contents unless verify() returned normally.
 */
class IdTokenVerifier
{
    public const LEEWAY_SECONDS = 60;

    public const JWKS_CACHE_KEY = 'kuartal_id.jwks';

    public const JWKS_CACHE_HOURS = 12;

    public function __construct(private KuartalIdClient $client) {}

    /**
     * @return array<string, mixed> the verified claims
     */
    public function verify(mixed $idToken, ?string $expectedNonce): array
    {
        if (! is_string($idToken) || $idToken === '') {
            throw KuartalIdException::invalid('id_token missing from token response');
        }

        if (! is_string($expectedNonce) || $expectedNonce === '') {
            throw KuartalIdException::invalid('no nonce stored in session');
        }

        $claims = $this->decode($idToken);

        if (! is_string($claims['iss'] ?? null) || rtrim($claims['iss'], '/') !== $this->client->issuer()) {
            throw KuartalIdException::invalid('id_token iss mismatch');
        }

        $aud = $claims['aud'] ?? null;
        $audiences = is_array($aud) ? $aud : [$aud];
        if ($this->client->clientId() === '' || ! in_array($this->client->clientId(), $audiences, true)) {
            throw KuartalIdException::invalid('id_token aud mismatch');
        }
        if (count($audiences) > 1 && ($claims['azp'] ?? null) !== $this->client->clientId()) {
            throw KuartalIdException::invalid('id_token azp mismatch');
        }

        if (! is_numeric($claims['exp'] ?? null) || ! is_numeric($claims['iat'] ?? null)) {
            throw KuartalIdException::invalid('id_token exp/iat missing');
        }

        if (! is_string($claims['nonce'] ?? null) || ! hash_equals($expectedNonce, $claims['nonce'])) {
            throw KuartalIdException::invalid('id_token nonce mismatch');
        }

        if (! is_string($claims['sub'] ?? null) || $claims['sub'] === '') {
            throw KuartalIdException::invalid('id_token sub missing');
        }

        return $claims;
    }

    /** @return array<string, mixed> */
    private function decode(string $jwt): array
    {
        $kid = $this->headerKid($jwt);

        $keys = $this->keys(fresh: false);
        if (! isset($keys[$kid]) && Cache::add(self::JWKS_CACHE_KEY.'.refetch', true, 60)) {
            $keys = $this->keys(fresh: true);
        }

        $previousLeeway = JWT::$leeway;
        JWT::$leeway = self::LEEWAY_SECONDS;

        try {
            $payload = JWT::decode($jwt, $keys);
        } catch (Throwable $e) {
            throw KuartalIdException::invalid('id_token failed verification ('.class_basename($e).': '.$e->getMessage().')', $e);
        } finally {
            JWT::$leeway = $previousLeeway;
        }

        return json_decode(json_encode($payload), true) ?: [];
    }

    private function headerKid(string $jwt): string
    {
        $parts = explode('.', $jwt);

        try {
            $header = count($parts) === 3 ? JWT::jsonDecode(JWT::urlsafeB64Decode($parts[0])) : null;
        } catch (Throwable $e) {
            $header = null;
        }

        if (! is_object($header) || ($header->alg ?? null) !== 'RS256') {
            throw KuartalIdException::invalid('id_token is not an RS256 JWT');
        }

        if (! is_string($header->kid ?? null) || $header->kid === '') {
            throw KuartalIdException::invalid('id_token has no kid');
        }

        return $header->kid;
    }

    /** @return array<string, Key> */
    private function keys(bool $fresh): array
    {
        $jwks = $fresh ? null : Cache::get(self::JWKS_CACHE_KEY);

        if (! is_array($jwks)) {
            $jwks = $this->client->jwks();
            Cache::put(self::JWKS_CACHE_KEY, $jwks, now()->addHours(self::JWKS_CACHE_HOURS));
        }

        try {
            return JWK::parseKeySet($jwks, 'RS256');
        } catch (Throwable $e) {
            Cache::forget(self::JWKS_CACHE_KEY);

            throw KuartalIdException::invalid('JWKS could not be parsed', $e);
        }
    }
}
