<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\KuartalId\IdTokenVerifier;
use App\Support\KuartalId\KuartalIdClient;
use App\Support\KuartalId\KuartalIdException;
use App\Support\KuartalId\SessionTokens;
use App\Support\SafeRedirect;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * "Log in with Kuartal ID" -- the OAuth2/OIDC authorization code flow
 * (with PKCE, which Kuartal ID requires of every client) against
 * id.kuartal.id (kuartal-login).
 *
 * There's no local password login on kuartalimpact.org at all -- every
 * user here is created from a successful Kuartal ID sign-in and mapped
 * back to the same local row by kuartal_id (Kuartal ID's `sub` claim) on
 * every later visit. Whether that account can reach the admin or the
 * is decided entirely by the `kuartalimpact.admin`
 * entitlement in the `entitlements` claim below -- see
 * App\Http\Middleware\EnsureIsAdmin, and App\Http\Middleware\RefreshKuartalIdEntitlements
 * which re-checks them at Kuartal ID every few minutes.
 *
 * Security checks on the way back (see callback()): state (CSRF), PKCE
 * verifier, a verified id_token (RS256 signature via JWKS, iss, aud,
 * exp/iat, nonce -- App\Support\KuartalId\IdTokenVerifier), id_token sub
 * == userinfo sub, and a same-origin-only post-login redirect
 * (App\Support\SafeRedirect).
 */
class KuartalIdLoginController extends Controller
{
    private const FAILED = 'Kuartal ID sign-in failed -- please try again.';

    public function __construct(
        private KuartalIdClient $kuartalId,
        private IdTokenVerifier $idTokens,
    ) {}

    /**
     * Step 1: send the browser to Kuartal ID's /oauth/authorize with a
     * PKCE code challenge, and remember the verifier, a CSRF-style state
     * value and the OIDC nonce in the session to check on the way back.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $nonce = Str::random(40);
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $request->session()->put('kuartal_id.state', $state);
        $request->session()->put('kuartal_id.nonce', $nonce);
        $request->session()->put('kuartal_id.verifier', $verifier);

        // Preserve where the person was trying to go (e.g. /portal or
        // /admin/jobs) so the callback can send them back there instead
        // of always landing on the portal home. Only same-origin targets
        // survive SafeRedirect -- anything else is dropped (open redirect).
        $request->session()->put('kuartal_id.intended', SafeRedirect::sanitize(
            $request->query('redirect', $request->headers->get('referer'))
        ));

        return redirect($this->kuartalId->authorizeUrl($state, $challenge, $nonce));
    }

    /**
     * Step 2: Kuartal ID redirects back here with a code (or an error).
     * Exchange the code for tokens, verify the id_token, fetch the
     * person's identity + entitlements from /oauth/userinfo, and log them
     * into a local session.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->fail('Kuartal ID sign-in was cancelled.');
        }

        $state = $request->session()->pull('kuartal_id.state');
        $nonce = $request->session()->pull('kuartal_id.nonce');
        $verifier = $request->session()->pull('kuartal_id.verifier');
        $intended = SafeRedirect::sanitize($request->session()->pull('kuartal_id.intended'));

        if (! is_string($state) || ! is_string($request->query('state'))
            || ! hash_equals($state, $request->query('state')) || ! $request->filled('code')
            || ! is_string($verifier) || ! is_string($nonce)) {
            return $this->fail(self::FAILED);
        }

        try {
            $tokens = $this->kuartalId->exchangeCode((string) $request->query('code'), $verifier, $nonce);
            $idClaims = $this->idTokens->verify($tokens['id_token'] ?? null, $nonce);
            $claims = $this->kuartalId->userInfo($tokens['access_token']);

            if ($claims['sub'] !== $idClaims['sub']) {
                throw KuartalIdException::invalid('userinfo sub does not match id_token sub');
            }
        } catch (KuartalIdException $e) {
            report($e);

            return $this->fail(self::FAILED);
        }

        $user = $this->resolveUser($claims);

        if (is_string($user)) {
            return $this->fail($user);
        }

        // A flat array of entitlement keys (e.g. 'investor.portal'), per
        // the 'entitlements' scope -- see Kuartal ID's UserInfoController.
        // Refreshed on every sign-in AND every few minutes while signed in
        // (RefreshKuartalIdEntitlements), so a revoked entitlement stops
        // working within KUARTAL_ID_ENTITLEMENTS_REFRESH_MINUTES.
        $user->entitlements = array_values(array_filter((array) ($claims['entitlements'] ?? []), 'is_string'));
        $user->avatar_url = $claims['picture'] ?? null;

        try {
            $user->save();
        } catch (UniqueConstraintViolationException $e) {
            // Lost a race with a concurrent sign-in / another account
            // claiming the same email -- never a 500.
            report($e);

            return $this->fail(self::FAILED);
        }

        // No "remember me" cookie: admin access ends with the
        // browser session, and the entitlement refresh needs its tokens.
        Auth::login($user, remember: false);
        $request->session()->regenerate();
        SessionTokens::store($request->session(), $tokens);
        SessionTokens::markChecked($request->session());

        if ($intended) {
            return redirect()->to($intended);
        }

        return $user->isAdmin() ? redirect()->route('admin.dashboard') : redirect()->to('/');
    }

    /**
     * Signs out of kuartalimpact.org AND ends this browser's Kuartal ID
     * session (RP-initiated / "single") logout -- otherwise Kuartal ID
     * would stay signed in and the next "Log in" would silently pick the
     * same account back up.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away($this->kuartalId->endSessionUrl(url('/')));
    }

    /**
     * Find or create the local account for these (verified) Kuartal ID
     * claims without ever 500ing on the unique `email` column.
     *
     * - Known `sub` -> that row. Its email is only updated if no other row
     *   already uses the new address (otherwise logged and kept).
     * - Unknown `sub`, email already used by a row with no kuartal_id ->
     *   link to it only when both Kuartal ID and the local row say the
     *   email is verified; otherwise refuse with a friendly message.
     * - Unknown `sub`, email already used by a *different* Kuartal ID ->
     *   new row with a placeholder email (logged), never overwriting.
     *
     * @param  array<string, mixed>  $claims
     * @return User|string the user, or a message to show instead
     */
    private function resolveUser(array $claims): User|string
    {
        $sub = $claims['sub'];
        $email = is_string($claims['email'] ?? null) && $claims['email'] !== '' ? $claims['email'] : null;
        $emailVerified = ($claims['email_verified'] ?? false) === true;

        $user = User::where('kuartal_id', $sub)->first();
        $holder = $email ? User::where('email', $email)->first() : null;

        if (! $user && $holder) {
            if ($holder->kuartal_id === null) {
                if (! $emailVerified || ! $holder->email_verified_at) {
                    Log::warning('Kuartal ID sign-in refused: email belongs to an unlinked local account that is not verified on both sides.', [
                        'local_user_id' => $holder->id,
                    ]);

                    return 'An account with this email already exists here but could not be linked automatically. Please contact support.';
                }

                $user = $holder;
            } else {
                Log::warning('Kuartal ID sign-in: email already used by another Kuartal ID account; using a placeholder email.', [
                    'other_user_id' => $holder->id,
                ]);
                $email = null;
            }
        }

        $user ??= new User;

        if ($email && $holder && $holder->isNot($user)) {
            Log::warning('Kuartal ID sign-in: new email already used by another account; keeping the existing email.', [
                'user_id' => $user->id,
                'other_user_id' => $holder->id,
            ]);
            $email = null;
        }

        $user->kuartal_id = $sub;
        $user->name = $claims['name'] ?? $user->name ?? 'Kuartal ID member';
        $newEmail = $email ?? $user->email ?? $sub.'@kuartal-id.local';
        if ($newEmail !== $user->email) {
            $user->email = $newEmail;
            $user->email_verified_at = $email && $emailVerified ? now() : null;
        } elseif ($email && $emailVerified) {
            $user->email_verified_at ??= now();
        }
        if (! $user->exists) {
            // SSO users never log in with a password here -- a random,
            // never-communicated hash just satisfies the NOT NULL column.
            $user->password = Hash::make(Str::random(40));
        }

        return $user;
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->to('/')->with('kuartal_id_error', $message);
    }
}
