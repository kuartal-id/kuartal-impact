<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\FakesKuartalId;
use Tests\TestCase;

class KuartalIdLoginTest extends TestCase
{
    use FakesKuartalId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpKuartalId();
    }

    public function test_redirect_sends_nonce_pkce_and_configured_scopes(): void
    {
        config(['services.kuartal_id.scopes' => 'openid profile email entitlements']);

        $response = $this->get('/auth/kuartal-id/redirect');

        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertStringStartsWith(self::BASE.'/oauth/authorize?', $response->headers->get('Location'));
        $this->assertSame('openid profile email entitlements', $query['scope']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertNotEmpty($query['nonce']);
        $this->assertSame(session('kuartal_id.nonce'), $query['nonce']);
        $this->assertSame(session('kuartal_id.state'), $query['state']);
    }

    public function test_valid_sign_in_creates_the_account_with_entitlements(): void
    {
        $this->fakeKuartalId(['entitlements' => ['investor.portal']]);

        $this->kuartalCallback()->assertRedirect('/');

        $user = User::where('kuartal_id', 'kuartal-sub-1')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(['investor.portal'], $user->entitlements);
        $this->assertSame('person@example.com', $user->email);
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/oauth/token') && $r['nonce'] === self::NONCE);
    }

    public function test_nonce_mismatch_is_rejected(): void
    {
        $this->fakeKuartalId(idToken: ['nonce' => 'some-other-nonce']);

        $this->kuartalCallback()->assertRedirect(route('home'))->assertSessionHas('kuartal_id_error');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_missing_id_token_is_rejected(): void
    {
        $this->fakeKuartalId(tokenResponse: ['id_token' => null]);

        $this->kuartalCallback()->assertRedirect(route('home'))->assertSessionHas('kuartal_id_error');
        $this->assertGuest();
    }

    public function test_missing_nonce_in_session_is_rejected(): void
    {
        $this->fakeKuartalId();

        $this->kuartalCallback(['kuartal_id.nonce' => null])->assertRedirect(route('home'))->assertSessionHas('kuartal_id_error');
        $this->assertGuest();
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function badIdTokens(): array
    {
        return [
            'wrong issuer' => [['iss' => 'https://evil.example']],
            'lookalike issuer' => [['iss' => 'https://id.kuartal.test.evil.example']],
            'wrong audience' => [['aud' => 'some-other-client']],
            'expired' => [['exp' => time() - 3600, 'iat' => time() - 7200]],
            'issued in the future' => [['iat' => time() + 3600]],
            'no nonce' => [['nonce' => null]],
            'no exp' => [['exp' => null]],
        ];
    }

    #[DataProvider('badIdTokens')]
    public function test_invalid_id_tokens_are_rejected(array $claims): void
    {
        $this->fakeKuartalId(idToken: $claims);

        $this->kuartalCallback()->assertRedirect(route('home'))->assertSessionHas('kuartal_id_error');
        $this->assertGuest();
    }

    public function test_id_token_signed_with_an_unknown_key_is_rejected(): void
    {
        $other = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($other, $otherPrivate);
        $this->fakeKuartalId(tokenResponse: ['id_token' => $this->makeIdToken([], $otherPrivate)]);

        $this->kuartalCallback()->assertRedirect(route('home'))->assertSessionHas('kuartal_id_error');
        $this->assertGuest();
    }

    public function test_userinfo_sub_must_match_id_token_sub(): void
    {
        $this->fakeKuartalId(userInfo: ['sub' => 'someone-else']);

        $this->kuartalCallback()->assertRedirect(route('home'))->assertSessionHas('kuartal_id_error');
        $this->assertGuest();
    }

    public function test_kuartal_id_timeout_shows_a_friendly_error_not_a_500(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->kuartalCallback()->assertRedirect(route('home'))->assertSessionHas('kuartal_id_error');
        $this->assertGuest();
    }

    public function test_kuartal_id_server_error_shows_a_friendly_error_not_a_500(): void
    {
        Http::fake([self::BASE.'/*' => Http::response(['error' => 'server_error'], 500)]);

        $this->kuartalCallback()->assertRedirect(route('home'))->assertSessionHas('kuartal_id_error');
    }

    public function test_email_already_used_by_another_kuartal_id_account_does_not_500(): void
    {
        $other = User::factory()->create(['email' => 'person@example.com', 'kuartal_id' => 'other-sub']);
        $this->fakeKuartalId();

        $this->kuartalCallback()->assertRedirect(route('home'));

        $user = User::where('kuartal_id', 'kuartal-sub-1')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('kuartal-sub-1@kuartal-id.local', $user->email);
        $this->assertSame('person@example.com', $other->fresh()->email);
        $this->assertSame('other-sub', $other->fresh()->kuartal_id);
    }

    public function test_changed_email_already_used_by_another_account_keeps_the_existing_email(): void
    {
        $me = User::factory()->create(['email' => 'old@example.com', 'kuartal_id' => 'kuartal-sub-1']);
        User::factory()->create(['email' => 'person@example.com', 'kuartal_id' => 'other-sub']);
        $this->fakeKuartalId();

        $this->kuartalCallback()->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($me);
        $this->assertSame('old@example.com', $me->fresh()->email);
    }

    public function test_unverified_unlinked_local_account_with_same_email_is_not_linked(): void
    {
        $local = User::factory()->create(['email' => 'person@example.com', 'kuartal_id' => null, 'email_verified_at' => null]);
        $this->fakeKuartalId();

        $this->kuartalCallback()->assertRedirect(route('home'))->assertSessionHas('kuartal_id_error');

        $this->assertGuest();
        $this->assertNull($local->fresh()->kuartal_id);
    }

    public function test_verified_unlinked_local_account_with_same_verified_email_is_linked(): void
    {
        $local = User::factory()->create(['email' => 'person@example.com', 'kuartal_id' => null]);
        $this->fakeKuartalId();

        $this->kuartalCallback()->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($local);
        $this->assertSame('kuartal-sub-1', $local->fresh()->kuartal_id);
    }

    /** @return array<string, array{0: string}> */
    public static function openRedirectTargets(): array
    {
        return [
            'userinfo trick' => ['https://kuartalimpact.org@evil.com/'],
            'suffix domain' => ['https://kuartalimpact.org.evil.com/portal'],
            'other host' => ['https://evil.com/'],
            'protocol relative' => ['//evil.com/'],
            'backslash' => ['/\\evil.com'],
            'javascript' => ['javascript:alert(1)'],
        ];
    }

    #[DataProvider('openRedirectTargets')]
    public function test_open_redirects_are_blocked(string $target): void
    {
        config(['app.url' => 'https://kuartalimpact.org']);

        $this->get('/auth/kuartal-id/redirect?redirect='.urlencode($target));
        $this->assertNull(session('kuartal_id.intended'));

        $this->get('/auth/kuartal-id/redirect', ['Referer' => $target]);
        $this->assertNull(session('kuartal_id.intended'));

        // Even if something unsafe were already in the session, the
        // callback re-checks it.
        $this->fakeKuartalId();
        $this->kuartalCallback(['kuartal_id.intended' => $target])->assertRedirect(route('home'));
    }

    public function test_same_origin_targets_are_kept(): void
    {
        config(['app.url' => 'https://kuartalimpact.org']);

        $this->get('/auth/kuartal-id/redirect?redirect='.urlencode('https://kuartalimpact.org/financials?x=1'));
        $this->assertSame('/financials?x=1', session('kuartal_id.intended'));

        $this->fakeKuartalId();
        $this->kuartalCallback(['kuartal_id.intended' => '/jobs'])->assertRedirect('/jobs');
    }

    public function test_admin_accounts_do_not_get_a_remember_me_cookie(): void
    {
        $this->fakeKuartalId(['entitlements' => ['kuartalimpact.admin']]);

        $response = $this->kuartalCallback();

        $response->assertCookieMissing(Auth::guard()->getRecallerName());
        $this->assertNull(User::where('kuartal_id', 'kuartal-sub-1')->first()->remember_token);
    }
}
