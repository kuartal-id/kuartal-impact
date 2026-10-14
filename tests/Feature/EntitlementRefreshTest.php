<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\KuartalId\SessionTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesKuartalId;
use Tests\TestCase;

class EntitlementRefreshTest extends TestCase
{
    use FakesKuartalId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpKuartalId();
        config(['services.kuartal_id.entitlements_refresh_minutes' => 15]);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create(['kuartal_id' => 'kuartal-sub-1']);
    }

    /** @return array<string, mixed> */
    private function tokenSession(int $checkedMinutesAgo, ?int $accessExpiresIn = 3600, ?string $refresh = 'test-refresh-token'): array
    {
        $session = [SessionTokens::CHECKED_AT_KEY => time() - $checkedMinutesAgo * 60];
        if ($accessExpiresIn !== null) {
            $session[SessionTokens::KEY] = Crypt::encryptString(json_encode([
                'access_token' => 'test-access-token',
                'refresh_token' => $refresh,
                'expires_at' => time() + $accessExpiresIn,
            ]));
        }

        return $session;
    }

    public function test_within_the_window_no_call_is_made(): void
    {
        Http::fake();

        $this->actingAs($this->admin())->withSession($this->tokenSession(5))
            ->get(route('admin.dashboard'))->assertOk();

        Http::assertNothingSent();
    }

    public function test_revoked_admin_entitlement_takes_effect_after_the_window(): void
    {
        $admin = $this->admin();
        $this->fakeKuartalId(['entitlements' => ['investor.portal']]);

        $this->actingAs($admin)->withSession($this->tokenSession(16))
            ->get(route('admin.dashboard'))->assertForbidden();

        $this->assertSame(['investor.portal'], $admin->fresh()->entitlements);
    }

    public function test_still_granted_entitlement_keeps_access(): void
    {
        $this->fakeKuartalId(['entitlements' => ['kuartalimpact.admin']]);

        $this->actingAs($this->admin())->withSession($this->tokenSession(16))
            ->get(route('admin.dashboard'))->assertOk();
    }

    public function test_expired_access_token_is_refreshed(): void
    {
        $this->fakeKuartalId(['entitlements' => ['kuartalimpact.admin']]);

        $this->actingAs($this->admin())->withSession($this->tokenSession(16, accessExpiresIn: -10))
            ->get(route('admin.dashboard'))->assertOk();

        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/oauth/token') && $r['grant_type'] === 'refresh_token');
    }

    public function test_session_without_tokens_must_sign_in_again(): void
    {
        Http::fake();

        $this->actingAs($this->admin())->withSession($this->tokenSession(16, accessExpiresIn: null))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login.kuartal-id', ['redirect' => '/admin']));

        $this->assertGuest();
    }

    public function test_revoked_tokens_must_sign_in_again(): void
    {
        Http::fake([
            self::BASE.'/oauth/userinfo' => Http::response(['error' => 'unauthenticated'], 401),
            self::BASE.'/oauth/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $this->actingAs($this->admin())->withSession($this->tokenSession(16))
            ->get(route('admin.dashboard'))->assertRedirect();

        $this->assertGuest();
    }

    public function test_short_kuartal_id_outage_keeps_access_then_fails_closed(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $admin = $this->admin();

        $this->actingAs($admin)->withSession($this->tokenSession(20))
            ->get(route('admin.dashboard'))->assertOk();

        $this->actingAs($admin)->withSession($this->tokenSession(31))
            ->get(route('admin.dashboard'))->assertStatus(503);
    }
}
