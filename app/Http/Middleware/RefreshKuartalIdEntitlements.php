<?php

namespace App\Http\Middleware;

use App\Support\KuartalId\KuartalIdClient;
use App\Support\KuartalId\KuartalIdException;
use App\Support\KuartalId\SessionTokens;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the signed-in account's Kuartal ID entitlement snapshot fresh on
 * the admin, so revoking `kuartalimpact.admin` at Kuartal ID takes effect within
 * KUARTAL_ID_ENTITLEMENTS_REFRESH_MINUTES (default 15) instead of "next
 * time this person happens to log in".
 *
 * Every refresh window it calls Kuartal ID /oauth/userinfo with the
 * access token stored in this session at sign-in (refreshing it with the
 * refresh token when it has expired) and overwrites
 * users.entitlements. Runs before the employee/hr-admin/super-admin
 * gates, which then decide on the fresh snapshot.
 *
 * Fails closed:
 *  - no tokens in the session (e.g. restored from a remember-me cookie)
 *    or Kuartal ID says the token/refresh token is no longer valid ->
 *    log out and send the person back through Kuartal ID sign-in;
 *  - Kuartal ID unreachable -> keep the snapshot for up to one extra
 *    window (a short outage doesn't kick admins out mid-task), then 503.
 */
class RefreshKuartalIdEntitlements
{
    public function __construct(private KuartalIdClient $kuartalId) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->kuartal_id) {
            return $next($request);
        }

        $session = $request->session();
        $window = max(1, (int) config('services.kuartal_id.entitlements_refresh_minutes', 15)) * 60;
        $age = time() - SessionTokens::checkedAt($session);

        if ($age < $window) {
            return $next($request);
        }

        $tokens = SessionTokens::get($session);

        if (! $tokens) {
            return $this->reauthenticate($request);
        }

        try {
            if ($tokens['expires_at'] <= time() + 30) {
                $tokens = $this->refresh($request, $tokens);
            }

            try {
                $claims = $this->kuartalId->userInfo($tokens['access_token']);
            } catch (KuartalIdException $e) {
                if (! $e->unauthorized) {
                    throw $e;
                }
                $tokens = $this->refresh($request, $tokens);
                $claims = $this->kuartalId->userInfo($tokens['access_token']);
            }
        } catch (KuartalIdException $e) {
            if ($e->unauthorized) {
                return $this->reauthenticate($request);
            }

            report($e);

            if ($age < 2 * $window) {
                return $next($request);
            }

            abort(503, "We couldn't confirm your access with Kuartal ID just now. Please try again in a minute.");
        }

        if ($claims['sub'] !== $user->kuartal_id) {
            return $this->reauthenticate($request);
        }

        $user->entitlements = array_values(array_filter((array) ($claims['entitlements'] ?? []), 'is_string'));
        $user->save();
        SessionTokens::markChecked($session);

        return $next($request);
    }

    /**
     * @param  array{access_token: string, refresh_token: ?string, expires_at: int}  $tokens
     * @return array{access_token: string, refresh_token: ?string, expires_at: int}
     */
    private function refresh(Request $request, array $tokens): array
    {
        if (! is_string($tokens['refresh_token'] ?? null) || $tokens['refresh_token'] === '') {
            $e = KuartalIdException::invalid('access token expired and there is no refresh token');
            $e->unauthorized = true;

            throw $e;
        }

        try {
            $fresh = $this->kuartalId->refresh($tokens['refresh_token']);
        } catch (KuartalIdException $e) {
            // 400 invalid_grant / 401 = refresh token revoked or expired.
            if (in_array($e->status, [400, 401], true)) {
                $e->unauthorized = true;
            }

            throw $e;
        }

        SessionTokens::store($request->session(), $fresh, $tokens['refresh_token']);

        return SessionTokens::get($request->session());
    }

    private function reauthenticate(Request $request): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $params = $request->isMethod('GET') ? ['redirect' => $request->getRequestUri()] : [];

        return redirect()->route('login.kuartal-id', $params);
    }
}
