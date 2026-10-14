<?php

namespace App\Support\KuartalId;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP client for Kuartal ID (id.kuartal.id, repo kuartal-login):
 * builds the /oauth/authorize URL and calls /oauth/token,
 * /oauth/userinfo and /oauth/jwks.
 *
 * Every request has a hard timeout (10s, 5s to connect) and every
 * failure -- network error, timeout, non-2xx, malformed JSON -- surfaces
 * as a KuartalIdException, so a slow or broken Kuartal ID shows a
 * friendly message instead of hanging or 500ing this site.
 */
class KuartalIdClient
{
    public const TIMEOUT_SECONDS = 10;

    public const CONNECT_TIMEOUT_SECONDS = 5;

    public function baseUrl(): string
    {
        return rtrim((string) config('services.kuartal_id.base_url'), '/');
    }

    /** Expected `iss` of id_tokens: KUARTAL_ID_ISSUER, else the base URL. */
    public function issuer(): string
    {
        return rtrim((string) (config('services.kuartal_id.issuer') ?: $this->baseUrl()), '/');
    }

    /**
     * The registered redirect URI (kuartalimpact.org has a single host).
     */
    public function redirectUri(): string
    {
        return (string) config('services.kuartal_id.redirect');
    }

    public function clientId(): string
    {
        return (string) config('services.kuartal_id.client_id');
    }

    /** Space-separated scopes from KUARTAL_ID_SCOPES (always includes openid). */
    public function scopes(): string
    {
        $scopes = preg_split('/[\s,]+/', (string) config('services.kuartal_id.scopes'), -1, PREG_SPLIT_NO_EMPTY);

        if (! in_array('openid', $scopes, true)) {
            array_unshift($scopes, 'openid');
        }

        return implode(' ', array_unique($scopes));
    }

    public function authorizeUrl(string $state, string $codeChallenge, string $nonce): string
    {
        return $this->baseUrl().'/oauth/authorize?'.http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => $this->scopes(),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * Exchange the authorization code for tokens. The nonce is sent here
     * too because Kuartal ID currently takes it from the token request
     * body when minting the id_token (kuartal-login TokenController); it
     * is also sent on /oauth/authorize, the standard OIDC place.
     *
     * @return array<string, mixed> token response (access_token, id_token, ...)
     */
    public function exchangeCode(string $code, string $codeVerifier, string $nonce): array
    {
        $tokens = $this->send('token exchange', fn (PendingRequest $http) => $http->asForm()->post($this->baseUrl().'/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId(),
            'client_secret' => config('services.kuartal_id.client_secret'),
            'redirect_uri' => $this->redirectUri(),
            'code' => $code,
            'code_verifier' => $codeVerifier,
            'nonce' => $nonce,
        ]));

        if (! is_string($tokens['access_token'] ?? null) || $tokens['access_token'] === '') {
            throw KuartalIdException::invalid('token response has no access_token');
        }

        return $tokens;
    }

    /** @return array<string, mixed> */
    public function refresh(string $refreshToken): array
    {
        $tokens = $this->send('token refresh', fn (PendingRequest $http) => $http->asForm()->post($this->baseUrl().'/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId(),
            'client_secret' => config('services.kuartal_id.client_secret'),
            'refresh_token' => $refreshToken,
        ]));

        if (! is_string($tokens['access_token'] ?? null) || $tokens['access_token'] === '') {
            throw KuartalIdException::invalid('refresh response has no access_token');
        }

        return $tokens;
    }

    /** @return array<string, mixed> userinfo claims (always has a non-empty string `sub`) */
    public function userInfo(string $accessToken): array
    {
        $claims = $this->send('userinfo', fn (PendingRequest $http) => $http->withToken($accessToken)->get($this->baseUrl().'/oauth/userinfo'));

        if (! is_string($claims['sub'] ?? null) || $claims['sub'] === '') {
            throw KuartalIdException::invalid('userinfo has no sub');
        }

        return $claims;
    }

    /** @return array<string, mixed> the JSON Web Key Set */
    public function jwks(): array
    {
        $jwks = $this->send('jwks', fn (PendingRequest $http) => $http->get($this->baseUrl().'/oauth/jwks'));

        if (! is_array($jwks['keys'] ?? null) || $jwks['keys'] === []) {
            throw KuartalIdException::invalid('JWKS has no keys');
        }

        return $jwks;
    }

    public function endSessionUrl(string $postLogoutRedirectUri): string
    {
        return $this->baseUrl().'/oauth/logout?'.http_build_query([
            'post_logout_redirect_uri' => $postLogoutRedirectUri,
        ]);
    }

    /**
     * @param  callable(PendingRequest): Response  $request
     * @return array<string, mixed>
     */
    private function send(string $what, callable $request): array
    {
        try {
            $response = $request(
                Http::timeout(self::TIMEOUT_SECONDS)->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)->acceptJson()
            );
        } catch (ConnectionException $e) {
            throw KuartalIdException::unreachable($what, $e);
        }

        if ($response->failed()) {
            $error = $response->json('error');

            throw KuartalIdException::failed($what, $response->status(), is_string($error) ? substr($error, 0, 100) : null);
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw KuartalIdException::invalid("{$what} returned non-JSON");
        }

        return $json;
    }
}
